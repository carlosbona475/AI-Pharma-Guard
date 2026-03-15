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

    $stmt = $conn->query("
        SELECT EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = 'medicamentos'
        ) AS ok
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($row['ok'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Tabela medicamentos não existe no banco.']);
        exit;
    }

    $stmt = $conn->query("
        SELECT id, nome,
               classe_farmacologica AS classe,
               classe_farmacologica,
               dose, indicacao, contraindicacoes
        FROM medicamentos
        ORDER BY nome
    ");
    $lista = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    echo json_encode($lista, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao listar medicamentos.', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno.', 'message' => $e->getMessage()]);
}
