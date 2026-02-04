<?php
$check = $pdo->prepare("
    SELECT COUNT(*) FROM bills
    WHERE customer_id=?
    AND status='unpaid'
");
$check->execute([$customer_id]);

if ($check->fetchColumn() > 0) {
    echo json_encode([
        'status'=>'error',
        'message'=>'Customer still has unpaid bills'
    ]);
    exit;
}

$pdo->prepare("
    UPDATE customers
    SET service_status='active'
    WHERE id=?
")->execute([$customer_id]);

$pdo->prepare("
    INSERT INTO audit_logs(user_id,action)
    VALUES (?,?)
    ")->execute([
        $_SESSION['user_id'],
        'Scheduled reconnection for customer '.$customer_id
]);

