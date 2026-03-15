<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getConnection();

    $tabelas = ['medicamentos', 'interacoes', 'pacientes'];
    foreach ($tabelas as $t) {
        $stmt = $conn->query("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'public' AND table_name = " . $conn->quote($t) . "
            ) AS ok
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($row['ok'])) {
            http_response_code(500);
            echo json_encode(['error' => "Tabela {$t} não existe no banco."]);
            exit;
        }
    }

    $stmt = $conn->query('SELECT COUNT(*) AS total FROM medicamentos');
    $total_medicamentos = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query('SELECT COUNT(*) AS total FROM interacoes');
    $total_interacoes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query('SELECT COUNT(*) AS total FROM pacientes');
    $total_pacientes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'total_medicamentos' => $total_medicamentos,
        'total_interacoes'   => $total_interacoes,
        'total_pacientes'    => $total_pacientes,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar dashboard.', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno.', 'message' => $e->getMessage()]);
}
