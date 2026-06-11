<?php

declare(strict_types=1);

/**
 * Legacy procedural stack: middleware, router, jobs, compat bridges to OOP layer.
 * Domain logic lives in src/ (Repositories, Services, Controllers).
 */
$backend = __DIR__;

require_once $backend . '/config/app.php';

require_once $backend . '/compat/models.php';
require_once $backend . '/compat/services.php';
require_once $backend . '/compat/auth.php';

require_once $backend . '/helpers/platforms.php';
require_once $backend . '/core/database.php';
require_once $backend . '/core/session.php';
require_once $backend . '/core/response.php';
require_once $backend . '/helpers/api_cors.php';
require_once $backend . '/core/request.php';
require_once $backend . '/core/validator.php';
require_once $backend . '/core/token_crypto.php';
require_once $backend . '/core/error_handler.php';
require_once $backend . '/core/mailer.php';
require_once $backend . '/middleware/csrf.php';
require_once $backend . '/middleware/auth.php';
require_once $backend . '/middleware/role.php';
require_once $backend . '/http.php';
require_once $backend . '/core/router.php';

require_once $backend . '/core/job_runner.php';
