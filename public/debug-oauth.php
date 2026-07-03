<?php

declare(strict_types=1);

/**
 * OAuth / Session diagnostic — bypasses the router entirely.
 * Requires APP_DEBUG=true in .env.
 * DELETE THIS FILE after debugging is complete.
 */

$backendDir = dirname(__DIR__) . '/backend';
$rootDir    = dirname(__DIR__);

// Load env first so we can check APP_DEBUG before doing anything else
require_once $backendDir . '/helpers/functions.php';
load_env($rootDir . '/.env');

if (!(bool) env('APP_DEBUG', false)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit("Set APP_DEBUG=true in .env to use this endpoint.\nDelete this file when done.");
}

// Bootstrap without calling router_dispatch()
require_once $backendDir . '/config/app.php';

$autoload = $rootDir . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

// Boot OOP container (needed for DB, Instagram service, etc.)
if (class_exists(\CreatorzHive\Core\Application::class)) {
    \CreatorzHive\Core\Application::boot();
}

// Load procedural layer (database, session handler, etc.) - NOT router_dispatch
require_once $backendDir . '/compat/models.php';
require_once $backendDir . '/compat/services.php';
require_once $backendDir . '/compat/auth.php';
require_once $backendDir . '/helpers/platforms.php';
require_once $backendDir . '/core/database.php';
require_once $backendDir . '/core/db-session-handler.php';
require_once $backendDir . '/core/session.php';
require_once $backendDir . '/core/response.php';
require_once $backendDir . '/core/request.php';

session_start_safe();

header('Content-Type: text/plain; charset=utf-8');

$pad = static function (string $label, string $value = ''): void {
    echo str_pad($label, 30) . $value . "\n";
};

echo "CreatorzHive — OAuth / Session Diagnostic\n";
echo str_repeat('=', 50) . "\n\n";

// ── SESSION ──────────────────────────────────────────
echo "[ SESSION ]\n";
$statusLabels = [1 => 'NONE', 2 => 'ACTIVE', 3 => 'DISABLED'];
$pad('Status', $statusLabels[session_status()] ?? '?');
$pad('Session ID', session_id() ?: '(none)');
$pad('gc_maxlifetime (php.ini)', ini_get('session.gc_maxlifetime'));

$sessionUser = session_get_user();
if ($sessionUser !== null) {
    $pad('Has user', 'YES');
    $pad('  id',    (string) ($sessionUser['id']    ?? ''));
    $pad('  email', (string) ($sessionUser['email'] ?? ''));
    $pad('  role',  (string) ($sessionUser['role']  ?? ''));
} else {
    $pad('Has user', 'NO  <-- not logged in, or session data missing');
}
$pad('Flash data', json_encode($_SESSION['_flash'] ?? []));

// ── PHP_SESSIONS TABLE ───────────────────────────────
echo "\n[ PHP_SESSIONS TABLE ]\n";
try {
    $pdo = db_get_pdo();
    $pad('DB connection', 'OK  host=' . (string) env('DB_HOST', '?'));

    $tableCheck = $pdo->query("SHOW TABLES LIKE 'php_sessions'")->fetchAll();

    if (count($tableCheck) === 0) {
        $pad('Table php_sessions', 'MISSING  <-- THIS IS THE ROOT CAUSE');
        echo "\nFix: run this SQL in phpMyAdmin:\n\n";
        echo "CREATE TABLE `php_sessions` (\n";
        echo "  `id`         VARCHAR(128) NOT NULL,\n";
        echo "  `data`       MEDIUMTEXT   NOT NULL,\n";
        echo "  `expires_at` INT UNSIGNED NOT NULL,\n";
        echo "  PRIMARY KEY (`id`),\n";
        echo "  KEY `idx_expires` (`expires_at`)\n";
        echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    } else {
        $pad('Table php_sessions', 'EXISTS');

        $counts = $pdo->query(
            "SELECT COUNT(*) AS total,
                    SUM(expires_at > UNIX_TIMESTAMP()) AS active
             FROM php_sessions"
        )->fetch(PDO::FETCH_ASSOC);
        $pad('  rows total',  (string) ($counts['total']  ?? 0));
        $pad('  rows active', (string) ($counts['active'] ?? 0));

        $sid = session_id();
        if ($sid !== '') {
            $stmt = $pdo->prepare(
                'SELECT LENGTH(data) AS dlen,
                        expires_at,
                        (expires_at - UNIX_TIMESTAMP()) AS ttl
                 FROM php_sessions WHERE id = ?'
            );
            $stmt->execute([$sid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $pad('  current session row', 'FOUND');
                $pad('  data size',           $row['dlen'] . ' bytes');
                $pad('  TTL',                 $row['ttl']  . 's');
            } else {
                $pad('  current session row', 'NOT FOUND  <-- write is failing or expired');
            }
        }

        // Quick write/read test
        $testId = 'dbg_' . bin2hex(random_bytes(4));
        $wOk = $pdo->prepare(
            'INSERT INTO php_sessions (id, data, expires_at)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)'
        )->execute([$testId, 'test', time() + 60]);

        $pad('Write test', $wOk ? 'OK' : 'FAILED  <-- DB user may lack INSERT permission');
        if ($wOk) {
            $pdo->prepare('DELETE FROM php_sessions WHERE id = ?')->execute([$testId]);
        }
    }
} catch (Throwable $e) {
    $pad('DB error', $e->getMessage());
}

// ── INSTAGRAM CONFIG ─────────────────────────────────
echo "\n[ INSTAGRAM CONFIG ]\n";
try {
    $app = \CreatorzHive\Core\Application::instance();
    if ($app !== null) {
        $ig = $app->get(\CreatorzHive\Services\InstagramOAuthService::class);
        $pad('Configured',   $ig->isConfigured() ? 'YES' : 'NO  <-- App ID or Secret missing');
        $pad('Redirect URI', $ig->redirectUri());
    }
} catch (Throwable $e) {
    $pad('Error', $e->getMessage());
}

// ── ENVIRONMENT ──────────────────────────────────────
echo "\n[ ENVIRONMENT ]\n";
$pad('APP_URL',         (string) env('APP_URL',         '(not set)'));
$pad('APP_DEBUG',       env('APP_DEBUG', false) ? 'true' : 'false');
$pad('APP_SECRET',      env('APP_SECRET', '') !== '' ? '(set, ' . strlen((string) env('APP_SECRET')) . ' chars)' : 'NOT SET');
$pad('SESSION_SECURE',  env('SESSION_SECURE', false) ? 'true' : 'false');
$pad('SESSION_LIFETIME', (string) env('SESSION_LIFETIME', '120 (default)'));
$pad('INSTAGRAM_OAUTH_REDIRECT_URI', (string) env('INSTAGRAM_OAUTH_REDIRECT_URI', '(auto-built from APP_URL)'));

echo "\n" . str_repeat('=', 50) . "\n";
echo "DELETE public/debug-oauth.php when debugging is complete.\n";
