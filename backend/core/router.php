<?php

declare(strict_types=1);

function router_reset(): void
{
    $GLOBALS['cz_router_routes'] = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];
    $GLOBALS['cz_router_api_mode'] = false;
}

function router_api_mode(bool $enabled = true): void
{
    $GLOBALS['cz_router_api_mode'] = $enabled;
}

/**
 * @param callable|string|array{0: class-string, 1: string} $handler
 */
function router_register(string $method, string $route, $handler, array $middleware = []): void
{
    if (!isset($GLOBALS['cz_router_routes'])) {
        router_reset();
    }
    $route = trim($route, '/');
    $method = strtoupper($method);
    $GLOBALS['cz_router_routes'][$method][$route] = [
        'handler' => $handler,
        'middleware' => $middleware,
        'is_api' => !empty($GLOBALS['cz_router_api_mode']),
    ];
}

function router_get(string $route, $handler, array $middleware = []): void
{
    router_register('GET', $route, $handler, $middleware);
}

function router_post(string $route, $handler, array $middleware = []): void
{
    router_register('POST', $route, $handler, $middleware);
}

/** @param class-string $controller */
function router_get_action(string $route, string $controller, string $action, array $middleware = []): void
{
    router_register('GET', $route, [$controller, $action], $middleware);
}

/** @param class-string $controller */
function router_post_action(string $route, string $controller, string $action, array $middleware = []): void
{
    router_register('POST', $route, [$controller, $action], $middleware);
}

function router_put(string $route, string $handler, array $middleware = []): void
{
    router_register('PUT', $route, $handler, $middleware);
}

function router_delete(string $route, string $handler, array $middleware = []): void
{
    router_register('DELETE', $route, $handler, $middleware);
}

function router_resolve_route(): string
{
    $queryRoute = isset($_GET['route']) ? trim((string) $_GET['route'], '/') : '';
    if ($queryRoute !== '') {
        return $queryRoute;
    }

    $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $baseDir = trim(str_replace('index.php', '', $scriptName), '/');

    if ($baseDir !== '' && strpos(trim($uri, '/'), $baseDir) === 0) {
        $uri = substr(trim($uri, '/'), strlen($baseDir));
    }

    $route = trim((string) $uri, '/');

    return $route === '' ? 'home' : $route;
}

function router_is_api_request(): bool
{
    $route = (string) ($_GET['route'] ?? '');
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

    return strpos($route, 'api/') === 0 || stripos($accept, 'application/json') !== false;
}

function router_run_middleware(array $middlewares): void
{
    foreach ($middlewares as $middleware) {
        if ($middleware === 'auth') {
            auth_middleware_handle(router_is_api_request());
        }

        if ($middleware === 'csrf') {
            csrf_validate_post();
        }

        if ($middleware === 'non_admin') {
            role_middleware_require_non_admin();
        }

        if (strpos((string) $middleware, 'role:') === 0) {
            $rolesCsv = substr((string) $middleware, 5);
            $roles = array_values(array_filter(array_map('trim', explode(',', $rolesCsv))));
            if ($roles !== []) {
                role_middleware_require(...$roles);
            }
        }
    }
}

function router_dispatch(): void
{
    if (!isset($GLOBALS['cz_router_routes'])) {
        router_reset();
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $route = router_resolve_route();

    if (!isset($GLOBALS['cz_router_routes'][$method][$route])) {
        error_handler_not_found();

        return;
    }

    $definition = $GLOBALS['cz_router_routes'][$method][$route];
    router_run_middleware($definition['middleware']);

    $handler = $definition['handler'];

    if (is_array($handler) && count($handler) === 2) {
        $class = $handler[0];
        $method = $handler[1];
        if (!empty($GLOBALS['cz_container']) && $GLOBALS['cz_container'] instanceof \CreatorzHive\Core\Container) {
            $controller = $GLOBALS['cz_container']->get($class);
            if (method_exists($controller, $method)) {
                $controller->{$method}();

                return;
            }
        }
        error_handler_not_found('Controller not found: ' . $class . '::' . $method);

        return;
    }

    if (is_string($handler) && function_exists($handler)) {
        $handler();

        return;
    }

    if (is_callable($handler)) {
        $handler();

        return;
    }

    error_handler_not_found('Route handler not found');
}
