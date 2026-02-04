<?php
require_once __DIR__ . '/../app/config/database.php';

function verifyToken($pdo) {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }

    $token = trim(str_replace('Bearer', '', $headers['Authorization']));

    $stmt = $pdo->prepare("SELECT user_id FROM api_tokens WHERE token = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$token]);

    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    return $user['user_id'];
}
