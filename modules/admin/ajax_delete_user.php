<?php
/**
 * ajax_delete_user.php
 * Securely deletes a user and their role-specific record.
 */

// Suppress warnings/notices from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering so any accidental output can be discarded
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';

// Discard any buffered output (warnings etc.) before sending JSON
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

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit;
}

// Cannot delete yourself
if ($id == (int)($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch role so we can remove the role-specific row
    $roleStmt = $pdo->prepare("
        SELECT r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $roleStmt->execute([$id]);
    $role_name = $roleStmt->fetchColumn();

    if (!$role_name) {
        throw new Exception("User not found.");
    }

    // Delete role-specific record first (FK constraint).
    // Try the plural table name first; if it doesn't exist, fall back to singular.
    $roleTableCandidates = [
        'admin'    => ['admins',    'admin'],
        'staff'    => ['staffs',    'staff'],
        'customer' => ['customers', 'customer'],
        'owner'    => ['owners',    'owner'],
    ];

    if (isset($roleTableCandidates[$role_name])) {
        $deleted = false;
        foreach ($roleTableCandidates[$role_name] as $tbl) {
            try {
                $pdo->prepare("DELETE FROM `{$tbl}` WHERE user_id = ?")
                    ->execute([$id]);
                $deleted = true;
                break; // success — stop trying
            } catch (PDOException $e) {
                // Table doesn't exist (1146) — try next candidate
                if (strpos($e->getCode(), '42S02') !== false || $e->errorInfo[1] == 1146) {
                    continue;
                }
                throw $e; // re-throw unexpected errors
            }
        }
    }

    // Delete the user
    $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $del->execute([$id]);

    if ($del->rowCount() === 0) {
        throw new Exception("User not found or already deleted.");
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}