<?php
/**
 * ajax_archive_user.php
 * Toggles a user's status between 'active' and 'archived'.
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

// Only accept AJAX POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

$id     = filter_input(INPUT_POST, 'id',     FILTER_VALIDATE_INT);
$action = trim($_POST['action'] ?? 'archive'); // 'archive' or 'restore'

if (!$id || $id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit;
}

// Cannot archive yourself
if ($id == (int)($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot archive your own account.']);
    exit;
}

try {
    // Confirm user exists
    $check = $pdo->prepare("SELECT id, status FROM users WHERE id = ?");
    $check->execute([$id]);
    $user = $check->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    $newStatus = ($action === 'restore') ? 'active' : 'archived';

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);

    echo json_encode([
        'status'     => 'success',
        'new_status' => $newStatus,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}