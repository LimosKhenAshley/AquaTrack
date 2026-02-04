<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

$id = $_POST['id'];

$r = $pdo->prepare("
    SELECT customer_id, action
    FROM disconnection_requests
    WHERE id=?
");
$r->execute([$id]);
$row = $r->fetch();

if(!$row){
echo json_encode(['status'=>'error','message'=>'Not found']);
exit;
}

$pdo->beginTransaction();

$pdo->prepare("
    UPDATE disconnection_requests
    SET status='completed', completed_date=NOW()
    WHERE id=?
")->execute([$id]);

$status = ($row['action']=='disconnect')
? 'disconnected'
: 'active';

$pdo->prepare("
    UPDATE customers
    SET service_status=?
    WHERE id=?
")->execute([$status, $row['customer_id']]);

/* audit log */
$pdo->prepare("
    INSERT INTO audit_logs(user_id,action)
    VALUES (?,?)
    ")->execute([
    $_SESSION['user']['id'],
    "Completed {$row['action']} for customer {$row['customer_id']}"
]);

$pdo->commit();

echo json_encode(['status'=>'success','message'=>'Completed']);
