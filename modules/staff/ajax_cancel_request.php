<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if(!$id){
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$pdo->prepare("
    UPDATE disconnection_requests
    SET status='cancelled'
    WHERE id=?
")->execute([$id]);

echo json_encode(['status'=>'success','message'=>'Request cancelled']);