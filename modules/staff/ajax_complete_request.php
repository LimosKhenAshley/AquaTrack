<?php
require_once '../../app/middleware/auth.php';
checkRole(['staff']);
require_once '../../app/config/database.php';

header('Content-Type: application/json'); // ← keep here, session already started by auth.php

// REMOVE session_start(); ← this is the culprit

if(!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']){
    exit(json_encode(['status'=>'error','message'=>'Invalid CSRF token']));
}

$id = $_POST['id'] ?? null;
$userId = $_SESSION['user']['id'];

if(!$id){
    exit(json_encode(['status'=>'error','message'=>'Invalid request']));
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT customer_id, action
        FROM disconnection_requests
        WHERE id=? AND requested_by=?
        FOR UPDATE
    ");
    $stmt->execute([$id, $userId]);
    $req = $stmt->fetch();

    if(!$req) throw new Exception("Unauthorized request");

    $pdo->prepare("
        UPDATE disconnection_requests
        SET status='completed', completed_date=NOW()
        WHERE id=?
    ")->execute([$id]);

    if($req['action'] == 'disconnect'){
        $pdo->prepare("UPDATE customers SET service_status='disconnected' WHERE id=?")->execute([$req['customer_id']]);
    }
    if($req['action'] == 'reconnect'){
        $pdo->prepare("UPDATE customers SET service_status='active' WHERE id=?")->execute([$req['customer_id']]);
    }

    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'Request completed successfully']);

} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}