<?php
/**
 * Conexão PDO PostgreSQL (Supabase).
 * Variáveis de ambiente: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSLMODE.
 * Compatível com Render.
 */

function getConnection() {
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '6543');
    $dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'postgres');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? '');
    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    $sslmode = getenv('DB_SSLMODE') ?: ($_ENV['DB_SSLMODE'] ?? 'require');

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}
