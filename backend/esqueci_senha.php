<?php
/**
 * Recuperação de senha — envia e-mail com link para redefinir.
 * POST { "email": "farmacia@email.com" }
 * Não revela se o e-mail existe (sempre retorna mesma mensagem de sucesso).
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
$email = isset($data['email']) ? trim((string) $data['email']) : '';

if ($email === '') {
    sendJson(['success' => false, 'message' => 'E-mail é obrigatório.'], 400);
}

require_once __DIR__ . '/config/database.php';
$conn = getConnection();

// Base da URL do sistema (para o link no e-mail). Ajuste em produção.
$baseUrl = getenv('BASE_URL') ?: (isset($_ENV['BASE_URL']) ? $_ENV['BASE_URL'] : 'http://localhost:8000');

try {
    $stmt = $conn->prepare('SELECT id FROM farmacias WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    sendJson(['success' => false, 'message' => 'Erro ao processar solicitação.'], 500);
}

// Sempre retornar a mesma mensagem (não revelar se o e-mail existe)
$resposta = ['success' => true, 'message' => 'Se o e-mail existir, você receberá as instruções.'];

if (!$row) {
    sendJson($resposta);
}

$farmaciaId = (int) $row['id'];
$token = bin2hex(random_bytes(32));

try {
    $stmt = $conn->prepare('INSERT INTO password_resets (farmacia_id, token, expira_em) VALUES (?, ?, NOW() + INTERVAL \'1 hour\')');
    $stmt->execute([$farmaciaId, $token]);
} catch (PDOException $e) {
    sendJson($resposta);
}

$link = rtrim($baseUrl, '/') . '/frontend/pages/nova_senha.html?token=' . urlencode($token);

$assunto = 'AI Pharma Guard - Redefinição de senha';
$mensagem = '
<html><head><meta charset="UTF-8"></head><body style="font-family: sans-serif;">
<p>Você solicitou a redefinição de senha.</p>
<p>Clique no link abaixo para definir uma nova senha (válido por 1 hora):</p>
<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Redefinir senha</a></p>
<p>Se não foi você, ignore este e-mail.</p>
<p>— AI Pharma Guard</p>
</body></html>';

$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: AI Pharma Guard <noreply@localhost>',
];

@mail($email, $assunto, $mensagem, implode("\r\n", $headers));

sendJson($resposta);
