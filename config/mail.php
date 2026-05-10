<?php

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
};

return [
    'provider' => strtolower((string) $env('MAIL_PROVIDER', 'log')),
    'from' => [
        'address' => $env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'name' => $env('MAIL_FROM_NAME', 'Taste of Macau'),
    ],
    'reply_to' => [
        'address' => $env('MAIL_REPLY_TO_ADDRESS', ''),
        'name' => $env('MAIL_REPLY_TO_NAME', ''),
    ],
    'smtp' => [
        'host' => $env('SMTP_HOST', 'smtp-relay.brevo.com'),
        'port' => (int) $env('SMTP_PORT', 465),
        'security' => strtolower((string) $env('SMTP_SECURITY', 'ssl')),
        'username' => $env('SMTP_USERNAME', ''),
        'password' => $env('SMTP_PASSWORD', ''),
        'timeout' => (int) $env('SMTP_TIMEOUT', 20),
    ],
    'cloudflare' => [
        'account_id' => $env('CLOUDFLARE_ACCOUNT_ID', ''),
        'api_token' => $env('CLOUDFLARE_EMAIL_API_TOKEN', ''),
        'endpoint' => $env(
            'CLOUDFLARE_EMAIL_ENDPOINT',
            'https://api.cloudflare.com/client/v4/accounts/{account_id}/email/sending/send'
        ),
        'timeout' => (int) $env('CLOUDFLARE_EMAIL_TIMEOUT', 10),
    ],
];
