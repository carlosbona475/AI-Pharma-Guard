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
            WHERE table_schema = 'public' AND table_name = 'interacoes'
        ) AS ok
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (empty($row['ok'])) {
        http_response_code(500);
        echo json_encode(['error' => 'Tabela interacoes não existe no banco.']);
        exit;
    }

    $stmt = $conn->query("
        SELECT i.medicamento_a,
               i.medicamento_b,
               i.nivel_risco AS gravidade,
               i.nivel_risco,
               i.tipo_interacao,
               i.recomendacao,
               ma.nome AS \"nomeA\",
               mb.nome AS \"nomeB\"
        FROM interacoes i
        JOIN medicamentos ma ON ma.id = i.medicamento_a
        JOIN medicamentos mb ON mb.id = i.medicamento_b
        ORDER BY i.nivel_risco DESC, ma.nome
    ");
    $lista = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    echo json_encode($lista, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao listar interações.', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno.', 'message' => $e->getMessage()]);
}
