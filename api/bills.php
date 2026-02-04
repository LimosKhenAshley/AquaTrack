<?php
require_once __DIR__ . '/../app/config/database.php';

header('Content-Type: application/json');

/*
 Optional (recommended later):
 - API token validation
 - Role restriction
*/

// Fetch bills with customer name and reading
$stmt = $pdo->query("
    SELECT 
        b.id,
        u.full_name AS customer_name,
        r.reading_value,
        b.amount,
        b.status,
        b.created_at
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN readings r ON b.reading_id = r.id
    ORDER BY b.created_at DESC
");

$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'count' => count($bills),
    'data' => $bills
]);
