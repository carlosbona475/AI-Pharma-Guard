<?php
// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmacia";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função para cadastrar paciente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_paciente') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, idade, sexo, doencas, medicamentos_usados) VALUES (?, ?, ?, ?, ?);");
    $stmt->bind_param("sisss", $data['nome'], $data['idade'], $data['sexo'], $data['doencas'], $data['medicamentos']);
    $stmt->execute();
    echo json_encode(['message' => 'Paciente cadastrado com sucesso!']);
}

// Função para cadastrar medicamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_medicamento') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("INSERT INTO medicamentos (nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES (?, ?, ?, ?, ?);");
    $stmt->bind_param("sssss", $data['nome'], $data['classe'], $data['dose'], $data['indicacao'], $data['contraindicacoes']);
    $stmt->execute();
    echo json_encode(['message' => 'Medicamento cadastrado com sucesso!']);
}

// Função para listar medicamentos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_medicamentos') {
    $result = $conn->query("SELECT * FROM medicamentos;");
    $medicamentos = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($medicamentos);
}

// Função para verificar interações medicamentosas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Lógica para verificar interações:
    $interacoes = [];
    // Recuperar IDs dos medicamentos
    $medicamentIds = array_column($data['medicamentos'], 'id');
    foreach ($medicamentIds as $medA) {
        foreach ($medicamentIds as $medB) {
            if ($medA != $medB) {
                $stmt = $conn->prepare("SELECT * FROM interacoes WHERE medicamentoA = ? AND medicamentoB = ?");
                $stmt->bind_param("ii", $medA, $medB);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $interacoes[] = $result->fetch_all(MYSQLI_ASSOC);
                }
            }
        }
    }
    $interacoes = [];
    foreach ($data['medicamentos'] as $med1) {
        foreach ($data['medicamentos'] as $med2) {
            if ($med1 !== $med2) {
                $stmt = $conn->prepare("SELECT * FROM interacoes WHERE medicamentoA = ? AND medicamentoB = ?;");
                $stmt->bind_param("ii", $med1, $med2);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $interacoes[] = $result->fetch_assoc();
                }
            }
        }
    }
    echo json_encode($interacoes);
}

$conn->close();
?>