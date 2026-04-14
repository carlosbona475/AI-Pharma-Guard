<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
set_time_limit(120);

// Proteção — só admin pode importar
$adminKey = $_GET['admin_key'] ?? '';
if (!hash_equals('Ph4rm4Gu4rd#Adm!n2025', $adminKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$action = $_GET['action'] ?? 'status';
define('ANVISA_ZIP_URL', 'https://dados.anvisa.gov.br/dados/DADOS_ABERTOS_MEDICAMENTOS.zip');
define('ANVISA_ZIP_LOCAL', sys_get_temp_dir() . '/DADOS_ABERTOS_MEDICAMENTOS.zip');

function fazerCurl($url) {
    if (!function_exists('curl_init')) return false;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => 'gzip, deflate, br',
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Authorization: Guest',
            'Connection: keep-alive',
            'Host: consultas.anvisa.gov.br',
            'Origin: https://consultas.anvisa.gov.br',
            'Referer: https://consultas.anvisa.gov.br/medicamentos',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"'
        ],
        CURLOPT_COOKIEJAR      => sys_get_temp_dir() . '/anvisa_cookie.txt',
        CURLOPT_COOKIEFILE     => sys_get_temp_dir() . '/anvisa_cookie.txt',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode !== 200) return false;
    return $response;
}

function downloadArquivoAnvisa($url, $destino) {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'erro' => 'cURL não disponível no servidor.'];
    }

    $fp = @fopen($destino, 'wb');
    if (!$fp) {
        return ['ok' => false, 'erro' => 'Não foi possível criar arquivo temporário do ZIP.'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (AI Pharma Guard Importador ANVISA)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/zip,application/octet-stream,*/*',
        ],
    ]);

    $ok = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if (!$ok || $httpCode !== 200) {
        @unlink($destino);
        return ['ok' => false, 'erro' => $erro !== '' ? $erro : ('Falha no download (HTTP ' . $httpCode . ').')];
    }

    if (!file_exists($destino) || filesize($destino) <= 0) {
        @unlink($destino);
        return ['ok' => false, 'erro' => 'Download concluído, mas o arquivo ZIP está vazio.'];
    }

    return ['ok' => true];
}

function extrairCsvAnvisa($zipPath) {
    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'erro' => 'Extensão ZipArchive não disponível no servidor.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['ok' => false, 'erro' => 'Não foi possível abrir o ZIP da ANVISA.'];
    }

    $csvPath = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat || empty($stat['name'])) {
            continue;
        }
        $nomeInterno = $stat['name'];
        if (strtolower(pathinfo($nomeInterno, PATHINFO_EXTENSION)) === 'csv') {
            $csvPath = sys_get_temp_dir() . '/anvisa_' . md5($nomeInterno) . '.csv';
            $conteudo = $zip->getFromIndex($i);
            if ($conteudo === false) {
                $zip->close();
                return ['ok' => false, 'erro' => 'Falha ao extrair CSV do ZIP.'];
            }
            file_put_contents($csvPath, $conteudo);
            break;
        }
    }
    $zip->close();

    if (!$csvPath || !file_exists($csvPath)) {
        return ['ok' => false, 'erro' => 'Nenhum arquivo CSV encontrado no ZIP.'];
    }

    return ['ok' => true, 'csv' => $csvPath];
}

function toUtf8($valor) {
    $valor = (string) $valor;
    if ($valor === '') {
        return '';
    }
    $conv = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $valor);
    return $conv !== false ? $conv : $valor;
}

// ---- Criar tabela medicamentos_anvisa ----
if ($action === 'criar_tabela') {
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS medicamentos_anvisa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                principio_ativo TEXT,
                classe_terapeutica VARCHAR(255),
                laboratorio VARCHAR(255),
                registro VARCHAR(50),
                situacao VARCHAR(50) DEFAULT 'ativo',
                tipo VARCHAR(50),
                data_vencimento DATE NULL,
                imported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_registro (registro),
                KEY idx_nome (nome(100)),
                KEY idx_principio (principio_ativo(100)),
                KEY idx_situacao (situacao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo json_encode([
            'success' => true,
            'message' => 'Tabela medicamentos_anvisa criada!',
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ---- Importar página de medicamentos ----
if ($action === 'importar') {
    $dl = downloadArquivoAnvisa(ANVISA_ZIP_URL, ANVISA_ZIP_LOCAL);
    if (!$dl['ok']) {
        echo json_encode([
            'success' => false,
            'message' => $dl['erro'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ext = extrairCsvAnvisa(ANVISA_ZIP_LOCAL);
    if (!$ext['ok']) {
        echo json_encode([
            'success' => false,
            'message' => $ext['erro'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $csvPath = $ext['csv'];

    $fp = @fopen($csvPath, 'r');
    if (!$fp) {
        echo json_encode([
            'success' => false,
            'message' => 'Não foi possível abrir o CSV extraído.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $header = fgetcsv($fp, 0, ';');
    if (!is_array($header) || count($header) === 0) {
        fclose($fp);
        echo json_encode([
            'success' => false,
            'message' => 'Cabeçalho CSV inválido.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $header = array_map(function ($h) {
        return strtoupper(trim(toUtf8($h)));
    }, $header);
    $idx = array_flip($header);

    try {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO medicamentos_anvisa
                (nome, principio_ativo, classe_terapeutica,
                 laboratorio, registro, situacao, tipo, data_vencimento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $totalLido = 0;
        $totalInserido = 0;
        $batchCount = 0;

        $conn->beginTransaction();
        while (($row = fgetcsv($fp, 0, ';')) !== false) {
            if (!is_array($row) || count($row) === 0) {
                continue;
            }

            $totalLido++;
            $nome = isset($idx['PRODUTO']) ? toUtf8($row[$idx['PRODUTO']] ?? '') : '';
            if (trim($nome) === '') {
                continue;
            }

            $principioAtivo = isset($idx['PRINCIPIO_ATIVO']) ? toUtf8($row[$idx['PRINCIPIO_ATIVO']] ?? '') : '';
            $classeTerapeutica = isset($idx['CLASSE_TERAPEUTICA']) ? toUtf8($row[$idx['CLASSE_TERAPEUTICA']] ?? '') : '';
            $laboratorio = isset($idx['EMPRESA_DETENTORA_REGISTRO']) ? toUtf8($row[$idx['EMPRESA_DETENTORA_REGISTRO']] ?? '') : '';
            $registro = isset($idx['NUMERO_REGISTRO_ANVISA']) ? toUtf8($row[$idx['NUMERO_REGISTRO_ANVISA']] ?? '') : '';
            $situacao = isset($idx['SITUACAO_REGISTRO']) ? mb_strtolower(toUtf8($row[$idx['SITUACAO_REGISTRO']] ?? ''), 'UTF-8') : 'ativo';
            $tipo = isset($idx['CATEGORIA_REGULATORIA']) ? mb_strtolower(toUtf8($row[$idx['CATEGORIA_REGULATORIA']] ?? ''), 'UTF-8') : '';
            $dataVenc = isset($idx['DATA_VENCIMENTO_REGISTRO']) ? trim(toUtf8($row[$idx['DATA_VENCIMENTO_REGISTRO']] ?? '')) : '';
            $dataVencDb = null;
            if ($dataVenc !== '') {
                $dt = DateTime::createFromFormat('d/m/Y', $dataVenc);
                if ($dt instanceof DateTime) {
                    $dataVencDb = $dt->format('Y-m-d');
                } else {
                    $dt2 = DateTime::createFromFormat('Y-m-d', $dataVenc);
                    if ($dt2 instanceof DateTime) {
                        $dataVencDb = $dt2->format('Y-m-d');
                    }
                }
            }

            $stmt->execute([
                $nome,
                $principioAtivo,
                $classeTerapeutica,
                $laboratorio,
                $registro,
                $situacao !== '' ? $situacao : 'ativo',
                $tipo,
                $dataVencDb,
            ]);
            $totalInserido += (int) $stmt->rowCount();
            $batchCount++;

            if ($batchCount >= 100) {
                $conn->commit();
                $conn->beginTransaction();
                $batchCount = 0;
            }
        }
        fclose($fp);
        $conn->commit();
        @unlink($csvPath);

        echo json_encode([
            'success' => true,
            'message' => 'Importação da base de Dados Abertos concluída.',
            'total_lido' => $totalLido,
            'total_inserido' => $totalInserido,
            'arquivo_zip' => ANVISA_ZIP_LOCAL,
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        fclose($fp);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ---- Status do download do ZIP ----
if ($action === 'download_status') {
    $existe = file_exists(ANVISA_ZIP_LOCAL);
    $bytes = $existe ? (int) filesize(ANVISA_ZIP_LOCAL) : 0;
    $mb = $bytes > 0 ? round($bytes / 1048576, 2) : 0;
    echo json_encode([
        'success' => true,
        'arquivo' => ANVISA_ZIP_LOCAL,
        'baixado' => $existe,
        'tamanho_mb' => $mb,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Status da importação ----
if ($action === 'status') {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM medicamentos_anvisa");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        echo json_encode([
            'success' => true,
            'total_importados' => (int) $total,
            'tabela_existe' => true,
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'tabela_existe' => false,
            'total' => 0,
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ---- Buscar na base local ANVISA ----
if ($action === 'buscar') {
    $q = trim($_GET['q'] ?? '');
    if (!$q) {
        echo json_encode(['resultados' => [], 'total' => 0], JSON_UNESCAPED_UNICODE);
        exit;
    }
    try {
        $stmt = $conn->prepare("
            SELECT nome, principio_ativo, classe_terapeutica,
                   laboratorio, registro, situacao, tipo
            FROM medicamentos_anvisa
            WHERE (nome LIKE ? OR principio_ativo LIKE ?)
              AND situacao = 'ativo'
            ORDER BY nome ASC
            LIMIT 20
        ");
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'resultados' => $resultados,
            'total' => count($resultados),
            'fonte' => 'base_local_anvisa',
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode([
            'resultados' => [],
            'total' => 0,
            'erro' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Ação inválida.',
], JSON_UNESCAPED_UNICODE);
