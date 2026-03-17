<?php
function getConnection() {
    // Tenta connection string completa primeiro (Render/Supabase)
    $dsn_url = getenv('DATABASE_URL');
    
    if ($dsn_url) {
        $params = parse_url($dsn_url);
        $host     = $params['host'];
        $port     = $params['port'] ?? 5432;
        $dbname   = ltrim($params['path'], '/');
        $user     = $params['user'];
        $password = $params['pass'];
    } else {
        $host     = getenv('DB_HOST')     ?: 'localhost';
        $dbname   = getenv('DB_NAME')     ?: 'farmacia';
        $user     = getenv('DB_USER')     ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: '';
        $port     = getenv('DB_PORT')     ?: '5432';
    }

    try {
        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$dbname}",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_SSL_CA             => null,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao conectar ao banco. Verifique as variáveis de ambiente.',
            'debug'   => $e->getMessage()
        ]);
        exit;
    }
}
