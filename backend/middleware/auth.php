<?php

declare(strict_types=1);

use CreatorzHive\Core\Application;
use CreatorzHive\Middleware\AuthMiddleware;

function auth_middleware_handle(bool $isApi = false): void
{
    Application::instance()->get(AuthMiddleware::class)->handle($isApi);
}
