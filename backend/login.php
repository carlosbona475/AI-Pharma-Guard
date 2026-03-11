<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
ob_start();
session_start();

function sendJson(,  = 200) {
    http_response_code();
    ob_end_clean();
    echo json_encode(, JSON_UNESCAPED_UNICODE);
    exit;
}

 = 'localhost';
   = 'root';
   = '';
     = 'farmacia';

 = @new mysqli(, , , );
if (->connect_error) {
    sendJson(['success' => false, 'message' => 'Erro de conexão com o banco.'], 500);
}
->set_charset('utf8');

  = file_get_contents('php://input');
 =  ? json_decode(, true) : null;

 = trim(['email'] ?? '');
 = ['senha'] ?? '';

if ( === '' ||  === '') {
    sendJson(['success' => false, 'message' => 'E-mail e senha são obrigatórios.'], 400);
}

 = ->prepare('SELECT id, senha FROM farmacias WHERE email = ?');
if (!) {
    sendJson(['success' => false, 'message' => 'Erro ao preparar comando.'], 500);
}
->bind_param('s', );
->execute();
 = ->get_result();

if (! || ->num_rows === 0) {
    sendJson(['success' => false, 'message' => 'Login inválido.'], 401);
}

 = ->fetch_assoc();
if (!password_verify(, ['senha'])) {
    sendJson(['success' => false, 'message' => 'Login inválido.'], 401);
}

['farmacia_id'] = (int)['id'];

sendJson(['success' => true]);
