<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$user_id = verifyToken($pdo);

// find customer
$stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();

if (!$customer) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.id, b.amount, b.status, r.reading_value, b.created_at
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$customer['id']]);

echo json_encode($stmt->fetchAll());
