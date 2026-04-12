<?php
/**
 * Redefinição de senha — valida token e atualiza senha na tabela farmacias.
 * POST { "token": "abc123", "nova_senha": "minhasenha" }
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
ob_start();

function sendJson($data, $code = 200) {
    http_response_code($code);
    ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Método não permitido.'], 405);
}

$raw = file_get_contents('php://input');
$data = $raw ? json_decode($raw, true) : null;
$token = isset($data['token']) ? trim((string) $data['token']) : '';
$novaSenha = isset($data['nova_senha']) ? (string) $data['nova_senha'] : '';

if ($token === '') {
    sendJson(['success' => false, 'message' => 'Token é obrigatório.'], 400);
}

if (strlen($novaSenha) < 6) {
    sendJson(['success' => false, 'message' => 'A nova senha deve ter no mínimo 6 caracteres.'], 400);
}

require_once __DIR__ . '/config/database.php';
$conn = getConnection();

try {
    $stmt = $conn->prepare('SELECT id, farmacia_id, usado, expira_em FROM password_resets WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendJson(['success' => false, 'message' => 'Erro ao validar token.'], 500);
}

if (!$row) {
    sendJson(['success' => false, 'message' => 'Token inválido ou expirado.'], 400);
}

if (!empty($row['usado'])) {
    sendJson(['success' => false, 'message' => 'Este link já foi utilizado.'], 400);
}

$expiraEm = strtotime($row['expira_em']);
if ($expiraEm === false || time() > $expiraEm) {
    sendJson(['success' => false, 'message' => 'Token expirado. Solicite uma nova redefinição de senha.'], 400);
}

$farmaciaId = (int) $row['farmacia_id'];
$senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare('UPDATE farmacias SET senha = ? WHERE id = ?');
    $stmt->execute([$senhaHash, $farmaciaId]);

    $stmt = $conn->prepare('UPDATE password_resets SET usado = 1 WHERE id = ?');
    $stmt->execute([(int) $row['id']]);
} catch (PDOException $e) {
    sendJson(['success' => false, 'message' => 'Erro ao atualizar senha.'], 500);
}

sendJson(['success' => true, 'message' => 'Senha alterada com sucesso. Redirecionando para o login...']);
