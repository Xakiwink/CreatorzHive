#!/usr/bin/env php
<?php

declare(strict_types=1);

/** Generate route → controller action map reference in OOP.md */

$root = dirname(__DIR__);

$controllerMap = [
    'auth_' => 'CreatorzHive\\Controllers\\AuthController',
    'system_' => 'CreatorzHive\\Controllers\\SystemController',
    'dashboard_' => 'CreatorzHive\\Controllers\\DashboardController',
    'posts_' => 'CreatorzHive\\Controllers\\PostController',
    'post_' => 'CreatorzHive\\Controllers\\PostController',
    'media_' => 'CreatorzHive\\Controllers\\MediaController',
    'tag_' => 'CreatorzHive\\Controllers\\TagController',
    'analytics_' => 'CreatorzHive\\Controllers\\AnalyticsController',
    'deals_' => 'CreatorzHive\\Controllers\\DealController',
    'deal_' => 'CreatorzHive\\Controllers\\DealController',
    'invoices_' => 'CreatorzHive\\Controllers\\InvoiceController',
    'notifications_' => 'CreatorzHive\\Controllers\\NotificationController',
    'settings_' => 'CreatorzHive\\Controllers\\SettingsController',
    'admin_' => 'CreatorzHive\\Controllers\\AdminUserController',
    'oauth_' => 'CreatorzHive\\Controllers\\OauthController',
    'api_' => 'CreatorzHive\\Controllers\\ApiMetaController',
];

function route_action(string $handler): ?array
{
    global $controllerMap;
    foreach ($controllerMap as $prefix => $class) {
        if (strpos($handler, $prefix) === 0) {
            $short = substr($handler, strlen($prefix));
            $action = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $short))));

            return [$class, $action];
        }
    }

    return null;
}

function route_transform_file(string $path): string
{
    $code = file_get_contents($path);
    $code = preg_replace_callback(
        "/router_(get|post)\('([^']+)',\s*'([^']+)'/",
        static function (array $m): string {
            $method = $m[1];
            $route = $m[2];
            $handler = $m[3];
            $map = route_action($handler);
            if ($map === null) {
                return $m[0];
            }
            [$class, $action] = $map;

            return "router_{$method}_action('{$route}', {$class}::class, '{$action}'";
        },
        $code
    ) ?? $code;

    return $code;
}

foreach (['backend/routes/web.php', 'backend/routes/api.php'] as $rel) {
    $path = $root . '/' . $rel;
    $out = route_transform_file($path);
    file_put_contents($path, $out);
    echo "Updated {$rel}\n";
}
