<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ob_start();
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    sendJson(['success' => false, 'error' => $message]);
    exit;
}

require_once __DIR__ . '/db.php';

// SaaS multi-farmácia: usar farmácia da sessão ou padrão 1 (evita NOT NULL no INSERT)
$farmacia_id = isset($_SESSION['farmacia_id']) ? (int) $_SESSION['farmacia_id'] : 1;

// ---- ESTATÍSTICAS (Dashboard) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'estatisticas') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pacientes WHERE farmacia_id = ?");
        $stmt->execute([$farmacia_id]);
        $pacientes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM medicamentos WHERE farmacia_id = ?");
        $stmt->execute([$farmacia_id]);
        $medicamentos = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM interacoes WHERE farmacia_id = ?");
        $stmt->execute([$farmacia_id]);
        $interacoes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        sendJson([
            'pacientes' => $pacientes,
            'medicamentos' => $medicamentos,
            'interacoes_cadastradas' => $interacoes
        ]);
    } catch (PDOException $e) {
        sendError('Erro ao consultar estatísticas. Verifique se o banco e as tabelas existem.', 500);
    }
}

// ---- LISTAR PACIENTES ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_pacientes') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE farmacia_id = ? ORDER BY nome");
        $stmt->execute([$farmacia_id]);
        $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJson($pacientes);
    } catch (PDOException $e) {
        sendError('Erro ao listar pacientes.', 500);
    }
}

// ---- CADASTRAR PACIENTE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_paciente') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || empty(trim($data['nome'] ?? ''))) {
        sendJson(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO pacientes (farmacia_id, nome, idade, sexo, doencas, medicamentos_usados, alergias, historico_clinico, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $farmacia_id,
            trim($data['nome']),
            isset($data['idade']) ? (int) $data['idade'] : 0,
            isset($data['sexo']) ? $data['sexo'] : 'masculino',
            isset($data['doencas']) ? $data['doencas'] : '',
            isset($data['medicamentos']) ? $data['medicamentos'] : '',
            isset($data['alergias']) ? $data['alergias'] : '',
            isset($data['historico_clinico']) ? $data['historico_clinico'] : '',
            isset($data['observacoes']) ? $data['observacoes'] : '',
        ]);
        sendJson(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
        exit;
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ---- LISTAR MEDICAMENTOS ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_medicamentos') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM medicamentos WHERE farmacia_id = ? ORDER BY nome");
        $stmt->execute([$farmacia_id]);
        $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJson($medicamentos);
    } catch (PDOException $e) {
        sendError('Erro ao listar medicamentos.', 500);
    }
}

// ---- CADASTRAR MEDICAMENTO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_medicamento') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || empty(trim($data['nome'] ?? ''))) {
        sendError('Dados inválidos', 400);
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO medicamentos (farmacia_id, nome, classe_farmacologica, dose, indicacao, contraindicacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $farmacia_id,
            trim($data['nome']),
            isset($data['classe']) ? $data['classe'] : '',
            isset($data['dose']) ? $data['dose'] : '',
            isset($data['indicacao']) ? $data['indicacao'] : '',
            isset($data['contraindicacoes']) ? $data['contraindicacoes'] : ''
        ]);
        sendJson(['message' => 'Medicamento cadastrado com sucesso!', 'id' => (int) $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        sendError($e->getMessage(), 500);
    }
}

// ---- LISTAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'listar_interacoes') {
    try {
        $stmt = $pdo->prepare("
            SELECT i.id, i.farmacia_id, i.medicamento_a AS \"medicamentoA\", i.medicamento_b AS \"medicamentoB\",
                   i.tipo_interacao, i.nivel_risco, i.recomendacao,
                   ma.nome AS \"nomeA\", mb.nome AS \"nomeB\"
            FROM interacoes i
            JOIN medicamentos ma ON ma.id = i.medicamento_a
            JOIN medicamentos mb ON mb.id = i.medicamento_b
            WHERE i.farmacia_id = ?
            ORDER BY i.nivel_risco DESC, ma.nome
        ");
        $stmt->execute([$farmacia_id]);
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJson($lista);
    } catch (PDOException $e) {
        sendError('Erro ao listar interações.', 500);
    }
}

// ---- CADASTRAR INTERAÇÃO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'cadastrar_interacao') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medA = $data ? (int) (isset($data['medicamentoA']) ? $data['medicamentoA'] : 0) : 0;
    $medB = $data ? (int) (isset($data['medicamentoB']) ? $data['medicamentoB'] : 0) : 0;
    if ($medA <= 0 || $medB <= 0 || $medA === $medB) {
        sendError('Selecione dois medicamentos diferentes.', 400);
    }
    $nivel = isset($data['nivel_risco']) && in_array($data['nivel_risco'], ['baixo', 'medio', 'alto']) ? $data['nivel_risco'] : 'medio';
    $tipo = isset($data['tipo_interacao']) ? $data['tipo_interacao'] : '';
    $recomendacao = isset($data['recomendacao']) ? $data['recomendacao'] : '';
    try {
        $stmt = $pdo->prepare("INSERT INTO interacoes (farmacia_id, medicamento_a, medicamento_b, tipo_interacao, nivel_risco, recomendacao) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$farmacia_id, $medA, $medB, $tipo, $nivel, $recomendacao]);
        sendJson(['message' => 'Interação cadastrada com sucesso!', 'id' => (int) $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        sendError($e->getMessage(), 500);
    }
}

// ---- VERIFICAR INTERAÇÕES ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verificar_interacoes') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $medicamentos = ($data && isset($data['medicamentos'])) ? $data['medicamentos'] : [];
    $ids = array_values(array_unique(array_filter(array_map(function ($m) {
        return is_array($m) ? (int) (isset($m['id']) ? $m['id'] : 0) : (int) $m;
    }, $medicamentos))));

    $interacoes = [];
    try {
        $stmt = $pdo->prepare("
            SELECT i.id, i.farmacia_id, i.medicamento_a AS \"medicamentoA\", i.medicamento_b AS \"medicamentoB\",
                   i.tipo_interacao, i.nivel_risco, i.recomendacao,
                   ma.nome AS \"nomeA\", mb.nome AS \"nomeB\"
            FROM interacoes i
            JOIN medicamentos ma ON ma.id = i.medicamento_a
            JOIN medicamentos mb ON mb.id = i.medicamento_b
            WHERE i.farmacia_id = ? AND ((i.medicamento_a = ? AND i.medicamento_b = ?) OR (i.medicamento_a = ? AND i.medicamento_b = ?))
        ");
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $a = $ids[$i];
                $b = $ids[$j];
                $stmt->execute([$farmacia_id, $a, $b, $b, $a]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $interacoes[] = $row;
                }
            }
        }
    } catch (PDOException $e) {
        sendError('Erro ao verificar interações.', 500);
    }
    sendJson($interacoes);
}

sendError('Ação não reconhecida', 400);
