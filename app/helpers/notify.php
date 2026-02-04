<?php

function sendNotification($pdo, $user_id, $title, $message, $type='system')
{
    $stmt = $pdo->prepare("
        INSERT INTO notifications(user_id,title,message,type)
        VALUES (?,?,?,?)
    ");

    $stmt->execute([
        $user_id,
        $title,
        $message,
        $type
    ]);
}
