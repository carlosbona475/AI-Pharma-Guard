<?php
/**
 * Conexão central PDO com Supabase (PostgreSQL).
 * Compatível com Render: use DATABASE_URL ou DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD.
 * Uso: require_once __DIR__ . '/db.php'; → $pdo disponível.
 */

function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl !== false && $databaseUrl !== '') {
        $url = parse_url($databaseUrl);
        $host = $url['host'] ?? 'localhost';
        $port = $url['port'] ?? '5432';
        $dbname = ltrim($url['path'] ?? '/postgres', '/');
        $user = $url['user'] ?? '';
        $password = $url['pass'] ?? '';
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    } else {
        $host = getenv('DB_HOST') ?: 'aws-1-us-east-1.pooler.supabase.com';
        $port = getenv('DB_PORT') ?: '6543';
        $dbname = getenv('DB_NAME') ?: 'postgres';
        $user = getenv('DB_USER') ?: 'postgres.zqptqkakqvqaxpxvwour';
        $password = getenv('DB_PASSWORD') ?: '';
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    }

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Conexão com o banco falhou.',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$pdo = getDb();
