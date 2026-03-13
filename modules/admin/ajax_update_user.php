<?php
/**
 * ajax_update_user.php
 * Validates and updates a user record.
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

$id        = filter_input(INPUT_POST, 'id',        FILTER_VALIDATE_INT);
$full_name = trim($_POST['full_name'] ?? '');
$address   = trim($_POST['address']   ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$role_id   = filter_input(INPUT_POST, 'role_id',   FILTER_VALIDATE_INT);
$password  = $_POST['password'] ?? '';

// ── Validation ──────────────────────────────────────────────
if (!$id || $id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit;
}
if ($full_name === '') {
    echo json_encode(['status' => 'error', 'message' => 'Full name is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}
if ($password !== '' && strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// Check email uniqueness (exclude current user)
$chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$chk->execute([$email, $id]);
if ($chk->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'That email is already used by another account.']);
    exit;
}

// ── Build UPDATE query ───────────────────────────────────────
try {
    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE users
            SET full_name = ?, address = ?, email = ?, phone = ?, role_id = ?, password = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $address, $email, $phone, $role_id, $hashed, $id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET full_name = ?, address = ?, email = ?, phone = ?, role_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $address, $email, $phone, $role_id, $id]);
    }

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    error_log("ajax_update_user: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
}