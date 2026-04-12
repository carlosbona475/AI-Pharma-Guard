<?php
/**
 * Login JSON — define $_SESSION['usuario_id'] e $_SESSION['farmacia_id'].
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/session.php';
auth_session_start();

require_once __DIR__ . '/../db.php';

$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;
$data = is_array($data) ? $data : [];

$email = trim($data['email'] ?? '');
$senha = $data['senha'] ?? '';

if ($email === '' || $senha === '') {
    http_response_code(400);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'E-mail e senha são obrigatórios.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn = getConnection();
    $stmt = $conn->prepare('SELECT id, senha, ativo FROM farmacias WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Erro interno'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$row) {
    http_response_code(401);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Login inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!password_verify($senha, $row['senha'])) {
    http_response_code(401);
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Login inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($row['ativo'])) {
    http_response_code(403);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Sua conta ainda não foi aprovada. Aguarde o contato do administrador.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int) $row['id'];
$_SESSION['usuario_id']   = $id;
$_SESSION['farmacia_id']    = $id;

ob_end_clean();
echo json_encode([
    'success'      => true,
    'message'      => 'Login realizado com sucesso.',
    'farmacia_id'  => $id,
    'usuario_id'   => $id,
], JSON_UNESCAPED_UNICODE);
