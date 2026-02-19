<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];

$customer_id = $_POST['customer_id'] ?? null;
$reason = $_POST['reason'] ?? '';
$scheduled_date = $_POST['scheduled_date'] ?? null;
$reconnection_fee = $_POST['reconnection_fee'] ?? 0;

if(!$customer_id || !$scheduled_date){
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit;
}

try{

    // 1️⃣ Check customer is disconnected
    $stmt = $pdo->prepare("SELECT service_status FROM customers WHERE id=?");
    $stmt->execute([$customer_id]);
    $status = $stmt->fetchColumn();

    if($status !== 'disconnected'){
        echo json_encode(['status'=>'error','message'=>'Customer is not disconnected']);
        exit;
    }

    // 2️⃣ Check unpaid bills
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM bills
        WHERE customer_id=?
        AND status='unpaid'
    ");
    $stmt->execute([$customer_id]);
    $unpaid = $stmt->fetchColumn();

    if($unpaid > 0){
        echo json_encode([
            'status'=>'error',
            'message'=>'Customer still has unpaid bills.'
        ]);
        exit;
    }

    // 3️⃣ Insert reconnection request
    $pdo->prepare("
        INSERT INTO disconnection_requests
        (customer_id, reason, requested_by, action, status, scheduled_date, reconnection_fee)
        VALUES (?,?,?,?, 'scheduled',?,?)
    ")->execute([
        $customer_id,
        $reason,
        $userId,
        'reconnect',
        $scheduled_date,
        $reconnection_fee
    ]);

    $pdo->prepare("
        UPDATE customers
        SET service_status='pending_reconnect'
        WHERE id=?
    ")->execute([$customer_id]);

    echo json_encode([
        'status'=>'success',
        'message'=>'Reconnect scheduled successfully'
    ]);

}catch(Exception $e){
    echo json_encode([
        'status'=>'error',
        'message'=>$e->getMessage()
    ]);
}