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

$user_id = $_SESSION['user']['id'];

/* VERIFY BILL BELONGS TO CUSTOMER */
$stmt = $pdo->prepare("
    SELECT b.id, b.customer_id, b.amount, b.penalty
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    WHERE b.id = ? AND c.user_id = ?
");
$stmt->execute([$bill_id, $user_id]);
$bill = $stmt->fetch();

if(!$bill){
    echo json_encode(['status'=>'error','message'=>'Bill not found']);
    exit;
}

$total_amount = $bill['amount'] + $bill['penalty'];

try {

    /* =========================
       CASH PAYMENT
       ========================= */
    if($method === 'cash'){

        echo json_encode([
            'status' => 'cash',
            'message' => 'Please proceed to the AquaTrack office to pay this bill in person.'
        ]);
        exit;
    }

    /* =========================
       ONLINE / CARD PAYMENT
       ========================= */

    $pdo->beginTransaction();

    // record payment request
    $stmt = $pdo->prepare("
        INSERT INTO payments (bill_id, amount_paid, method)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$bill_id, $total_amount, $method]);

    // change bill to pending
    $stmt = $pdo->prepare("
        UPDATE bills 
        SET status='pending', overdue_notified = 1
        WHERE id=?
    ");
    $stmt->execute([$bill_id]);

    $pdo->commit();

    // send notification
    $userStmt = $pdo->prepare("SELECT user_id FROM customers WHERE id=?");
    $userStmt->execute([$bill['customer_id']]);
    $uid = $userStmt->fetchColumn();

    sendNotification(
        $pdo,
        $uid,
        "Payment Pending",
        "Your payment of ₱{$total_amount} is waiting for staff verification.",
        "payment"
    );

    echo json_encode([
        'status'=>'success',
        'message'=>'Payment submitted. Waiting for staff verification.'
    ]);

} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>'Payment failed.']);
}