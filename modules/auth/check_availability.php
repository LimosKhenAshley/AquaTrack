<?php
require_once __DIR__ . '/../../app/config/database.php';

$response = ['status' => 'ok'];

if (isset($_GET['email'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([trim($_GET['email'])]);

    if ($stmt->fetchColumn()) {
        $response = [
            'status' => 'error',
            'message' => 'Email is already registered'
        ];
    }
}

if (isset($_GET['phone'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([trim($_GET['phone'])]);

    if ($stmt->fetchColumn()) {
        $response = [
            'status' => 'error',
            'message' => 'Phone number is already registered'
        ];
    }
}

if (isset($_GET['meter_number'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE meter_number = ?");
    $stmt->execute([trim($_GET['meter_number'])]);

    if ($stmt->fetchColumn()) {
        $response = [
            'status' => 'error',
            'message' => 'Meter number already exists'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);