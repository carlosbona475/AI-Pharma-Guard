<?php
/**
 * Painel admin simples — controle de aprovação de farmácias.
 * Protegido por admin_key (temporário). Respostas sempre JSON.
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

// Senha do admin (troque em produção por variável de ambiente)
define('ADMIN_KEY', 'Ph4rm4Gu4rd#Adm!n2025');

$adminKey = isset($_GET['admin_key']) ? (string) $_GET['admin_key'] : '';

if ($adminKey === '' || !hash_equals(ADMIN_KEY, $adminKey)) {
    sendJson(['success' => false, 'message' => 'Acesso negado.'], 403);
}

require_once __DIR__ . '/config/database.php';
$conn = getConnection();

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

// ---- Listar farmácias pendentes (ativo = false) ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'listar_pendentes') {
    try {
        $stmt = $conn->prepare('SELECT id, nome, email, telefone, ativo, aprovado_em FROM farmacias WHERE ativo = false OR ativo IS NULL ORDER BY id');
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJson(['success' => true, 'farmacias' => $lista, 'total' => count($lista)]);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Erro ao listar: ' . $e->getMessage()], 500);
    }
}

// ---- Aprovar farmácia ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'aprovar') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $farmaciaId = isset($data['farmacia_id']) ? (int) $data['farmacia_id'] : 0;

    if ($farmaciaId <= 0) {
        sendJson(['success' => false, 'message' => 'farmacia_id inválido.'], 400);
    }

    try {
        $stmt = $conn->prepare('UPDATE farmacias SET ativo = true, aprovado_em = NOW() WHERE id = ?');
        $stmt->execute([$farmaciaId]);
        if ($stmt->rowCount() === 0) {
            sendJson(['success' => false, 'message' => 'Farmácia não encontrada.'], 404);
        }
        sendJson(['success' => true, 'message' => 'Farmácia aprovada.']);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Erro ao aprovar: ' . $e->getMessage()], 500);
    }
}

// ---- Rejeitar (excluir) farmácia ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'rejeitar') {
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : null;
    $farmaciaId = isset($data['farmacia_id']) ? (int) $data['farmacia_id'] : 0;

    if ($farmaciaId <= 0) {
        sendJson(['success' => false, 'message' => 'farmacia_id inválido.'], 400);
    }

    try {
        $stmt = $conn->prepare('DELETE FROM farmacias WHERE id = ?');
        $stmt->execute([$farmaciaId]);
        if ($stmt->rowCount() === 0) {
            sendJson(['success' => false, 'message' => 'Farmácia não encontrada.'], 404);
        }
        sendJson(['success' => true, 'message' => 'Farmácia removida.']);
    } catch (PDOException $e) {
        sendJson(['success' => false, 'message' => 'Erro ao rejeitar: ' . $e->getMessage()], 500);
    }
}

sendJson(['success' => false, 'message' => 'Ação não reconhecida. Use action=listar_pendentes, aprovar ou rejeitar.'], 400);
