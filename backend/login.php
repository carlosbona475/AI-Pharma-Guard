<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
ob_start();
session_start();

function sendJson($data, $code = 200) {
    http_response_code($code);
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/db.php';
} catch (Throwable $e) {
    sendJson(['success' => false, 'message' => 'Erro de conexão.'], 500);
}

$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;
$data = is_array($data) ? $data : [];

$email = trim($data['email'] ?? '');
$senha = $data['senha'] ?? '';

if ($email === '' || $senha === '') {
    sendJson(['success' => false, 'message' => 'E-mail e senha são obrigatórios.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT id, senha FROM farmacias WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendJson(['success' => false, 'message' => 'Erro ao consultar usuário.'], 500);
}

if (!$row) {
    sendJson(['success' => false, 'message' => 'Login inválido.'], 401);
}

if (!password_verify($senha, $row['senha'])) {
    sendJson(['success' => false, 'message' => 'Login inválido.'], 401);
}

$_SESSION['farmacia_id'] = (int) $row['id'];

sendJson(['success' => true]);
