<?php
header('Content-Type: application/json');
// Garantir que apenas JSON seja enviado (evitar HTML de erros ou BOM)
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

function sendJson($data) {
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $code = 500) {
    http_response_code($code);
    sendJson(['error' => $message]);
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmacia";

$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    sendError('Conexão falhou: ' . $conn->connect_error, 500);
}

$conn->set_charset("utf8");

// ---- ESTATÍSTICAS (Dashboard) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'estatisticas') {
    $q1 = $conn->query("SELECT COUNT(*) as total FROM pacientes");
    $q2 = $conn->query("SELECT COUNT(*) as total FROM medicamentos");
    $q3 = $conn->query("SELECT COUNT(*) as total FROM interacoes");
    if (!$q1 || !$q2 || !$q3) {
        sendError('Erro ao consultar estatísticas. Verifique se o banco e as tabelas existem.', 500);
    }
    $pacientes = (int) $q1->fetch_assoc()['total'];
    $medicamentos = (int) $q2->fetch_assoc()['total'];
    $interacoes = (int) $q3->fetch_assoc()['total'];
    sendJson([
        'pacientes' => $pacientes,
        'medicamentos' => $medicamentos,
        'interacoes_cadastradas' => $interacoes
    ]);
}

// ---- LISTAR PACIENTES ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_pacientes') {
    $result = $conn->query("SELECT * FROM pacientes ORDER BY nome");
    $pacientes = ($result && $result->num_rows >= 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    sendJson($pacientes);
}

// ---- CADASTRAR PACIENTE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_paciente') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || empty($data['nome'])) {
        sendJson(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, idade, sexo, doencas, medicamentos_usados, alergias, historico_clinico, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Erro ao preparar comando']);
        exit;
    }
    $alergias = isset($data['alergias']) ? $data['alergias'] : '';
    $historico = isset($data['historico_clinico']) ? $data['historico_clinico'] : '';
    $observacoes = isset($data['observacoes']) ? $data['observacoes'] : '';
    $stmt->bind_param("sissssss",
        $data['nome'],
        isset($data['idade']) ? (int)$data['idade'] : 0,
        isset($data['sexo']) ? $data['sexo'] : 'masculino',
        isset($data['doencas']) ? $data['doencas'] : '',
        isset($data['medicamentos']) ? $data['medicamentos'] : '',
        $alergias,
        $historico,
        $observacoes
    );
    if ($stmt->execute()) {
        sendJson(['success' => true, 'id' => (int)$conn->insert_id]);
        exit;
    }
    sendJson(['success' => false, 'message' => $stmt->error ?: 'Erro ao cadastrar']);
    exit;
}

// ---- LISTAR MEDICAMENTOS ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_medicamentos') {
    $result = $conn->query("SELECT * FROM medicamentos ORDER BY nome");
    $medicamentos = ($result && $result->num_rows >= 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    sendJson($medicamentos);
}

// ---- CADASTRAR MEDICAMENTO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_medicamento') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || empty($data['nome'])) {
        sendError('Dados inválidos', 400);
    }
    $stmt = $conn->prepare("INSERT INTO medicamentos (nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        sendError('Erro ao preparar comando', 500);
    }
    $stmt->bind_param("sssss",
        $data['nome'],
        isset($data['classe']) ? $data['classe'] : '',
        isset($data['dose']) ? $data['dose'] : '',
        isset($data['indicacao']) ? $data['indicacao'] : '',
        isset($data['contraindicacoes']) ? $data['contraindicacoes'] : ''
    );
    if ($stmt->execute()) {
        sendJson(['message' => 'Medicamento cadastrado com sucesso!', 'id' => (int)$conn->insert_id]);
    }
    sendError($stmt->error ?: 'Erro ao cadastrar', 500);
}

// ---- LISTAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_interacoes') {
    $result = $conn->query("
        SELECT i.*, ma.nome AS nomeA, mb.nome AS nomeB
        FROM interacoes i
        JOIN medicamentos ma ON ma.id = i.medicamentoA
        JOIN medicamentos mb ON mb.id = i.medicamentoB
        ORDER BY i.nivel_risco DESC, ma.nome
    ");
    $lista = ($result && $result->num_rows >= 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    sendJson($lista);
}

// ---- CADASTRAR INTERAÇÃO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_interacao') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medA = $data ? (int)(isset($data['medicamentoA']) ? $data['medicamentoA'] : 0) : 0;
    $medB = $data ? (int)(isset($data['medicamentoB']) ? $data['medicamentoB'] : 0) : 0;
    if ($medA <= 0 || $medB <= 0 || $medA === $medB) {
        sendError('Selecione dois medicamentos diferentes.', 400);
    }
    $nivel = isset($data['nivel_risco']) && in_array($data['nivel_risco'], ['baixo', 'medio', 'alto']) ? $data['nivel_risco'] : 'medio';
    $tipo = isset($data['tipo_interacao']) ? $data['tipo_interacao'] : '';
    $recomendacao = isset($data['recomendacao']) ? $data['recomendacao'] : '';
    $stmt = $conn->prepare("INSERT INTO interacoes (medicamentoA, medicamentoB, tipo_interacao, nivel_risco, recomendacao) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        sendError('Erro ao preparar comando', 500);
    }
    $stmt->bind_param("iisss", $medA, $medB, $tipo, $nivel, $recomendacao);
    if ($stmt->execute()) {
        sendJson(['message' => 'Interação cadastrada com sucesso!', 'id' => (int)$conn->insert_id]);
    }
    sendError($stmt->error ?: 'Erro ao cadastrar', 500);
}

// ---- VERIFICAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medicamentos = ($data && isset($data['medicamentos'])) ? $data['medicamentos'] : [];
    $ids = array_values(array_unique(array_filter(array_map(function ($m) {
        return is_array($m) ? (int)(isset($m['id']) ? $m['id'] : 0) : (int)$m;
    }, $medicamentos))));

    $interacoes = [];
    $stmt = $conn->prepare("
        SELECT i.*, ma.nome AS nomeA, mb.nome AS nomeB
        FROM interacoes i
        JOIN medicamentos ma ON ma.id = i.medicamentoA
        JOIN medicamentos mb ON mb.id = i.medicamentoB
        WHERE (i.medicamentoA = ? AND i.medicamentoB = ?) OR (i.medicamentoA = ? AND i.medicamentoB = ?)
    ");
    if ($stmt) {
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $a = $ids[$i];
                $b = $ids[$j];
                $stmt->bind_param("iiii", $a, $b, $b, $a);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($row = $res->fetch_assoc()) {
                        $interacoes[] = $row;
                    }
                }
            }
        }
    }
    sendJson($interacoes);
}

$conn->close();
sendError('Ação não reconhecida', 400);
