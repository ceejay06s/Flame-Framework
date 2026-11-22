<?php

$env = static function ($name, $default = '') {
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
};

$config('ini__display_errors', false);
$config('ini__display_startup_errors', false);
$config('ini__log_errors', true);
$config('securitySalt', $env('APP_KEY'));
$config('hash', 'SHA256');

$config('session_path', $env('SESSION_PATH', APP . 'tmp/cache/session'));

$config('smtp_service', $env('MAIL_TRANSPORT', 'native'));
$config('smtp_server', $env('MAIL_HOST'));
$config('smtp_port', (int) $env('MAIL_PORT', 587));
$config('smtp_protocol', $env('MAIL_ENCRYPTION', 'tls'));
$config('smtp_username', $env('MAIL_USERNAME'));
$config('smtp_password', $env('MAIL_PASSWORD'));
$config('smtp_curl_url', $env('MAIL_CURL_ENDPOINT'));
$config('smtp_curl_api_key', $env('MAIL_CURL_API_KEY'));
$config('smtp_XMailer', 'Flame Framework');
$config('ini__sendmail_path', SYSTEM . 'Tools/sendmail/sendmail -t -i');
