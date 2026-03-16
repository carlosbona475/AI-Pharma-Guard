<?php
/**
 * Conexão PDO PostgreSQL (Supabase).
 * Carrega .env via config.php; usa getenv()/$_ENV para credenciais.
 * Variáveis: SUPABASE_HOST, SUPABASE_DB, SUPABASE_USER, SUPABASE_PASS, SUPABASE_PORT, SUPABASE_SSLMODE.
 * Fallback para DB_* se SUPABASE_* não estiver definido (compatibilidade).
 */
require_once __DIR__ . '/../config.php';

function getConnection() {
    $host = getenv('SUPABASE_HOST') ?: ($_ENV['SUPABASE_HOST'] ?? getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ''));
    $dbname = getenv('SUPABASE_DB') ?: ($_ENV['SUPABASE_DB'] ?? getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'postgres'));
    $user = getenv('SUPABASE_USER') ?: ($_ENV['SUPABASE_USER'] ?? getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ''));
    $pass = getenv('SUPABASE_PASS') ?: ($_ENV['SUPABASE_PASS'] ?? getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ''));
    $port = getenv('SUPABASE_PORT') ?: ($_ENV['SUPABASE_PORT'] ?? getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432'));
    $sslmode = getenv('SUPABASE_SSLMODE') ?: ($_ENV['SUPABASE_SSLMODE'] ?? getenv('DB_SSLMODE') ?: ($_ENV['DB_SSLMODE'] ?? 'require'));

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
