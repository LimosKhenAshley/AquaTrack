<?php

/**
 * Normalize Philippine phone numbers to +639XXXXXXXXX
 * Twilio requires international format
 */
function normalizePhone($phone)
{
    $phone = preg_replace('/\D/', '', $phone);

    if (str_starts_with($phone, '09')) {
        return '+63' . substr($phone, 1);
    }

    if (str_starts_with($phone, '639')) {
        return '+' . $phone;
    }

    if (str_starts_with($phone, '63')) {
        return '+' . $phone;
    }

    if (str_starts_with($phone, '9')) {
        return '+63' . $phone;
    }

    return $phone;
}

/**
 * Send SMS via Twilio API
 *
 * @param string $phone
 * @param string $message
 * @return array{success: bool, error: string|null, status: int|null}
 */
function sendSMS($phone, $message)
{
    if (!$phone) {
        return ['success' => false, 'error' => 'No phone number provided.', 'status' => null];
    }

    /* ==============================
       Load Twilio credentials
    ============================== */
    $sid   = defined('TWILIO_SID')        ? TWILIO_SID        : '';
    $token = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
    $from  = defined('TWILIO_PHONE')      ? TWILIO_PHONE      : '';

    if (!$sid || !$token || !$from) {
        error_log("SMS skipped — Twilio credentials not defined.");
        return ['success' => false, 'error' => 'Twilio credentials not defined.', 'status' => null];
    }

    /* ==============================
       Normalize & validate phone number
    ============================== */
    $phone = normalizePhone($phone);

    if (!preg_match('/^\+639\d{9}$/', $phone)) {
        error_log("SMS skipped — invalid phone number after normalization: $phone");
        return ['success' => false, 'error' => "Invalid phone number after normalization: $phone", 'status' => null];
    }

    /* ==============================
       Twilio API request
    ============================== */
    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

    $data = [
        'From' => $from,
        'To'   => $phone,
        'Body' => $message,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST,            true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,      http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
    curl_setopt($ch, CURLOPT_USERPWD,         "$sid:$token");
    curl_setopt($ch, CURLOPT_TIMEOUT,         10);

    $response   = curl_exec($ch);
    $curlError  = curl_error($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE); // FIX: capture HTTP status
    curl_close($ch);

    /* ==============================
       Handle cURL transport errors
    ============================== */
    if ($curlError) {
        error_log("Twilio cURL error for $phone: $curlError");
        return ['success' => false, 'error' => "cURL error: $curlError", 'status' => null];
    }

    /* ==============================
       FIX: Handle Twilio API-level errors
       2xx = success, anything else = failure
    ============================== */
    if ($httpStatus < 200 || $httpStatus >= 300) {
        $decoded      = json_decode($response, true);
        $twilioMessage = $decoded['message'] ?? $response;
        $twilioCode    = $decoded['code']    ?? 'N/A';

        error_log("Twilio API error for $phone — HTTP $httpStatus | Code: $twilioCode | $twilioMessage");
        return [
            'success' => false,
            'error'   => "Twilio error $twilioCode: $twilioMessage",
            'status'  => $httpStatus,
        ];
    }

    return ['success' => true, 'error' => null, 'status' => $httpStatus];
}