<?php

function auditLog($pdo, $action, $description = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = $_SESSION['user']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, description, ip_address)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $action,
        $description,
        $ip
    ]);
}
