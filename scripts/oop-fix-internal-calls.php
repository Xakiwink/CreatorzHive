#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);

function fix_camel(string $name): string
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
}

$map = [
    'user' => 'user_',
    'deal' => 'deal_',
    'invoice' => 'invoice_',
    'tag' => 'tag_',
    'notification' => 'notification_',
    'notification_preference' => 'notification_preference_',
    'user_preferences' => 'user_preferences_',
    'user_session' => 'user_session_',
    'media_file' => 'media_file_',
    'social_account' => 'social_account_',
    'job_queue' => 'job_queue_',
    'audit_log' => 'audit_log_',
    'Analytics' => 'analytics_',
    'Post' => 'post_',
];

$classNames = [
    'user' => 'User',
    'deal' => 'Deal',
    'invoice' => 'Invoice',
    'tag' => 'Tag',
    'notification' => 'Notification',
    'notification_preference' => 'NotificationPreference',
    'user_preferences' => 'UserPreferences',
    'user_session' => 'UserSession',
    'media_file' => 'MediaFile',
    'social_account' => 'SocialAccount',
    'job_queue' => 'JobQueue',
    'audit_log' => 'AuditLog',
    'Analytics' => 'Analytics',
    'Post' => 'Post',
];

foreach ($map as $file => $prefix) {
    $repoPath = $root . '/src/Repositories/' . $classNames[$file] . 'Repository.php';
    if (!is_file($repoPath)) {
        continue;
    }
    $code = file_get_contents($repoPath);
    preg_match_all('/function\s+' . preg_quote($prefix, '/') . '([a-zA-Z0-9_]+)\s*\(/', file_get_contents($root . '/backend/models/' . $file . '.php'), $fns);
    foreach (array_unique($fns[1] ?? []) as $short) {
        $fn = $prefix . $short;
        $method = fix_camel($short);
        $code = preg_replace('/\b' . preg_quote($fn, '/') . '\s*\(/', '$this->' . $method . '(', $code) ?? $code;
    }
    // platform helpers
    $code = str_replace('platform_normalize_slug(', '\\CreatorzHive\\Helpers\\PlatformHelper::normalize(', $code);
    $code = str_replace('analytics_normalize_platform_filter(', '\\CreatorzHive\\Helpers\\PlatformHelper::normalize(', $code);
    $code = str_replace('analytics_sql_with_platform_filter(', '$this->sqlWithPlatformFilter(', $code);
    file_put_contents($repoPath, $code);
    echo "Fixed {$classNames[$file]}Repository\n";
}
