<?php
require_once __DIR__ . '/../../app/config/database.php';

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

/* COUNT */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE u.full_name LIKE :search 
       OR c.meter_number LIKE :search
");
$countStmt->execute(['search' => "%$search%"]);
$totalCustomers = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalCustomers / $perPage);

/* FETCH DATA */
$stmt = $pdo->prepare("
    SELECT
        c.id AS customer_id,
        c.service_status,
        u.full_name,
        c.meter_number,
        MAX(r.reading_date) AS last_reading_date,
        MAX(r.reading_value) AS last_reading,
        MAX(r.id) AS last_reading_id
    FROM customers c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN readings r ON c.id = r.customer_id
    WHERE u.full_name LIKE :search 
       OR c.meter_number LIKE :search
    GROUP BY c.id, u.full_name, c.meter_number
    ORDER BY u.full_name
    LIMIT :offset, :perPage
");

$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();

$customers = $stmt->fetchAll();

/* RETURN JSON */
echo json_encode([
    'customers' => $customers,
    'totalPages' => $totalPages
]);