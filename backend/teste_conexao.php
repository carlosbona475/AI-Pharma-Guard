<?php
header('Content-Type: application/json');

$drivers = PDO::getAvailableDrivers();
echo json_encode([
    'drivers_disponiveis' => $drivers,
    'mysql_habilitado'    => in_array('mysql', $drivers),
    'php_version'         => PHP_VERSION,
]);
