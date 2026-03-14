<?php
header('Content-Type: application/json');
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
    $stmt = $pdo->prepare('INSERT INTO farmacias (nome, email, senha, telefone) VALUES (?, ?, ?, ?)');
    $stmt->execute([$nome, $email, $senhaHash, $telefone]);
    sendJson(['success' => true, 'message' => 'Farmácia cadastrada com sucesso']);
} catch (PDOException $e) {
    // PostgreSQL unique violation
    if ($e->getCode() === '23505') {
        sendJson(['success' => false, 'message' => 'E-mail já cadastrado.'], 400);
    }
    sendJson(['success' => false, 'message' => 'Erro ao cadastrar'], 500);
}
