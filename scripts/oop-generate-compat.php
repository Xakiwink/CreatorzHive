#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);

function compat_studly(string $name): string
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
}

function compat_camel(string $name): string
{
    return lcfirst(compat_studly($name));
}

$modelMap = [
    'user' => ['class' => 'User', 'prefix' => 'user_'],
    'deal' => ['class' => 'Deal', 'prefix' => 'deal_'],
    'invoice' => ['class' => 'Invoice', 'prefix' => 'invoice_'],
    'tag' => ['class' => 'Tag', 'prefix' => 'tag_'],
    'notification' => ['class' => 'Notification', 'prefix' => 'notification_'],
    'notification_preference' => ['class' => 'NotificationPreference', 'prefix' => 'notification_preference_'],
    'user_preferences' => ['class' => 'UserPreferences', 'prefix' => 'user_preferences_'],
    'user_session' => ['class' => 'UserSession', 'prefix' => 'user_session_'],
    'media_file' => ['class' => 'MediaFile', 'prefix' => 'media_file_'],
    'social_account' => ['class' => 'SocialAccount', 'prefix' => 'social_account_'],
    'job_queue' => ['class' => 'JobQueue', 'prefix' => 'job_queue_'],
    'audit_log' => ['class' => 'AuditLog', 'prefix' => 'audit_log_'],
    'Analytics' => ['class' => 'Analytics', 'prefix' => 'analytics_'],
    'Post' => ['class' => 'Post', 'prefix' => 'post_'],
];

$lines = ["<?php\n\ndeclare(strict_types=1);\n\n// Auto-generated model compat → OOP repositories.\n"];

foreach ($modelMap as $file => $meta) {
    $path = $root . '/backend/models/' . $file . '.php';
    if (!is_file($path)) {
        continue;
    }
    $code = file_get_contents($path);
    preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)/', $code, $matches, PREG_SET_ORDER);
    $repoClass = 'CreatorzHive\\Repositories\\' . $meta['class'] . 'Repository';
    $prefix = $meta['prefix'];

    foreach ($matches as $m) {
        $fn = $m[1];
        $params = $m[2];
        if (strpos($fn, $prefix) !== 0) {
            continue;
        }
        $short = substr($fn, strlen($prefix));
        $method = compat_camel($short);
        $return = ': void';
        if (strpos($params, '):') === false && strpos($code, "function {$fn}") !== false) {
            $return = '';
        }
        $lines[] = "function {$fn}({$params}){$return}";
        $lines[] = "{";
        $lines[] = "    return \\CreatorzHive\\Core\\Application::instance()->get({$repoClass}::class)->{$method}(...func_get_args());";
        $lines[] = "}\n";
    }
}

// analytics_sql_with_platform_filter has no analytics_ duplicate prefix issue - already in map

file_put_contents($root . '/backend/compat/models.php', implode("\n", $lines));
echo "Wrote backend/compat/models.php\n";
