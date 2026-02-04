<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$notification_id = $input['id'] ?? null;
$user_id = $_SESSION['user']['id'];

if(!$notification_id){
    echo json_encode(['status'=>'error','message'=>'Invalid ID']);
    exit;
}

// Mark as read
$stmt = $pdo->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ? AND user_id = ?
");
if($stmt->execute([$notification_id, $user_id])){
    echo json_encode(['status'=>'success','message'=>'Notification marked as read']);
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to update notification']);
}
