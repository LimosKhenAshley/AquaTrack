<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

header('Content-Type: application/json');
session_start();

if(!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']){
    exit(json_encode(['status'=>'error','message'=>'Invalid CSRF token']));
}

$id = $_POST['id'] ?? null;
$userId = $_SESSION['user']['id'];

if(!$id){
    exit(json_encode(['status'=>'error','message'=>'Invalid request']));
}

$stmt = $pdo->prepare("
    UPDATE disconnection_requests
    SET status='cancelled'
    WHERE id=? AND requested_by=? AND status='scheduled'
");

$stmt->execute([$id,$userId]);

echo json_encode([
    'status'=>'success',
    'message'=>'Request cancelled'
]);