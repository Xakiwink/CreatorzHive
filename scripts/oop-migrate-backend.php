#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Generates OOP repositories/services/controllers from procedural backend files.
 * Run: php scripts/oop-migrate-backend.php
 */

$root = dirname(__DIR__);

function oop_studly(string $name): string
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
}

function oop_camel(string $name): string
{
    $s = oop_studly($name);

    return lcfirst($s);
}

function oop_transform_body(string $body, bool $isHandler): string
{
    $body = preg_replace('/\bdb_fetchAll\(/', '$this->db->fetchAll(', $body) ?? $body;
    $body = preg_replace('/\bdb_fetch_all\(/', '$this->db->fetchAll(', $body) ?? $body;
    $body = preg_replace('/\bdb_fetch\(/', '$this->db->fetchOne(', $body) ?? $body;
    $body = preg_replace('/\bdb_query\(/', '$this->db->query(', $body) ?? $body;
    $body = preg_replace('/\bdb_insert\(/', '$this->db->insert(', $body) ?? $body;
    $body = preg_replace('/\bdb_update\(/', '$this->db->update(', $body) ?? $body;
    $body = preg_replace('/\bdb_delete\(/', '$this->db->delete(', $body) ?? $body;
    $body = preg_replace('/\bdb_bind_limit_offset\(/', '$this->db->bindLimitOffset(', $body) ?? $body;
    $body = preg_replace('/\bdb_bind_limit\(/', '$this->db->bindLimit(', $body) ?? $body;
    $body = preg_replace('/\bdb_in_int_placeholders\(/', '$this->db->inIntPlaceholders(', $body) ?? $body;
    $body = preg_replace('/\bdb_quote_column\(/', '$this->db->quoteColumn(', $body) ?? $body;
    $body = preg_replace('/\bdb_quote_table\(/', '$this->db->quoteTable(', $body) ?? $body;
    $body = preg_replace('/\bdb_sql_sort_direction\(/', '$this->db->sortDirection(', $body) ?? $body;
    $body = preg_replace('/\bdb_last_insert_id\(/', '$this->db->pdo()->lastInsertId(', $body) ?? $body;
    $body = preg_replace('/\bdb_get_pdo\(\)/', '$this->db->pdo()', $body) ?? $body;

    if ($isHandler) {
        $body = preg_replace('/\bhttp_json_success\(/', '$this->json->success(', $body) ?? $body;
        $body = preg_replace('/\bhttp_json_error\(/', '$this->json->error(', $body) ?? $body;
        $body = preg_replace('/\bhttp_view\(/', '$this->views->render(', $body) ?? $body;
        $body = preg_replace('/\bhttp_redirect\(/', '$this->redirect(', $body) ?? $body;
    }

    return $body;
}

/**
 * @return list<array{name: string, body: string}>
 */
function oop_extract_functions(string $code): array
{
    $tokens = token_get_all($code);
    $functions = [];
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if ($j >= $count || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
            continue;
        }
        $fnName = $tokens[$j][1];
        $j++;
        while ($j < $count && $tokens[$j] !== '{') {
            $j++;
        }
        if ($j >= $count) {
            continue;
        }
        $depth = 0;
        $start = $j;
        for (; $j < $count; $j++) {
            $t = $tokens[$j];
            if ($t === '{') {
                $depth++;
            } elseif ($t === '}') {
                $depth--;
                if ($depth === 0) {
                    $body = '';
                    for ($k = $start + 1; $k < $j; $k++) {
                        $body .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
                    }
                    $functions[] = ['name' => $fnName, 'body' => trim($body)];
                    break;
                }
            }
        }
        $i = $j;
    }

    return $functions;
}

function oop_write_file(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
}

// --- Repositories from models ---
$modelMap = [
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

foreach ($modelMap as $file => $class) {
    $path = $root . '/backend/models/' . $file . '.php';
    if (!is_file($path)) {
        continue;
    }
    $code = file_get_contents($path);
    $functions = oop_extract_functions($code);
    $methods = '';
    foreach ($functions as $fn) {
        $short = $fn['name'];
        if (strpos($short, '_') !== false) {
            $parts = explode('_', $short, 2);
            $short = isset($parts[1]) ? $parts[1] : $short;
        }
        $methodName = oop_camel($short);
        $body = oop_transform_body($fn['body'], false);
        $methods .= "\n    public function {$methodName}(";
        // copy signature from original - simplified: parse from file
        if (preg_match('/function\s+' . preg_quote($fn['name'], '/') . '\s*\(([^)]*)\)/', $code, $m)) {
            $methods = rtrim($methods, '(') . '(' . $m[1] . ")\n    {\n        " . str_replace("\n", "\n        ", $body) . "\n    }\n";
        }
    }

    $out = <<<PHP
<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class {$class}Repository
{
    /** @var Connection */
    private \$db;

    public function __construct(Connection \$db)
    {
        \$this->db = \$db;
    }
{$methods}
}

PHP;
    oop_write_file($root . '/src/Repositories/' . $class . 'Repository.php', $out);
    echo "Repository: {$class}Repository\n";
}

// --- Services ---
$serviceFiles = glob($root . '/backend/services/*.php') ?: [];
foreach ($serviceFiles as $path) {
    $base = basename($path, '.php');
    $class = oop_studly(str_replace('_service', '', $base));
    if ($class === 'PlatformApiSecrets') {
        $class = 'PlatformApiSecrets';
    }
    $code = file_get_contents($path);
    $functions = oop_extract_functions($code);
    $methods = '';
    $prefix = $base . '_';
    if ($base === 'auth_service') {
        $prefix = 'auth_service_';
        $class = 'Auth';
    } elseif ($base === 'admin_service') {
        $prefix = 'admin_service_';
        $class = 'Admin';
    } elseif ($base === 'analytics_service') {
        $prefix = 'analytics_service_';
        $class = 'Analytics';
    } elseif ($base === 'notification_service') {
        $prefix = 'notification_service_';
        $class = 'Notification';
    } elseif ($base === 'social_api_service') {
        $prefix = 'social_api_service_';
        $class = 'SocialApi';
    } elseif ($base === 'meta_oauth_service') {
        $prefix = 'meta_oauth_';
        $class = 'MetaOAuth';
    } elseif ($base === 'platform_api_secrets_service') {
        $prefix = 'platform_api_secrets_';
        $class = 'PlatformApiSecrets';
    }

    foreach ($functions as $fn) {
        $name = $fn['name'];
        $short = $name;
        if (strpos($name, $prefix) === 0) {
            $short = substr($name, strlen($prefix));
        }
        $methodName = oop_camel($short);
        $body = oop_transform_body($fn['body'], false);
        if (preg_match('/function\s+' . preg_quote($name, '/') . '\s*\(([^)]*)\)/', $code, $m)) {
            $methods .= "\n    public function {$methodName}({$m[1]})\n    {\n        " . str_replace("\n", "\n        ", $body) . "\n    }\n";
        }
    }

    $out = <<<PHP
<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Core\Database\Connection;

final class {$class}Service
{
    /** @var Connection */
    private \$db;

    public function __construct(Connection \$db)
    {
        \$this->db = \$db;
    }
{$methods}
}

PHP;
    oop_write_file($root . '/src/Services/' . $class . 'Service.php', $out);
    echo "Service: {$class}Service\n";
}

// --- Controllers from handlers ---
$handlerFiles = glob($root . '/backend/handlers/*.php') ?: [];
foreach ($handlerFiles as $path) {
    $base = basename($path, '.php');
    if ($base === 'dashboard') {
        continue;
    }
    $class = oop_studly($base) . 'Controller';
    $code = file_get_contents($path);
    $functions = oop_extract_functions($code);
    $methods = '';
    $routeMap = [];

    foreach ($functions as $fn) {
        $name = $fn['name'];
        if (strpos($name, '_handler_') !== false || strpos($name, 'handler_') !== false) {
            continue;
        }
        $short = $name;
        $prefix = $base . '_';
        if (strpos($name, $prefix) === 0) {
            $short = substr($name, strlen($prefix));
        } elseif (strpos($name, $base) === 0) {
            $short = substr($name, strlen($base));
            $short = ltrim($short, '_');
        }
        $methodName = oop_camel($short);
        if ($methodName === '') {
            $methodName = oop_camel($name);
        }
        $body = oop_transform_body($fn['body'], true);
        if (preg_match('/function\s+' . preg_quote($name, '/') . '\s*\(([^)]*)\)/', $code, $m)) {
            $methods .= "\n    public function {$methodName}({$m[1]})\n    {\n        " . str_replace("\n", "\n        ", $body) . "\n    }\n";
            $routeMap[$name] = $methodName;
        }
    }

    $out = <<<PHP
<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;

final class {$class} extends AbstractController
{
{$methods}
}

PHP;
    oop_write_file($root . '/src/Controllers/' . $class . '.php', $out);
    echo "Controller: {$class}\n";
}

echo "Done.\n";
