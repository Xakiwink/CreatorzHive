<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/index.php';

if (!(bool) env('APP_DEBUG', false)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit("Set APP_DEBUG=true in .env to use this endpoint.\nDelete this file when debugging is complete.");
}

header('Content-Type: text/plain; charset=utf-8');

$line = static function (string $label, string $value = ''): void {
    echo str_pad($label, 28) . $value . "\n";
};

echo "CreatorzHive — OAuth / Session Diagnostic\n";
echo str_repeat('=', 48) . "\n\n";

echo "[ SESSION ]\n";
$statusMap = [1 => 'NONE', 2 => 'ACTIVE', 3 => 'DISABLED'];
$line('Status', $statusMap[session_status()] ?? '?');
$line('Session ID', session_id() ?: '(none)');
$line('gc_maxlifetime', ini_get('session.gc_maxlifetime'));

$sessionUser = session_get_user();
if ($sessionUser !== null) {
    $line('Has user', 'YES');
    $line('  user.id', (string) ($sessionUser['id'] ?? ''));
    $line('  user.email', (string) ($sessionUser['email'] ?? ''));
    $line('  user.role', (string) ($sessionUser['role'] ?? ''));
} else {
    $line('Has user', 'NO  <-- logged out or session empty');
}
$line('Flash messages', json_encode($_SESSION['_flash'] ?? []));

echo "\n[ PHP_SESSIONS TABLE ]\n";
try {
    $pdo = db_get_pdo();
    $line('DB connection', 'OK  host=' . (string) env('DB_HOST', '?'));

    $exists = $pdo->query("SHOW TABLES LIKE 'php_sessions'")->fetchAll();
    if (count($exists) === 0) {
        $line('php_sessions table', 'MISSING  <-- ROOT CAUSE');
        echo "\nRun this in phpMyAdmin to fix:\n";
        echo "CREATE TABLE `php_sessions` (\n";
        echo "  `id`         VARCHAR(128) NOT NULL,\n";
        echo "  `data`       MEDIUMTEXT   NOT NULL,\n";
        echo "  `expires_at` INT UNSIGNED NOT NULL,\n";
        echo "  PRIMARY KEY (`id`),\n";
        echo "  KEY `idx_expires` (`expires_at`)\n";
        echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    } else {
        $counts = $pdo->query(
            "SELECT COUNT(*) AS total, SUM(expires_at > UNIX_TIMESTAMP()) AS active FROM php_sessions"
        )->fetch();
        $line('php_sessions table', 'EXISTS');
        $line('  rows total', (string) ($counts['total'] ?? 0));
        $line('  rows active', (string) ($counts['active'] ?? 0));

        $sid = session_id();
        if ($sid !== '') {
            $stmt = $pdo->prepare(
                'SELECT LENGTH(data) AS dlen, expires_at, (expires_at - UNIX_TIMESTAMP()) AS ttl
                 FROM php_sessions WHERE id = ?'
            );
            $stmt->execute([$sid]);
            $row = $stmt->fetch();
            if ($row !== false && $row !== null) {
                $line('  current session', 'FOUND');
                $line('  data size', $row['dlen'] . ' bytes');
                $line('  TTL', $row['ttl'] . 's');
            } else {
                $line('  current session', 'NOT IN DB  <-- session not yet written or expired');
            }
        }

        $testId = 'dbg_' . bin2hex(random_bytes(4));
        $wStmt = $pdo->prepare(
            'INSERT INTO php_sessions (id, data, expires_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data)'
        );
        if ($wStmt->execute([$testId, 'test', time() + 60])) {
            $line('Write test', 'OK');
            $pdo->prepare('DELETE FROM php_sessions WHERE id = ?')->execute([$testId]);
        } else {
            $line('Write test', 'FAILED  <-- DB user may lack INSERT permission');
        }
    }
} catch (Throwable $e) {
    $line('DB error', $e->getMessage());
}

echo "\n[ INSTAGRAM CONFIG ]\n";
try {
    $app = \CreatorzHive\Core\Application::instance();
    if ($app !== null) {
        $ig = $app->get(\CreatorzHive\Services\InstagramOAuthService::class);
        $line('Configured', $ig->isConfigured() ? 'YES' : 'NO  <-- App ID or Secret missing');
        $line('Redirect URI', $ig->redirectUri());
    }
} catch (Throwable $e) {
    $line('Error', $e->getMessage());
}

echo "\n[ ENVIRONMENT ]\n";
$line('APP_URL', (string) env('APP_URL', '(not set)'));
$line('APP_DEBUG', env('APP_DEBUG', false) ? 'true' : 'false');
$line('APP_SECRET', env('APP_SECRET', '') !== '' ? '(set, ' . strlen((string) env('APP_SECRET')) . ' chars)' : 'NOT SET');
$line('SESSION_SECURE', env('SESSION_SECURE', false) ? 'true' : 'false');
$line('SESSION_LIFETIME', (string) env('SESSION_LIFETIME', '120 (default)'));

echo "\n";
echo str_repeat('=', 48) . "\n";
echo "DELETE public/debug-oauth.php when debugging is complete.\n";
