<?php

declare(strict_types=1);

use CreatorzHive\Core\Application;
use CreatorzHive\Middleware\CsrfMiddleware;

function csrf_middleware(): CsrfMiddleware
{
    return Application::instance()->get(CsrfMiddleware::class);
}

function csrf_generate_token(): string
{
    return csrf_middleware()->generateToken();
}

function csrf_token(): string
{
    return csrf_middleware()->token();
}

function csrf_validate_post(): bool
{
    csrf_middleware()->validatePost();

    return true;
}
