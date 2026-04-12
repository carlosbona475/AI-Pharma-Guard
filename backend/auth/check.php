<?php
/**
 * Estado da sessão para o frontend (JSON).
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/session.php';
auth_session_start();

if (!empty($_SESSION['farmacia_id'])) {
    $fid = (int) $_SESSION['farmacia_id'];
    $uid = (int) ($_SESSION['usuario_id'] ?? $fid);
    echo json_encode([
        'authenticated' => true,
        'farmacia_id'   => $fid,
        'usuario_id'    => $uid,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'authenticated' => false,
    'farmacia_id'   => null,
    'usuario_id'    => null,
], JSON_UNESCAPED_UNICODE);
