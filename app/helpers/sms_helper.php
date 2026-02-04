<?php

function sendSMS($phone, $message)
{
    // Example placeholder for API call
    // Replace with your SMS provider API

    $api_url = "https://api.smsprovider.com/send";

    $payload = [
        'to' => $phone,
        'message' => $message
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response !== false;
}
