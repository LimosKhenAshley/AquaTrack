<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mail_helper.php';   // PHPMailer wrapper
require_once __DIR__ . '/sms_helper.php';    // SMS wrapper (if added)

/**
 * Send system notification
 */
function sendNotification($pdo, $user_id, $title, $message, $type = 'system')
{
    /* =========================
       Always store in-app notification
    ========================== */
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $title, $message, $type]);

    /* =========================
       Load user + preferences
    ========================== */
    $stmt = $pdo->prepare("
        SELECT u.email, u.phone,
               p.email_enabled, p.sms_enabled
        FROM users u
        LEFT JOIN user_contact_preferences p
            ON p.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) return;

    /* =========================
       EMAIL
    ========================== */
    if (!empty($user['email']) && ($user['email_enabled'] ?? 1)) {
        sendEmail(
            $user['email'],
            $title,
            nl2br($message)
        );
    }

    /* =========================
       SMS
    ========================== */
    if (!empty($user['phone']) && ($user['sms_enabled'] ?? 0)) {
        sendSMS(
            $user['phone'],
            $title . " — " . $message
        );
    }
}
