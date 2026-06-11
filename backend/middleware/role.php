<?php

declare(strict_types=1);

use CreatorzHive\Core\Application;
use CreatorzHive\Middleware\RoleMiddleware;

function role_middleware_require(string ...$roles): void
{
    Application::instance()->get(RoleMiddleware::class)->requireRoles(...$roles);
}

function role_middleware_require_non_admin(): void
{
    Application::instance()->get(RoleMiddleware::class)->requireNonAdmin();
}
