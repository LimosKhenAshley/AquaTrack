<?php
require_once __DIR__ . '/../../app/config/database.php';

// ── Input params ──────────────────────────────────────────────────
$search         = trim($_GET['search']          ?? '');
$page           = max(1, (int)($_GET['page']    ?? 1));
$perPage        = 10;
$offset         = ($page - 1) * $perPage;

$filterStatus   = $_GET['filter_status']        ?? '';
$filterReading  = $_GET['filter_reading']       ?? '';   // 'has' | 'none' | ''
$filterDateFrom = $_GET['filter_date_from']     ?? '';
$filterDateTo   = $_GET['filter_date_to']       ?? '';
$sortBy         = $_GET['sort_by']              ?? 'meter_number';

$allowedSorts = ['meter_number', 'full_name', 'last_reading_date'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'meter_number';
}

// ── WHERE conditions ──────────────────────────────────────────────
$conditions = ["(u.full_name LIKE :search OR c.meter_number LIKE :search)"];
$params     = [':search' => "%$search%"];

if ($filterStatus !== '') {
    $conditions[] = "c.service_status = :status";
    $params[':status'] = $filterStatus;
}

if ($filterDateFrom !== '') {
    $conditions[] = "EXISTS (SELECT 1 FROM readings r2 WHERE r2.customer_id = c.id AND r2.reading_date >= :date_from)";
    $params[':date_from'] = $filterDateFrom;
}

if ($filterDateTo !== '') {
    $conditions[] = "EXISTS (SELECT 1 FROM readings r2 WHERE r2.customer_id = c.id AND r2.reading_date <= :date_to)";
    $params[':date_to'] = $filterDateTo;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// ── HAVING for has/no reading filter ──────────────────────────────
$havingClause = '';
if ($filterReading === 'has')  $havingClause = 'HAVING last_reading IS NOT NULL';
if ($filterReading === 'none') $havingClause = 'HAVING last_reading IS NULL';

// ── Sort column ───────────────────────────────────────────────────
$sortColumn = match($sortBy) {
    'full_name'         => 'u.full_name',
    'last_reading_date' => 'last_reading_date',
    default             => 'c.meter_number',   // default: meter_number ASC
};

// ── Total count (respects all filters) ───────────────────────────
$countSQL = "
    SELECT COUNT(*) FROM (
        SELECT c.id
        FROM customers c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN readings r ON c.id = r.customer_id
        $whereClause
        GROUP BY c.id
        $havingClause
    ) sub
";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalCustomers = (int) $countStmt->fetchColumn();
$totalPages     = (int) ceil($totalCustomers / $perPage);

// ── Fetch rows ────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        c.id                 AS customer_id,
        c.service_status,
        u.full_name,
        c.meter_number,
        MAX(r.reading_date)  AS last_reading_date,
        MAX(r.reading_value) AS last_reading,
        MAX(r.id)            AS last_reading_id
    FROM customers c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN readings r ON c.id = r.customer_id
    $whereClause
    GROUP BY c.id, u.full_name, c.meter_number, c.service_status
    $havingClause
    ORDER BY $sortColumn ASC
    LIMIT :offset, :perPage
");

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Return JSON ───────────────────────────────────────────────────
header('Content-Type: application/json');
echo json_encode([
    'customers'  => $customers,
    'total'      => $totalCustomers,
    'totalPages' => $totalPages,
]);