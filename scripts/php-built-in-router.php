<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';
$publicRoot = realpath(__DIR__ . '/../public');
$requestedFile = $publicRoot !== false ? realpath($publicRoot . $path) : false;
$normalizedPublicRoot = $publicRoot !== false ? rtrim(str_replace('\\', '/', $publicRoot), '/') : false;
$normalizedRequestedFile = $requestedFile !== false ? str_replace('\\', '/', $requestedFile) : false;
$isInsidePublicRoot = (
    $normalizedPublicRoot !== false &&
    $normalizedRequestedFile !== false &&
    (
        $normalizedRequestedFile === $normalizedPublicRoot ||
        str_starts_with($normalizedRequestedFile, $normalizedPublicRoot . '/')
    )
);

if (
    $isInsidePublicRoot &&
    is_file($requestedFile)
) {
    return false;
}

require __DIR__ . '/../public/index.php';
