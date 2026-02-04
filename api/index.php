<?php
require_once __DIR__ . '/../app/config/database.php';
header('Content-Type: application/json');

$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {
    case 'bills':
        require 'bills.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid endpoint']);
}
