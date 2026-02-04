<?php
require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../../app/config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$customer_id = $data['customer_id'] ?? null;
$reading_value = $data['reading_value'] ?? null;

if (!$customer_id || !$reading_value) {
    http_response_code(400);
    echo json_encode(["error" => "Missing data"]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO readings (customer_id, reading_value, created_at)
    VALUES (?, ?, NOW())
");
$stmt->execute([$customer_id, $reading_value]);

echo json_encode(["message" => "Reading recorded successfully"]);
