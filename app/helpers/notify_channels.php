<?php
require_once __DIR__ . '/mail_helper.php';
require_once __DIR__ . '/sms_helper.php';

function sendExternalNotification($pdo, $customer_id, $subject, $message)
{
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.email, c.phone
        FROM customers c
        JOIN users u ON u.id = c.user_id
        WHERE c.id = ?
    ");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch();

    if (!$user) return;

    /* EMAIL */
    if (!empty($user['email'])) {
        sendEmail(
            $user['email'],
            $user['full_name'],
            $subject,
            $message
        );
    }

    /* SMS */
    if (!empty($user['phone'])) {
        sendSMS(
            $user['phone'],
            $message
        );
    }
}
