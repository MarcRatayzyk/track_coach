<?php
/**
 * Dev router for PHP built-in server.
 *
 * Usage:
 * php -S 127.0.0.1:8000 dev/php-router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$fullPath = __DIR__ . '/../public' . $path;

if ($path !== '/' && is_file($fullPath)) {
    return false; // serve the requested resource as-is.
}

require __DIR__ . '/../public/index.php';

