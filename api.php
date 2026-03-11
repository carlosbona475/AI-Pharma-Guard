<?php
header('Content-Type: application/json');
// Evitar que erros PHP gerem HTML
ini_set('display_errors', '0');
ob_start();

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

function jsonResponse($data) {
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmacia";

$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    jsonResponse(['error' => 'Conexão falhou: ' . $conn->connect_error]);
}

// Função para cadastrar paciente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_paciente') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, idade, sexo, doencas, medicamentos_usados) VALUES (?, ?, ?, ?, ?);");
    $stmt->bind_param("sisss", $data['nome'], $data['idade'], $data['sexo'], $data['doencas'], $data['medicamentos']);
    $stmt->execute();
    jsonResponse(['message' => 'Paciente cadastrado com sucesso!']);
}

// Função para cadastrar medicamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_medicamento') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO medicamentos (nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES (?, ?, ?, ?, ?);");
    $stmt->bind_param("sssss", $data['nome'], $data['classe'], $data['dose'], $data['indicacao'], $data['contraindicacoes']);
    $stmt->execute();
    jsonResponse(['message' => 'Medicamento cadastrado com sucesso!']);
}

// Função para listar medicamentos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_medicamentos') {
    $result = $conn->query("SELECT * FROM medicamentos;");
    $medicamentos = ($result && $result->num_rows >= 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
    jsonResponse($medicamentos);
}

// Função para verificar interações medicamentosas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medicamentos = ($data && isset($data['medicamentos'])) ? $data['medicamentos'] : [];
    $interacoes = [];
    $ids = array_values(array_unique(array_filter(array_map(function ($m) {
        return is_array($m) ? (int)(isset($m['id']) ? $m['id'] : 0) : (int)$m;
    }, $medicamentos))));
    $stmt = $conn->prepare("SELECT * FROM interacoes WHERE medicamentoA = ? AND medicamentoB = ?");
    if ($stmt) {
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $a = $ids[$i];
                $b = $ids[$j];
                $stmt->bind_param("ii", $a, $b);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $interacoes[] = $res->fetch_assoc();
                }
                $stmt->bind_param("ii", $b, $a);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $interacoes[] = $res->fetch_assoc();
                }
            }
        }
    }
    jsonResponse($interacoes);
}

$conn->close();
http_response_code(400);
jsonResponse(['error' => 'Ação não reconhecida']);