<?php
/**
 * Conexão PDO com Supabase (PostgreSQL).
 * Inclua este arquivo nos scripts que precisam do banco: require_once __DIR__ . '/db.php';
 */
$host = "aws-1-us-east-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.zqptqkakqvqaxpxvwour";
$password = "SUA_SENHA_AQUI";

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
