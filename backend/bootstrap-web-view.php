<?php

declare(strict_types=1);

/**
 * Minimal bootstrap for standalone PHP views under frontend/pages/
 * (when Apache executes them directly). Full app flow uses backend/index.php instead.
 */
require_once __DIR__ . '/helpers/functions.php';

load_env(base_path('.env'));
require_once __DIR__ . '/config/app.php';

date_default_timezone_set((string) APP_TIMEZONE);

$composerAutoload = base_path('vendor/autoload.php');
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/middleware/csrf.php';

session_start_safe();
csrf_generate_token();
