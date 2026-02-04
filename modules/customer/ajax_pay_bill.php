<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';
require_once '../../app/helpers/notify.php';


header('Content-Type: application/json');

$bill_id = $_POST['bill_id'] ?? null;
$method  = $_POST['method'] ?? 'cash';

if(!$bill_id){
    echo json_encode(['status'=>'error','message'=>'Invalid bill ID']);
    exit;
}

// check if bill belongs to this customer
$customer_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("
    SELECT id, amount, penalty FROM bills
    WHERE id = ? AND customer_id = (
        SELECT id FROM customers WHERE user_id = ?
    )
");
$stmt->execute([$bill_id, $customer_id]);
$bill = $stmt->fetch();

if(!$bill){
    echo json_encode(['status'=>'error','message'=>'Bill not found']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO payments (bill_id, amount_paid, method)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$bill_id, $bill['amount'], $method]);

    // mark bill paid
    $stmt = $pdo->prepare("UPDATE bills SET status='paid' WHERE id=?");
    $stmt->execute([$bill_id]);

    $pdo->commit();

    // get user id
    $user = $pdo->prepare("
        SELECT user_id FROM customers WHERE id=?
    ");
    $user->execute([$bill['customer_id']]);
    $uid = $user->fetchColumn();

    sendNotification(
        $pdo,
        $uid,
        "Payment Received",
        "Your payment of ₱{$bill['amount']} was successfully recorded.",
        "payment"
    );

    echo json_encode(['status'=>'success','message'=>'Payment successful!']);
} catch (Exception $e){
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>'Payment failed.']);
}
