<?php
function getConnection() {
    $host = 'localhost';
    $dbname = 'u632052358_phramguard';
    $user = 'u632052358_pharmguard1';
    $password = 'Cn05091@';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco. Verifique a configuração.',
            'debug'   => $e->getMessage()
        ]);
        exit;
    }
}
