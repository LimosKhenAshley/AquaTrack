<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

$id = $_POST['id'];

$pdo->prepare("
    UPDATE disconnection_requests
    SET status='cancelled'
    WHERE id=?
")->execute([$id]);

echo json_encode([
    'status'=>'success',
    'message'=>'Request cancelled'
]);
