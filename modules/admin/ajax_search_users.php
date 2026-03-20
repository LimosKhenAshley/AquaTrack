<?php
/**
 * ajax_search_users.php
 * Returns JSON array of users matching the search query.
 * Supports: search, sort column, sort direction, limit, status_filter.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';

ob_clean();
header('Content-Type: application/json');

// Only accept AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$search       = trim($_GET['search'] ?? '');
$limit        = in_array((int)($_GET['limit'] ?? 10), [10, 25, 50, 100]) ? (int)$_GET['limit'] : 10;
$statusFilter = $_GET['status_filter'] ?? 'active';
if (!in_array($statusFilter, ['active', 'archived', 'all'])) $statusFilter = 'active';

// Whitelist sort columns & directions
$sortMap = [
    'id'        => 'u.id',
    'full_name' => 'u.full_name',
    'email'     => 'u.email',
    'phone'     => 'u.phone',
    'role_name' => 'r.role_name',
    'status'    => 'u.status',
];
$sortCol = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortMap)
    ? $_GET['sort'] : 'id';
$sortDir = (isset($_GET['dir']) && strtoupper($_GET['dir']) === 'ASC') ? 'ASC' : 'DESC';
$orderBy = $sortMap[$sortCol] . ' ' . $sortDir;

// Build WHERE clauses
$whereClauses = [];
$bindParams   = [];

if ($search !== '') {
    $whereClauses[] = "(u.full_name LIKE :s OR u.email LIKE :s)";
    $bindParams[':s'] = "%$search%";
}

if ($statusFilter !== 'all') {
    $whereClauses[] = "u.status = :status";
    $bindParams[':status'] = $statusFilter;
}

$whereSQL = $whereClauses ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

$sql = "
    SELECT u.id, u.full_name, u.address, u.email, u.phone, u.role_id, r.role_name, u.status
    FROM users u
    JOIN roles r ON u.role_id = r.id
    $whereSQL
    ORDER BY {$orderBy}
    LIMIT :lim
";

$stmt = $pdo->prepare($sql);

foreach ($bindParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['users' => $users]);