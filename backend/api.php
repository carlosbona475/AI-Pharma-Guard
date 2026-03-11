<?php
// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmacia";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Conexão falhou: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8");

// ---- ESTATÍSTICAS (Dashboard) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'estatisticas') {
    $pacientes = $conn->query("SELECT COUNT(*) as total FROM pacientes")->fetch_assoc()['total'];
    $medicamentos = $conn->query("SELECT COUNT(*) as total FROM medicamentos")->fetch_assoc()['total'];
    $interacoes = $conn->query("SELECT COUNT(*) as total FROM interacoes")->fetch_assoc()['total'];
    echo json_encode([
        'pacientes' => (int) $pacientes,
        'medicamentos' => (int) $medicamentos,
        'interacoes_cadastradas' => (int) $interacoes
    ]);
    $conn->close();
    exit;
}

// ---- LISTAR PACIENTES ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_pacientes') {
    $result = $conn->query("SELECT * FROM pacientes ORDER BY nome");
    $pacientes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($pacientes);
    $conn->close();
    exit;
}

// ---- CADASTRAR PACIENTE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_paciente') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['nome'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        $conn->close();
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, idade, sexo, doencas, medicamentos_usados) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss",
        $data['nome'],
        $data['idade'] ?? 0,
        $data['sexo'] ?? 'masculino',
        $data['doencas'] ?? '',
        $data['medicamentos'] ?? ''
    );
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Paciente cadastrado com sucesso!', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $stmt->error]);
    }
    $conn->close();
    exit;
}

// ---- LISTAR MEDICAMENTOS ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_medicamentos') {
    $result = $conn->query("SELECT * FROM medicamentos ORDER BY nome");
    $medicamentos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($medicamentos);
    $conn->close();
    exit;
}

// ---- CADASTRAR MEDICAMENTO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_medicamento') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['nome'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        $conn->close();
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO medicamentos (nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss",
        $data['nome'],
        $data['classe'] ?? '',
        $data['dose'] ?? '',
        $data['indicacao'] ?? '',
        $data['contraindicacoes'] ?? ''
    );
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Medicamento cadastrado com sucesso!', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $stmt->error]);
    }
    $conn->close();
    exit;
}

// ---- LISTAR INTERAÇÕES (todas cadastradas) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_interacoes') {
    $result = $conn->query("
        SELECT i.*, ma.nome AS nomeA, mb.nome AS nomeB
        FROM interacoes i
        JOIN medicamentos ma ON ma.id = i.medicamentoA
        JOIN medicamentos mb ON mb.id = i.medicamentoB
        ORDER BY i.nivel_risco DESC, ma.nome
    ");
    $lista = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode($lista);
    $conn->close();
    exit;
}

// ---- CADASTRAR INTERAÇÃO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_interacao') {
    $data = json_decode(file_get_contents('php://input'), true);
    $medA = (int) ($data['medicamentoA'] ?? 0);
    $medB = (int) ($data['medicamentoB'] ?? 0);
    if ($medA <= 0 || $medB <= 0 || $medA === $medB) {
        http_response_code(400);
        echo json_encode(['error' => 'Selecione dois medicamentos diferentes.']);
        $conn->close();
        exit;
    }
    $nivel = in_array($data['nivel_risco'] ?? '', ['baixo', 'medio', 'alto']) ? $data['nivel_risco'] : 'medio';
    $stmt = $conn->prepare("INSERT INTO interacoes (medicamentoA, medicamentoB, tipo_interacao, nivel_risco, recomendacao) VALUES (?, ?, ?, ?, ?)");
    $tipo = $data['tipo_interacao'] ?? '';
    $recomendacao = $data['recomendacao'] ?? '';
    $stmt->bind_param("iisss", $medA, $medB, $tipo, $nivel, $recomendacao);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Interação cadastrada com sucesso!', 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $stmt->error]);
    }
    $conn->close();
    exit;
}

// ---- VERIFICAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes') {
    $data = json_decode(file_get_contents('php://input'), true);
    $medicamentos = $data['medicamentos'] ?? [];
    $ids = array_map(function ($m) {
        return is_array($m) ? (int) ($m['id'] ?? 0) : (int) $m;
    }, $medicamentos);
    $ids = array_unique(array_filter($ids));

    $interacoes = [];
    $stmt = $conn->prepare("
        SELECT i.*, ma.nome AS nomeA, mb.nome AS nomeB
        FROM interacoes i
        JOIN medicamentos ma ON ma.id = i.medicamentoA
        JOIN medicamentos mb ON mb.id = i.medicamentoB
        WHERE (i.medicamentoA = ? AND i.medicamentoB = ?) OR (i.medicamentoA = ? AND i.medicamentoB = ?)
    ");

    for ($i = 0; $i < count($ids); $i++) {
        for ($j = $i + 1; $j < count($ids); $j++) {
            $a = $ids[$i];
            $b = $ids[$j];
            $stmt->bind_param("iiii", $a, $b, $b, $a);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $interacoes[] = $row;
            }
        }
    }

    echo json_encode($interacoes);
    $conn->close();
    exit;
}

$conn->close();
http_response_code(400);
echo json_encode(['error' => 'Ação não reconhecida']);
?>
