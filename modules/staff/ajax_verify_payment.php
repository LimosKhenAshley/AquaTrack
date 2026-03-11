<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);

require_once '../../app/config/database.php';
require_once '../../app/helpers/notify.php';

$bill_id = $_POST['bill_id'] ?? null;
$result = $_POST['result'] ?? null;

if(!$bill_id){
    echo json_encode([
        'status'=>'error',
        'message'=>'Bill ID missing'
    ]);
    exit;
}

if($result === 'approve'){

    $pdo->prepare("
        UPDATE bills
        SET status='paid'
        WHERE id=?
    ")->execute([$bill_id]);

}else{

    $pdo->prepare("
        UPDATE bills
        SET status='unpaid'
        WHERE id=?
    ")->execute([$bill_id]);

}

//send notification to customer
$stmt = $pdo->prepare("
    SELECT c.user_id, b.amount, b.penalty
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    WHERE b.id = ?
");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();
if($bill){
    $user_id = $bill['user_id'];
    $total_amount = $bill['amount'] + $bill['penalty'];

    if($result === 'approve'){
        sendNotification($pdo, $user_id, "Payment Approved", "Your payment of ₱".number_format($total_amount,2)." has been verified. Thank you!");
    }else{
        sendNotification($pdo, $user_id, "Payment Rejected", "Your payment of ₱".number_format($total_amount,2)." was rejected. Please contact support for assistance.");
    }
}
echo json_encode([
    'status'=>'success'
]);