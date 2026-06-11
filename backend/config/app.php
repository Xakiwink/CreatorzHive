<?php

declare(strict_types=1);

if (!function_exists('env')) {
    require_once dirname(__DIR__) . '/helpers/functions.php';
}

define('APP_NAME', 'CreatorzHive');
define('APP_VERSION', '1.0.0');
define('APP_URL', env('APP_URL', 'http://localhost/creatorzhive'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', true));
define('APP_TIMEZONE', 'Africa/Dar_es_Salaam');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);
