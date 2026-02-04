<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$user_id = verifyToken($pdo);

$data = json_decode(file_get_contents("php://input"), true);

$bill_id = $data['bill_id'] ?? null;
$amount = $data['amount'] ?? null;

if (!$bill_id || !$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("INSERT INTO payments (bill_id, amount_paid, method) VALUES (?, ?, 'online')");
    $stmt->execute([$bill_id, $amount]);

    $stmt = $pdo->prepare("UPDATE bills SET status='paid' WHERE id=?");
    $stmt->execute([$bill_id]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Payment failed']);
}
