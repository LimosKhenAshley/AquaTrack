<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);

require_once '../../app/config/database.php';

$user_id = $_SESSION['user']['id'];

/* map user → staff */
$stmt = $pdo->prepare("SELECT id FROM staffs WHERE user_id=?");
$stmt->execute([$user_id]);
$staff_id = $stmt->fetchColumn();

if(!$staff_id){
    die("Staff record not found.");
}

/* form values */
$request_id = $_POST['request_id'] ?? 0;
$status = $_POST['status'] ?? 'open';
$notes = trim($_POST['staff_notes'] ?? '');

/* update request */
$pdo->prepare("
    UPDATE service_requests
    SET status=?,
        staff_notes=?,
        assigned_staff_id = COALESCE(assigned_staff_id, ?),
        updated_at = NOW()
    WHERE id=?
")->execute([$status,$notes,$staff_id,$request_id]);

/* notify customer when resolved */
if($status === 'resolved'){

$stmt = $pdo->prepare("
    SELECT u.id
    FROM service_requests sr
    JOIN customers c ON c.id = sr.customer_id
    JOIN users u ON u.id = c.user_id
    WHERE sr.id=?
");
$stmt->execute([$request_id]);
$target_user = $stmt->fetchColumn();

if($target_user){
$pdo->prepare("
    INSERT INTO notifications(user_id,title,message,type)
    VALUES (?,?,?,?)
")->execute([
$target_user,
    "Service Request Resolved",
    "Your service request has been resolved by staff.",
    "system"
]);
}

}

/* audit log */
$pdo->prepare("
    INSERT INTO audit_logs(user_id,action)
    VALUES (?,?)
")->execute([
$user_id,
    "Updated service request #$request_id → $status"
]);

header("Location: service_requests.php");
exit;