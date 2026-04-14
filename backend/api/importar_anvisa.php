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

// ---- Importante: função cURL global para evitar redeclaração ----
function fazerCurlAnvisa($url) {
    if (!function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Guest',
            'Referer: https://consultas.anvisa.gov.br/',
        ],
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200 ? $resp : false;
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
    $pagina = (int) ($_GET['pagina'] ?? 1);
    if ($pagina < 1) {
        $pagina = 1;
    }

    $total = 0;
    $novos = 0;

    $url = 'https://consultas.anvisa.gov.br/api/consulta/medicamentos'
        . '?count=50&page=' . $pagina
        . '&filter%5BsituacaoRegistro%5D=Ativo';

    $response = fazerCurlAnvisa($url);

    if (!$response) {
        echo json_encode([
            'success' => false,
            'message' => 'ANVISA não respondeu. Tente novamente.',
            'pagina' => $pagina,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode($response, true);
    $items = $data['content'] ?? [];
    $total = $data['totalElements'] ?? 0;
    $totalPg = $data['totalPages'] ?? 1;

    try {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO medicamentos_anvisa
                (nome, principio_ativo, classe_terapeutica,
                 laboratorio, registro, situacao, tipo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $nome = $item['nomeProduto'] ?? '';
            if (!$nome) {
                continue;
            }
            $stmt->execute([
                $nome,
                $item['principioAtivo'] ?? '',
                $item['classeTerapeutica'] ?? '',
                $item['empresa'] ?? '',
                $item['numRegistro'] ?? '',
                strtolower($item['situacaoRegistro'] ?? 'ativo'),
                strtolower($item['categoriaRegulatoria'] ?? 'generico'),
            ]);
            $novos += (int) $stmt->rowCount();
        }

        echo json_encode([
            'success' => true,
            'pagina_atual' => $pagina,
            'total_pages' => $totalPg,
            'total_anvisa' => $total,
            'importados' => $novos,
            'proxima_pagina' => $pagina < $totalPg ? $pagina + 1 : null,
            'concluido' => $pagina >= $totalPg,
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'pagina' => $pagina,
        ], JSON_UNESCAPED_UNICODE);
    }
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
