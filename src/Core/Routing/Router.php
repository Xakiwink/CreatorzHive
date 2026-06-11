<?php

declare(strict_types=1);

namespace CreatorzHive\Core\Routing;

use CreatorzHive\Core\Application;
use CreatorzHive\Core\Container;

/**
 * HTTP router — registers routes and dispatches to controller methods or callables.
 */
final class Router
{
    /** @var array<string, array<string, array{handler: callable|string|array, middleware: list<string>}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    private bool $apiMode = false;

    public function reset(): void
    {
        $this->routes = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];
        $this->apiMode = false;
    }

    public function apiMode(bool $enabled = true): void
    {
        $this->apiMode = $enabled;
    }

    /**
     * @param callable|string|array{0: class-string, 1: string} $handler
     * @param list<string> $middleware
     */
    public function register(string $method, string $route, $handler, array $middleware = []): void
    {
        $route = trim($route, '/');
        $method = strtoupper($method);
        $this->routes[$method][$route] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $route, $handler, array $middleware = []): void
    {
        $this->register('GET', $route, $handler, $middleware);
    }

    public function post(string $route, $handler, array $middleware = []): void
    {
        $this->register('POST', $route, $handler, $middleware);
    }

    public function dispatch(Container $container): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $route = $this->resolveRoute();

        if (!isset($this->routes[$method][$route])) {
            if (function_exists('error_handler_not_found')) {
                error_handler_not_found();
            }

            return;
        }

        $definition = $this->routes[$method][$route];
        $this->runMiddleware($definition['middleware']);

        $handler = $definition['handler'];
        if (is_array($handler) && count($handler) === 2) {
            $class = $handler[0];
            $methodName = $handler[1];
            $controller = $container->get($class);
            if (!method_exists($controller, $methodName)) {
                if (function_exists('error_handler_not_found')) {
                    error_handler_not_found('Controller method not found');
                }

                return;
            }
            $controller->{$methodName}();

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

        if (function_exists('error_handler_not_found')) {
            error_handler_not_found('Route handler not found');
        }
    }

    public function resolveRoute(): string
    {
        if (function_exists('router_resolve_route')) {
            return router_resolve_route();
        }

        $queryRoute = isset($_GET['route']) ? trim((string) $_GET['route'], '/') : '';
        if ($queryRoute !== '') {
            return $queryRoute;
        }

        return 'login';
    }

    /**
     * @param list<string> $middlewares
     */
    private function runMiddleware(array $middlewares): void
    {
        if (function_exists('router_run_middleware')) {
            router_run_middleware($middlewares);

            return;
        }

        foreach ($middlewares as $middleware) {
            if ($middleware === 'auth' && function_exists('auth_middleware_handle')) {
                auth_middleware_handle($this->isApiRequest());
            }
        }
    }

    private function isApiRequest(): bool
    {
        if (function_exists('router_is_api_request')) {
            return router_is_api_request();
        }

        return false;
    }
}
