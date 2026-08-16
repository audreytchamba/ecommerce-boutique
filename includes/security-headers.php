<?php

declare(strict_types=1);

if (headers_sent()) {
    return;
}


header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self'; " .
    "style-src 'self' 'unsafe-inline'; " .
    "img-src 'self' data:; " .
    "media-src 'self'; " .
    "font-src 'self'; " .
    "connect-src 'self'; " .
    "object-src 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self'; " .
    "frame-ancestors 'self'; " .
    "frame-src 'none'; " .
    "upgrade-insecure-requests"
);

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=()');

if (defined('APP_ENV') && APP_ENV !== 'local'
    && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
