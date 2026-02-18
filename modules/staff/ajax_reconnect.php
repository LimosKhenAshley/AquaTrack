<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];

$customer_id = $_POST['customer_id'] ?? null;
$reason = $_POST['reason'] ?? '';
$scheduled_date = $_POST['scheduled_date'] ?? null;

if(!$customer_id || !$scheduled_date){
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit;
}

// Check customer status first
$stmt = $pdo->prepare("SELECT service_status FROM customers WHERE id=?");
$stmt->execute([$customer_id]);
$status = $stmt->fetchColumn();

if($status !== 'disconnected'){
    echo json_encode(['status'=>'error','message'=>'Customer is not disconnected']);
    exit;
}

try{
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO disconnection_requests
        (customer_id, reason, requested_by, action, status, scheduled_date)
        VALUES (?,?,?,?, 'scheduled',?)
    ");

    $stmt->execute([
        $customer_id,
        $reason,
        $userId,
        'reconnect',
        $scheduled_date
    ]);

    $pdo->prepare("
        UPDATE customers
        SET service_status='pending_reconnect'
        WHERE id=?
    ")->execute([$customer_id]);

    $pdo->commit();

    echo json_encode(['status'=>'success','message'=>'Reconnection scheduled successfully']);

}catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}