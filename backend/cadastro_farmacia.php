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

     = trim(['nome'] ?? '');
    = trim(['email'] ?? '');
    = ['senha'] ?? '';
 = trim(['telefone'] ?? '');

if ( === '' ||  === '' ||  === '') {
    sendJson(['success' => false, 'message' => 'Campos obrigatórios não preenchidos.'], 400);
}

 = password_hash(, PASSWORD_DEFAULT);

 = ->prepare('INSERT INTO farmacias (nome, email, senha, telefone) VALUES (?, ?, ?, ?)');
if (!) {
    sendJson(['success' => false, 'message' => 'Erro ao preparar comando.'], 500);
}
->bind_param('ssss', , , , );

if (->execute()) {
    sendJson(['success' => true, 'message' => 'Farmácia cadastrada com sucesso']);
}

if (->errno === 1062) {
    sendJson(['success' => false, 'message' => 'E-mail já cadastrado.'], 400);
}

sendJson(['success' => false, 'message' => 'Erro ao cadastrar'], 500);
