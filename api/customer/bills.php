<?php
require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../../app/config/database.php';

$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
    http_response_code(400);
    echo json_encode(["error" => "Customer ID required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.amount,
        b.status,
        b.created_at,
        r.reading_value
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$customer_id]);

echo json_encode($stmt->fetchAll());
