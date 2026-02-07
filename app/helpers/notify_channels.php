<?php
require_once __DIR__ . '/mail_helper.php';
require_once __DIR__ . '/sms_helper.php';

function sendUserNotifications($pdo, $user_id, $subject, $message)
{
    // get contact + prefs
    $stmt = $pdo->prepare("
        SELECT u.email, u.phone, p.email_enabled, p.sms_enabled
        FROM users u
        LEFT JOIN user_contact_preferences p ON p.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();

    if (!$u) return;

    /* EMAIL */
    if ($u['email_enabled'] && !empty($u['email'])) {
        sendEmail($u['email'], $subject, $message);
    }

    /* SMS */
    if ($u['sms_enabled'] && !empty($u['phone'])) {
        sendSMS($u['phone'], $message);
    }
}

