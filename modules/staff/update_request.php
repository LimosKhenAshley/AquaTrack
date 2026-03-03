<?php
session_start();

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

/* ================================
   🔐 SECURITY VALIDATIONS
================================ */

/* 1️⃣ Status whitelist */
$allowed = ['open', 'in_progress', 'resolved', 'rejected', 'cancelled'];
if (!in_array($status, $allowed)) {
    die("Invalid status");
}

/* 2️⃣ CSRF validation */
if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
    die("CSRF failed");
}

/* 3️⃣ Verify ownership OR allow claim if unassigned */
$check = $pdo->prepare("
    SELECT status 
    FROM service_requests
    WHERE id = ? 
      AND (assigned_staff_id = ? OR assigned_staff_id IS NULL)
");
$check->execute([$request_id, $staff_id]);
$oldStatus = $check->fetchColumn();

if (!$oldStatus) {
    die("Unauthorized action");
}

/* ================================
   ✅ UPDATE REQUEST
================================ */

$pdo->prepare("
    UPDATE service_requests
    SET status = ?, 
        staff_notes = ?, 
        assigned_staff_id = COALESCE(assigned_staff_id, ?),
        updated_at = NOW()
    WHERE id = ?
")->execute([$status, $notes, $staff_id, $request_id]);

/* ================================
   📝 TIMELINE LOGGING
================================ */

$pdo->prepare("
    INSERT INTO service_request_updates
        (request_id, old_status, new_status, notes, updated_by)
    VALUES 
        (?, ?, ?, ?, ?)
")->execute([
    $request_id,
    $oldStatus,
    $status,
    $notes,
    $staff_id
]);

/* ================================
   🔔 NOTIFY CUSTOMER IF RESOLVED
================================ */

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

/* ================================
   📜 AUDIT LOG
================================ */

$pdo->prepare("
    INSERT INTO audit_logs(user_id,action)
    VALUES (?,?)
")->execute([
    $user_id,
    "Updated service request #$request_id → $status"
]);

header("Location: service_requests.php");
exit;