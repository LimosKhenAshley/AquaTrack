<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';
require_once '../../app/helpers/notify.php';

$customer_id = $_POST['customer_id'];
$reason = $_POST['reason'];
$date = $_POST['scheduled_date'];

# check unpaid overdue bills
$check = $pdo->prepare("
    SELECT COUNT(*) FROM bills
    WHERE customer_id = ?
    AND status='unpaid'
    AND due_date < CURDATE()
");
$check->execute([$customer_id]);

if ($check->fetchColumn() == 0) {
    echo json_encode([
        'status'=>'error',
        'message'=>'No overdue bills'
    ]);
    exit;
}

$pdo->prepare("
    INSERT INTO disconnection_requests
    (customer_id,reason,requested_by,action,scheduled_date)
    VALUES (?,?,?,?,?)
    ")->execute([
        $customer_id,
        $reason,
        $_SESSION['user_id'],
        'disconnect',
        $date
]);

sendNotification(
    $pdo,
    $user_id,
    "Disconnection Scheduled",
    "Your service is scheduled for disconnection due to unpaid balance.",
    "disconnect"
);


$pdo->prepare("
    UPDATE customers
    SET service_status='pending_disconnect'
    WHERE id=?
")->execute([$customer_id]);

echo json_encode([
    'status'=>'success',
    'message'=>'Disconnection scheduled'
]);

$pdo->prepare("
    INSERT INTO audit_logs(user_id,action)
    VALUES (?,?)
    ")->execute([
        $_SESSION['user_id'],
        'Scheduled disconnect for customer '.$customer_id
]);
