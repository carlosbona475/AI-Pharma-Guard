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
define('ANVISA_CSV_URL', 'https://dados.anvisa.gov.br/dados/DADOS_ABERTOS_MEDICAMENTOS.csv');
define('ANVISA_CSV_LOCAL', sys_get_temp_dir() . '/anvisa_meds.csv');

function baixarCsvAnvisa($url, $destino) {
    if (!function_exists('curl_init')) {
        return false;
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $data = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$data || strlen($data) < 1000) {
        return false;
    }
    return file_put_contents($destino, $data) !== false;
}

function convLinhaUtf8(array $linha) {
    $out = [];
    foreach ($linha as $col) {
        $out[] = mb_convert_encoding((string) $col, 'UTF-8', 'ISO-8859-1');
    }
    return $out;
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
    // 1) baixar CSV direto
    if (!baixarCsvAnvisa(ANVISA_CSV_URL, ANVISA_CSV_LOCAL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Não foi possível baixar o CSV da ANVISA.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2) abrir CSV local
    $fp = @fopen(ANVISA_CSV_LOCAL, 'r');
    if (!$fp) {
        echo json_encode([
            'success' => false,
            'message' => 'Não foi possível abrir o CSV local.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3) primeira linha é cabeçalho (ignorar)
    $header = fgetcsv($fp, 0, ';');
    if ($header === false) {
        fclose($fp);
        echo json_encode(['success' => false, 'message' => 'CSV vazio ou inválido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $headerUtf8 = convLinhaUtf8($header);
    $indices = array_flip(array_map(function ($h) {
        return strtoupper(trim($h));
    }, $headerUtf8));

    // 4) paginação por bloco de 200
    $pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
    $porPagina = 200;
    $offset = ($pagina - 1) * $porPagina;

    $linhaAtual = 0;
    $processadas = 0;
    $importados = 0;
    $hasMore = false;

    try {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO medicamentos_anvisa
                (nome, principio_ativo, classe_terapeutica,
                 laboratorio, registro, situacao, tipo, data_vencimento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $conn->beginTransaction();
        while (($row = fgetcsv($fp, 0, ';')) !== false) {
            if (!is_array($row) || empty($row)) {
                continue;
            }
            if ($linhaAtual < $offset) {
                $linhaAtual++;
                continue;
            }
            if ($processadas >= $porPagina) {
                $hasMore = true;
                break;
            }
            $linhaAtual++;
            $processadas++;

            $cols = convLinhaUtf8($row);
            $nome = trim((string) ($cols[$indices['PRODUTO']] ?? ''));
            if ($nome === '') {
                continue;
            }
            $principioAtivo = (string) ($cols[$indices['PRINCIPIO_ATIVO']] ?? '');
            $classeTerapeutica = (string) ($cols[$indices['CLASSE_TERAPEUTICA']] ?? '');
            $laboratorio = (string) ($cols[$indices['EMPRESA_DETENTORA_REGISTRO']] ?? '');
            $registro = (string) ($cols[$indices['NUMERO_REGISTRO_ANVISA']] ?? '');
            $situacao = mb_strtolower((string) ($cols[$indices['SITUACAO_REGISTRO']] ?? 'ativo'), 'UTF-8');
            $tipo = mb_strtolower((string) ($cols[$indices['CATEGORIA_REGULATORIA']] ?? ''), 'UTF-8');
            $dataVenc = trim((string) ($cols[$indices['DATA_VENCIMENTO_REGISTRO']] ?? ''));
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
            $importados += (int) $stmt->rowCount();
        }
        fclose($fp);
        $conn->commit();

        echo json_encode([
            'success' => true,
            'pagina_atual' => $pagina,
            'importados' => $importados,
            'proxima_pagina' => $pagina + 1,
            'concluido' => !$hasMore,
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

if ($action === 'testar_urls') {
    $urls = [
        'https://dados.anvisa.gov.br/dados/DADOS_ABERTOS_MEDICAMENTOS.csv',
    ];
    $resultados = [];
    foreach ($urls as $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        $resultados[] = [
            'url'      => $url,
            'http'     => $code,
            'tamanho'  => round($size / 1024 / 1024, 2) . ' MB',
            'acessivel'=> $code === 200
        ];
    }
    echo json_encode(['resultados' => $resultados], 
                     JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Status do download do ZIP ----
if ($action === 'download_status') {
    $existe = file_exists(ANVISA_CSV_LOCAL);
    $bytes = $existe ? (int) filesize(ANVISA_CSV_LOCAL) : 0;
    $mb = $bytes > 0 ? round($bytes / 1048576, 2) : 0;
    echo json_encode([
        'success' => true,
        'arquivo' => ANVISA_CSV_LOCAL,
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
