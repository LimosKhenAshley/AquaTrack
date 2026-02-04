<?php

function sendSMS($phone, $message)
{
    if (!$phone) return false;

    $apiKey = 'd4f1d3350605bdd2b3660d73d6262484';

    $payload = [
        'apikey' => $apiKey,
        'number' => $phone,
        'message' => $message,
        'sendername' => 'AquaTrack'
    ];

    $ch = curl_init('https://semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response !== false;
}
