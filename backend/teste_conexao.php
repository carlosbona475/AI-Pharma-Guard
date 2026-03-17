<?php
header('Content-Type: application/json');

// Testa se o driver pgsql está disponível
$drivers = PDO::getAvailableDrivers();
echo json_encode([
    'drivers_disponiveis' => $drivers,
    'pgsql_habilitado' => in_array('pgsql', $drivers),
    'php_version' => PHP_VERSION,
    'database_url' => getenv('DATABASE_URL') ? 'definida' : 'NÃO definida',
]);

