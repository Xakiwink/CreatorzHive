#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

function svc_compat_studly(string $name): string
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
}

function svc_compat_camel(string $name): string
{
    return lcfirst(svc_compat_studly($name));
}

/** @return array<string, array{class: string, prefix: string}> */
function svc_compat_map(): array
{
    return [
        'admin_service.php' => ['class' => 'AdminService', 'prefix' => 'admin_service_'],
        'meta_oauth_service.php' => ['class' => 'MetaOAuthService', 'prefix' => 'meta_oauth_'],
        'platform_api_secrets_service.php' => ['class' => 'PlatformApiSecretsService', 'prefix' => 'platform_api_secrets_'],
        'analytics_service.php' => ['class' => 'AnalyticsService', 'prefix' => 'analytics_service_'],
        'social_api_service.php' => ['class' => 'SocialApiService', 'prefix' => 'social_api_service_'],
        'notification_service.php' => ['class' => 'NotificationService', 'prefix' => 'notification_service_'],
    ];
}

$lines = [
    "<?php\n",
    "declare(strict_types=1);\n",
    "\n// Auto-generated service compat → OOP services.\n",
];

foreach (svc_compat_map() as $file => $meta) {
    $path = $root . '/backend/services/' . $file;
    if (!is_file($path)) {
        continue;
    }
    $serviceClass = 'CreatorzHive\\Services\\' . $meta['class'];
    $reflection = new ReflectionClass($serviceClass);
    $methods = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->getName() !== '__construct') {
            $methods[strtolower($m->getName())] = $m->getName();
        }
    }

    $code = file_get_contents($path);
    preg_match_all('/function\s+([a-z][a-z0-9_]*)\s*\(([^)]*)\)/', $code, $matches, PREG_SET_ORDER);
    $prefix = $meta['prefix'];

    foreach ($matches as $m) {
        $fn = $m[1];
        $params = $m[2];
        if (strpos($fn, $prefix) !== 0) {
            continue;
        }
        $short = substr($fn, strlen($prefix));
        $method = $methods[strtolower(svc_compat_camel($short))] ?? null;
        if ($method === null) {
            fwrite(STDERR, "Skip {$fn}: no method on {$meta['class']}\n");
            continue;
        }
        $lines[] = "function {$fn}({$params})";
        $lines[] = '{';
        $lines[] = "    return \\CreatorzHive\\Core\\Application::instance()->get({$serviceClass}::class)->{$method}(...func_get_args());";
        $lines[] = "}\n";
    }
}

$out = $root . '/backend/compat/services.php';
file_put_contents($out, implode("\n", $lines));
echo "Wrote {$out}\n";
