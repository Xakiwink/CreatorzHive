<?php

declare(strict_types=1);

/**
 * Router for PHP's built-in server when the document root is the project root
 * (so /frontend/… static files resolve). Mimics Apache routing to public/index.php.
 *
 *   composer run serve
 *
 * Open http://127.0.0.1:8080/ and set APP_URL=http://127.0.0.1:8080 in .env (no path) for this mode.
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($uri);
if ($path === '' || $path === '/') {
    require __DIR__ . '/public/index.php';

    return;
}

$local = __DIR__ . $path;
if (is_file($local)) {
    return false;
}

require __DIR__ . '/public/index.php';
