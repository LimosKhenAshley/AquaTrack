<?php
require_once __DIR__ . '/../../app/config/database.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');  // ← new
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$conditions = [];
$params     = [];

if (!empty($search)) {
    $conditions[] = "(u.full_name LIKE :search OR c.meter_number LIKE :search OR b.status LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status)) {
    $conditions[] = "b.status = :status";
    $params[':status'] = $status;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

/* COUNT */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN readings r ON b.reading_id = r.id
    $where
");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages   = (int)ceil($totalRecords / $limit);

/* FETCH DATA */
$stmt = $pdo->prepare("
    SELECT
        b.id AS bill_id,
        u.full_name,
        c.meter_number,
        r.reading_date,
        r.reading_value,
        b.amount,
        b.penalty,
        (b.amount + b.penalty) AS total_amount,
        b.status
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN readings r ON b.reading_id = r.id
    $where
    ORDER BY b.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'bills'       => $bills,
    'totalPages'  => $totalPages,
    'currentPage' => $page
]);