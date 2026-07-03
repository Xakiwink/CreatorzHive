<?php

declare(strict_types=1);

$rootDir    = __DIR__;
$backendDir = $rootDir . '/backend';

require_once $backendDir . '/helpers/functions.php';
load_env($rootDir . '/.env');

$debug = (bool) env('APP_DEBUG', false);

require_once $backendDir . '/config/app.php';

$autoload = $rootDir . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists(\CreatorzHive\Core\Application::class)) {
    \CreatorzHive\Core\Application::boot();
}

require_once $backendDir . '/compat/models.php';
require_once $backendDir . '/compat/services.php';
require_once $backendDir . '/compat/auth.php';
require_once $backendDir . '/helpers/platforms.php';
require_once $backendDir . '/core/database.php';
require_once $backendDir . '/core/db-session-handler.php';
require_once $backendDir . '/core/session.php';
require_once $backendDir . '/core/response.php';
require_once $backendDir . '/core/request.php';
require_once $backendDir . '/core/job_runner.php';

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

session_start_safe();

$cbError = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
$cbState = trim((string) ($_GET['state'] ?? ''));
$cbCode  = trim((string) ($_GET['code'] ?? ''));

if ($cbError !== '') {
    session_flash('oauth_error', 'Instagram authorization was denied.');
    if ($debug) {
        igCbDebug('instagram_denied', ['error' => $cbError]);
    }
    response_redirect(route_url('settings-integrations'));
}

$appSecret = trim((string) env('APP_SECRET', ''));
$userId    = null;
$parts     = explode('.', $cbState, 3);

if (count($parts) === 3) {
    [$userIdStr, $nonce, $sig] = $parts;
    $expected = hash_hmac('sha256', $userIdStr . '.' . $nonce, $appSecret);
    if (hash_equals($expected, $sig) && (int) $userIdStr > 0) {
        $userId = (int) $userIdStr;
    }
}

if ($userId === null) {
    session_flash('oauth_error', 'Invalid OAuth state. Please try connecting again.');
    if ($debug) {
        igCbDebug('state_invalid', [
            'state_len'    => strlen($cbState),
            'parts'        => count($parts),
            'secret_set'   => $appSecret !== '' ? 'YES (' . strlen($appSecret) . ' chars)' : 'NO',
            'session_user' => session_get_user() !== null ? 'YES' : 'NO',
        ]);
    }
    response_redirect(route_url('settings-integrations'));
}

try {
    $user = db_fetch(
        'SELECT id, name, username, email, role, is_active FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
        [$userId]
    );
} catch (\Throwable $e) {
    $user = null;
}

if ($user !== null) {
    session_set_user($user);
}

if ($cbCode === '') {
    session_flash('oauth_error', 'Authorization code missing from Instagram response.');
    if ($debug) {
        igCbDebug('no_code', [
            'userId'       => $userId,
            'user_in_db'   => $user !== null ? 'YES' : 'NO',
            'session_user' => session_get_user() !== null ? 'YES' : 'NO',
        ]);
    }
    response_redirect(route_url('settings-integrations'));
}

$result = ['success' => false, 'message' => 'Service unavailable'];
try {
    $app = \CreatorzHive\Core\Application::instance();
    if ($app !== null) {
        $igService = $app->get(\CreatorzHive\Services\InstagramOAuthService::class);
        $result    = $igService->completeConnection($userId, $cbCode);
    }
} catch (\Throwable $e) {
    $result = ['success' => false, 'message' => $e->getMessage()];
}

if ($result['success']) {
    session_flash('oauth_success', (string) $result['message']);
} else {
    session_flash('oauth_error', (string) $result['message']);
}

if ($debug) {
    $finalSid  = session_id();
    $finalUser = session_get_user();

    session_write_close();

    $dbRow = null;
    try {
        $dbRow = db_fetch(
            'SELECT LENGTH(data) AS dlen, (expires_at - UNIX_TIMESTAMP()) AS ttl FROM php_sessions WHERE id = ?',
            [$finalSid]
        );
    } catch (\Throwable $ignored) {
    }

    igCbDebug('complete', [
        'session_id'   => $finalSid,
        'session_user' => $finalUser !== null ? 'YES id=' . ($finalUser['id'] ?? '?') : 'NO',
        'db_session'   => $dbRow !== null
            ? 'FOUND ' . (int) ($dbRow['dlen'] ?? 0) . 'B TTL=' . (int) ($dbRow['ttl'] ?? 0) . 's'
            : 'NOT FOUND — session was NOT written!',
        'result'       => $result,
    ]);
}

response_redirect(route_url('settings-integrations'));

function igCbDebug(string $step, array $info): void
{
    header('Content-Type: text/html; charset=utf-8');

    echo '<pre style="font:14px monospace;padding:20px 24px;background:#0d1117;color:#58a6ff;line-height:1.7">';
    echo "<b style='color:#f0f6fc;font-size:16px'>CreatorzHive — ig-cb.php OAuth Debug</b>\n\n";
    echo "<b>Step :</b> " . htmlspecialchars($step, ENT_QUOTES, 'UTF-8') . "\n";

    foreach ($info as $k => $v) {
        $label = str_pad(htmlspecialchars($k, ENT_QUOTES, 'UTF-8'), 14);
        $val   = is_string($v) ? $v : json_encode($v);

        $color = '';
        if (is_string($val) && (stripos($val, 'NO') === 0 || stripos($val, 'NOT') === 0 || stripos($val, 'FAIL') === 0)) {
            $color = ' style="color:#f85149"';
        } elseif (is_string($val) && (stripos($val, 'YES') === 0 || stripos($val, 'FOUND') === 0 || stripos($val, 'success') !== false)) {
            $color = ' style="color:#3fb950"';
        }

        echo "<b>$label:</b><span$color> " . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "</span>\n";
    }

    $nextUrl = route_url('settings-integrations');
    echo "\n<a href=\"" . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . "\" "
        . "style=\"color:#e3b341;font-weight:bold;font-size:16px\">&#8594; Continue to Settings</a>\n";
    echo '</pre>';

    exit;
}
