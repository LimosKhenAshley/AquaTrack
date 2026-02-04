<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';

$id = $_POST['id'];

$pdo->prepare("
    UPDATE notifications
    SET is_read=1
    WHERE id=?
")->execute([$id]);

echo json_encode(['status'=>'ok']);
