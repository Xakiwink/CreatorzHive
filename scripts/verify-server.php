#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Quick server checks (DB, uploads dir, optional HTTP ping).
 *   php scripts/verify-server.php
 *   php scripts/verify-server.php http://localhost/creatorzhive
 */

require_once __DIR__ . '/../backend/helpers/cli_bootstrap.php';
cli_load_env();
cli_boot_oop(false);

$ok = true;

echo "=== CreatorzHive server verify ===\n";

$db = (string) env('DB_DATABASE', '');
echo "DB_DATABASE={$db}\n";
try {
    $row = db_fetch('SELECT 1 AS ok');
    echo 'database: OK (' . ($row['ok'] ?? '?') . ")\n";
} catch (\Throwable $e) {
    echo 'database: FAIL — ' . $e->getMessage() . "\n";
    $ok = false;
}

$avatarDir = public_path('uploads/avatars');
echo 'uploads/avatars: ' . $avatarDir . "\n";
if (!is_dir($avatarDir)) {
    @mkdir($avatarDir, 0775, true);
}
if (!is_dir($avatarDir)) {
    echo "uploads/avatars: FAIL (cannot create)\n";
    $ok = false;
} elseif (!is_writable($avatarDir)) {
    echo "uploads/avatars: FAIL (not writable by " . get_current_user() . ")\n";
    $ok = false;
} else {
    echo "uploads/avatars: OK (writable)\n";
}

$tmp = $avatarDir . '/.write-test-' . bin2hex(random_bytes(4));
if (@file_put_contents($tmp, '1') !== false) {
    @unlink($tmp);
    echo "uploads/avatars: write probe OK\n";
} else {
    echo "uploads/avatars: FAIL (write probe)\n";
    $ok = false;
}

$warnApacheWrite = static function (string $dir, string $label): void {
    if (!is_dir($dir)) {
        return;
    }
    $perms = fileperms($dir) & 07777;
    $otherWrite = ($perms & 0002) !== 0;
    $ownerWritableByApache = false;
    if (\function_exists('posix_getpwuid')) {
        $st = @stat($dir);
        if ($st !== false && isset($st['uid'])) {
            $pw = @posix_getpwuid((int) $st['uid']);
            $name = is_array($pw) ? (string) ($pw['name'] ?? '') : '';
            if ($name === 'www-data' || $name === 'apache' || $name === 'http') {
                $ownerWritableByApache = true;
            }
        }
    }
    if (!$otherWrite && !$ownerWritableByApache) {
        echo "WARNING: {$label} may block Apache (www-data): mode " . sprintf('%o', $perms) . " — run: chmod o+w {$dir}\n";
        echo "          or: sudo chown -R www-data:www-data {$dir}\n";
    }
};

$warnApacheWrite($avatarDir, 'uploads/avatars');
$warnApacheWrite(storage_path('logs'), 'backend/storage/logs');

$base = $argv[1] ?? '';
if ($base !== '') {
    $base = rtrim($base, '/');
    $url = $base . '/?route=ping';
    echo "HTTP GET {$url}\n";
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        echo "HTTP: FAIL (no response)\n";
        $ok = false;
    } else {
        echo 'HTTP: ' . substr($body, 0, 200) . (strlen($body) > 200 ? '...' : '') . "\n";
    }
}

exit($ok ? 0 : 1);
