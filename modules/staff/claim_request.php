<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

if($_POST['csrf'] !== $_SESSION['csrf']) die("CSRF");

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT id FROM staffs WHERE user_id=?");
$stmt->execute([$user_id]);
$staff_id = $stmt->fetchColumn();

$id = $_POST['id'];

$pdo->prepare("
    UPDATE service_requests
    SET assigned_staff_id=?
    WHERE id=? AND assigned_staff_id IS NULL
")->execute([$staff_id,$id]);

header("Location: service_requests.php");