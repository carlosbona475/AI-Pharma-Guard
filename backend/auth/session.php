<?php
/**
 * Sessão e proteção de rotas — AI Pharma Guard
 */

if (!function_exists('auth_session_start')) {
    function auth_session_start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

/**
 * Exige login para APIs JSON (401 + JSON).
 */
function requireAuthJson() {
    auth_session_start();
    if (empty($_SESSION['farmacia_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success'         => false,
            'authenticated'   => false,
            'message'         => 'Sessão expirada ou não autenticado.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Exige login para páginas HTML (redireciona ao login).
 *
 * @param string $loginPath caminho a partir da raiz do site (ex.: /frontend/pages/login.html)
 */
function requireAuthRedirect($loginPath = '/frontend/pages/login.html') {
    auth_session_start();
    if (empty($_SESSION['farmacia_id'])) {
        header('Location: ' . $loginPath);
        exit;
    }
}
