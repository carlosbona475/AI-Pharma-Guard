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

require_once __DIR__ . '/db.php';
$conn = getConnection();

$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;

$nome = trim($data['nome'] ?? '');
$email = trim($data['email'] ?? '');
$senha = $data['senha'] ?? '';
$telefone = trim($data['telefone'] ?? '');

if ($nome === '' || $email === '' || $senha === '') {
    sendJson(['success' => false, 'message' => 'Campos obrigatórios não preenchidos.'], 400);
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare('INSERT INTO farmacias (nome, email, senha, telefone, ativo) VALUES (?, ?, ?, ?, 0)');
    $stmt->execute([$nome, $email, $senhaHash, $telefone]);
    sendJson(['success' => true, 'message' => 'Farmácia cadastrada com sucesso']);
} catch (PDOException $e) {
    // MySQL duplicate key (email único)
    if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
        sendJson(['success' => false, 'message' => 'E-mail já cadastrado.'], 400);
    }
    // Erro genérico de banco
    sendJson(['success' => false, 'message' => 'Erro interno'], 500);
}
