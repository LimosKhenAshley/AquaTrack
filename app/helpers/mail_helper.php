<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendEmail($to, $subject, $htmlMessage)
{
    $mail = new PHPMailer(true);

    try {

        /* ======================
           SMTP CONFIG
        ====================== */

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // CHANGE THESE
        $mail->Username   = 'aquatrack.2026@gmail.com';
        $mail->Password   = 'tktv ddpg xkty jpet';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        /* ======================
           MESSAGE CONFIG
        ====================== */

        $mail->setFrom('aquatrack.2026@gmail.com', 'AquaTrack System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = buildEmailTemplate($htmlMessage);
        $mail->AltBody = strip_tags($htmlMessage);

        $mail->send();

        return true;

    } catch (Exception $e) {

        // Optional: log error
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}

function buildEmailTemplate($content)
{
    return "
    <div style='font-family:Arial,sans-serif;background:#f5f6fa;padding:30px'>
        <div style='max-width:600px;background:#ffffff;border-radius:8px;padding:25px'>

            <h2 style='color:#0d6efd;margin-top:0'>
                AquaTrack Water Billing
            </h2>

            <div style='font-size:15px;color:#333'>
                {$content}
            </div>

            <hr style='margin:25px 0'>

            <p style='font-size:12px;color:#777'>
                This is an automated message from AquaTrack.<br>
                Please do not reply directly.
            </p>

        </div>
    </div>
    ";
}
