<?php

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
};

return [
    'name' => 'Taste of Macau',
    'url' => $env('APP_URL', 'http://localhost:8000'),
    'key' => $env('APP_KEY', ''),
    'debug' => filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'locale' => $env('APP_LOCALE', 'zh'),
    'timezone' => $env('APP_TIMEZONE', 'Asia/Macau'),
];
