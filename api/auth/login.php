<?php
require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../../app/config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.password, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
    exit;
}

echo json_encode([
    "user_id" => $user['id'],
    "name" => $user['full_name'],
    "role" => $user['role_name']
]);
