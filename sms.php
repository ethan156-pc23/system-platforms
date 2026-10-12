<?php
// SMS settings. Leave SMS_API_KEY empty while testing locally.
const SMS_API_KEY    = '';     // your SMS provider API key (example below uses Semaphore)
const SMS_SENDER     = '';     // approved sender name (optional)
const SHOW_TEST_CODE = true;   // set to false on the live site

// Accepts 09xxxxxxxxx, 639xxxxxxxxx or +639xxxxxxxxx and returns 09xxxxxxxxx, or null if not a PH mobile number
function normalize_ph_mobile(string $number): ?string {
    $n = preg_replace('/[\s\-()]/', '', $number);
    if (preg_match('/^(?:\+?63|0)(9\d{9})$/', $n, $m)) {
        return '0' . $m[1];
    }
    return null;
}

function send_sms_otp(string $mobile, string $otp): bool {
    $fields = [
        'apikey'  => SMS_API_KEY,
        'number'  => $mobile,
        'message' => "Your Cebu Emergency verification code is $otp. It expires in 10 minutes.",
    ];
    if (SMS_SENDER !== '') {
        $fields['sendername'] = SMS_SENDER;
    }
    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $res !== false && $http >= 200 && $http < 300;
}