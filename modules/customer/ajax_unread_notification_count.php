<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$user_id]);

echo json_encode([
    'status' => 'success',
    'count' => (int)$stmt->fetchColumn()
]);
