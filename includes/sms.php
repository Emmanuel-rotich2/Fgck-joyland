<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/sms.php';

function sms_log(string $message): void
{
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents(
        $dir . '/sms.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Normalize common Kenyan numbers to E.164.
 * Examples:
 * 0706004939 -> +254706004939
 * 0112345678 -> +254112345678
 * +254706004939 -> +254706004939
 */
function normalize_kenyan_phone(string $phone): ?string
{
    $phone = trim($phone);
    $phone = preg_replace('/[^\d+]/', '', $phone);

    if ($phone === '') {
        return null;
    }

    if (str_starts_with($phone, '00')) {
        $phone = '+' . substr($phone, 2);
    }

    if (str_starts_with($phone, '+254')) {
        $number = $phone;
    } elseif (str_starts_with($phone, '254')) {
        $number = '+' . $phone;
    } elseif (preg_match('/^0(7|1)\d{8}$/', $phone)) {
        $number = '+254' . substr($phone, 1);
    } else {
        return null;
    }

    return preg_match('/^\+254[17]\d{8}$/', $number) ? $number : null;
}

function send_sms(string $to, string $message): bool
{
    if (!SMS_ENABLED) {
        sms_log('SMS is disabled. Recipient: ' . $to);
        return false;
    }

    $to = normalize_kenyan_phone($to);

    if (!$to) {
        sms_log('Invalid Kenyan phone number.');
        return false;
    }

    if (
        SMS_TWILIO_ACCOUNT_SID === 'YOUR_TWILIO_ACCOUNT_SID' ||
        SMS_TWILIO_AUTH_TOKEN === 'YOUR_TWILIO_AUTH_TOKEN' ||
        SMS_TWILIO_FROM === 'YOUR_TWILIO_PHONE_NUMBER'
    ) {
        sms_log('Twilio credentials are not configured.');
        return false;
    }

    $url = 'https://api.twilio.com/2010-04-01/Accounts/'
        . rawurlencode(SMS_TWILIO_ACCOUNT_SID)
        . '/Messages.json';

    $postFields = http_build_query([
        'To' => $to,
        'From' => SMS_TWILIO_FROM,
        'Body' => $message,
    ]);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => SMS_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => SMS_TWILIO_ACCOUNT_SID . ':' . SMS_TWILIO_AUTH_TOKEN,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        sms_log(
            'Twilio SMS failed. HTTP=' . $httpCode .
            ' error=' . $curlError .
            ' response=' . substr((string)$response, 0, 500)
        );
        return false;
    }

    return true;
}

function send_password_reset_sms(string $phone, string $code): bool
{
    $message =
        'FGCK Makutano West Joyland Pastor Portal: Your password reset code is '
        . $code
        . '. It expires in 10 minutes. If you did not request this, ignore this message.';

    return send_sms($phone, $message);
}
