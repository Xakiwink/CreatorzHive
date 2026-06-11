<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/functions.php';

load_env(base_path('.env'));
require_once __DIR__ . '/config/app.php';

date_default_timezone_set((string) APP_TIMEZONE);

if (!\defined('CREATORZHIVE_PHPUNIT') || !CREATORZHIVE_PHPUNIT) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$composerAutoload = base_path('vendor/autoload.php');
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

require_once __DIR__ . '/bootstrap-oop.php';
require_once __DIR__ . '/bootstrap-procedural.php';

error_handler_register();
session_start_safe();
csrf_generate_token();

router_reset();

if (\function_exists('api_cors_handle_preflight')) {
    api_cors_handle_preflight();
}

require __DIR__ . '/routes/web.php';
router_api_mode(true);
require __DIR__ . '/routes/api.php';

router_dispatch();
