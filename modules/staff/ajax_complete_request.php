<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if(!$id){
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

try{

    $pdo->beginTransaction();

    // Get request details
    $stmt = $pdo->prepare("
        SELECT customer_id, action
        FROM disconnection_requests
        WHERE id=?
    ");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if(!$req){
        throw new Exception("Request not found");
    }

    // Mark request completed
    $pdo->prepare("
        UPDATE disconnection_requests
        SET status='completed',
            completed_date=NOW()
        WHERE id=?
    ")->execute([$id]);

    // Update customer service status
    if($req['action'] === 'disconnect'){
        $pdo->prepare("
            UPDATE customers
            SET service_status='disconnected'
            WHERE id=?
        ")->execute([$req['customer_id']]);
    }

    if($req['action'] === 'reconnect'){

        // Check reconnection fee is paid
        $stmt = $pdo->prepare("
            SELECT reconnection_fee FROM disconnection_requests
            WHERE id=?
        ");
        $stmt->execute([$id]);
        $fee = $stmt->fetchColumn();

        if($fee > 0){
            // You may later check if payment exists
            // For now assume fee is paid manually
        }

        $pdo->prepare("
            UPDATE customers
            SET service_status='active'
            WHERE id=?
        ")->execute([$req['customer_id']]);
    }

    $pdo->commit();

    echo json_encode(['status'=>'success','message'=>'Request completed successfully']);

}catch(Exception $e){

    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}