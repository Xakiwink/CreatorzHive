<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private static array $routes = [];

    public static function get(string $page, callable $handler): void
    {
        self::$routes['GET'][$page] = $handler;
    }

    public static function post(string $page, callable $handler): void
    {
        self::$routes['POST'][$page] = $handler;
    }

    public static function dispatch(): void
    {
        $method  = Request::method();
        $page    = Request::page();

        $handler = self::$routes[$method][$page]
            ?? self::$routes['GET'][$page]
            ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '<p style="font-family:sans-serif;padding:2rem">Page not found.</p>';
            return;
        }

        $handler();
    }
}
