#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = array_merge(
    glob($root . '/src/Controllers/*.php') ?: [],
    glob($root . '/src/Services/*.php') ?: [],
    glob($root . '/src/Repositories/*.php') ?: []
);

$prefixes = [
    'auth_', 'auth_service_', 'user_', 'session_', 'validator_', 'request_',
    'db_', 'post_', 'deal_', 'notification_', 'settings_', 'admin_',
    'media_', 'media_file_', 'tag_', 'invoice_', 'analytics_', 'analytics_service_',
    'platform_', 'job_', 'job_queue_', 'mailer_', 'csrf_', 'role_',
    'error_handler_', 'env', 'now', 'base_url', 'route_url', 'upload_url',
    'frontend_', 'api_cors_', 'meta_oauth_', 'social_', 'social_api_service_',
    'notification_service_', 'admin_service_', 'platform_api_secrets_',
    'audit_log_', 'user_preferences_', 'user_session_', 'notification_preference_',
    'http_', 'public_path', 'array_merge', 'array_values', 'array_filter',
    'array_map', 'in_array', 'strtolower', 'trim', 'json_encode', 'json_decode',
    'preg_match', 'preg_replace', 'sprintf', 'strlen', 'mb_strlen', 'hash',
    'password_hash', 'password_verify', 'strtotime', 'date', 'gmdate',
    'filter_var', 'is_array', 'is_file', 'file_get_contents', 'move_uploaded_file',
    'bin2hex', 'random_bytes', 'unlink', 'header', 'http_response_code',
];

foreach ($files as $path) {
    $code = file_get_contents($path);
    foreach ($prefixes as $prefix) {
        if (substr($prefix, -1) === '_') {
            $code = preg_replace(
                '/(?<!\\\\)(?<![\$a-zA-Z0-9_])(' . preg_quote($prefix, '/') . '[a-z0-9_]+)\s*\(/',
                '\\\\$1(',
                $code
            ) ?? $code;
        } else {
            $code = preg_replace(
                '/(?<!\\\\)(?<![\$a-zA-Z0-9_])' . preg_quote($prefix, '/') . '\s*\(/',
                '\\' . $prefix . '(',
                $code
            ) ?? $code;
        }
    }
    file_put_contents($path, $code);
}

echo "Qualified global calls.\n";
