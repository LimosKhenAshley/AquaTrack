<?php
require_once __DIR__ . '/../app/config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))");
$stmt->execute([$user['id'], $token]);

echo json_encode([
    'token' => $token,
    'expires_in' => '24 hours'
]);
