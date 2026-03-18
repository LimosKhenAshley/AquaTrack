<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user']['id'];

// Mark ALL as read
if (!empty($input['mark_all'])) {
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ? AND is_read = 0
    ");
    if ($stmt->execute([$user_id])) {
        echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update notifications']);
    }
    exit;
}

// Mark SINGLE notification as read
$notification_id = $input['id'] ?? null;

if (!$notification_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ? AND user_id = ?
");
if ($stmt->execute([$notification_id, $user_id])) {
    echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update notification']);
}