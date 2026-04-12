<?php
/**
 * Conexão MySQL via PDO (Hostinger).
 * Charset utf8mb4 (compatível com utf8; emojis e acentuação).
 */
function getConnection() {
    $host = 'localhost';
    $dbname = 'u632052358_phramguard';
    $user = 'u632052358_pharmguard1';
    $password = 'Cn05091@';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco. Verifique a configuração.',
            'error'   => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Garante que a tabela pacientes existe com colunas usadas pelo cadastro (inclui cpf).
 */
function ensurePacientesTable(PDO $conn) {
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS pacientes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          farmacia_id INT NOT NULL DEFAULT 1,
          nome VARCHAR(100) NOT NULL,
          idade INT NOT NULL DEFAULT 0,
          cpf VARCHAR(20) DEFAULT NULL,
          sexo VARCHAR(20) DEFAULT \'masculino\',
          doencas TEXT,
          medicamentos_usados TEXT,
          alergias TEXT,
          historico_clinico TEXT,
          observacoes TEXT,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          KEY idx_pacientes_farmacia (farmacia_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    try {
        $conn->exec('ALTER TABLE pacientes ADD COLUMN cpf VARCHAR(20) DEFAULT NULL AFTER idade');
    } catch (PDOException $e) {
        if (!isset($e->errorInfo[1]) || (int) $e->errorInfo[1] !== 1060) {
            throw $e;
        }
    }
}
