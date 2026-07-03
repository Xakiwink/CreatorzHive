<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\InstagramOAuthService;
use function env;
use function response_redirect;
use function route_url;
use function session_flash;
use function session_get_user;
use function session_set_user;
use function session_write_close;

final class InstagramOAuthController extends AbstractController
{
    /** @var InstagramOAuthService */
    private $instagramOAuth;

    /** @var AdminService */
    private $admin;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        InstagramOAuthService $instagramOAuth,
        AdminService $admin
    ) {
        parent::__construct($views, $json, $db);
        $this->instagramOAuth = $instagramOAuth;
        $this->admin          = $admin;
    }

    public function connectStart(): void
    {
        $user = session_get_user();
        if ($user === null) {
            response_redirect(route_url('login'));
        }

        if ((string) ($user['role'] ?? '') === 'admin') {
            session_flash('oauth_error', 'Admins cannot connect creator social accounts. Use a creator account.');
            response_redirect(route_url('settings-integrations'));
        }

        if (!$this->admin->integrationEnabled('instagram')) {
            session_flash('oauth_error', 'Instagram integration is disabled by admin.');
            response_redirect(route_url('settings-integrations'));
        }

        if (!$this->instagramOAuth->isConfigured()) {
            session_flash(
                'oauth_error',
                'Instagram App ID and App Secret must be configured by an admin before connecting.'
            );
            response_redirect(route_url('settings-integrations'));
        }

        $userId = (int) ($user['id'] ?? 0);
        $state  = $this->buildState($userId);

        response_redirect($this->instagramOAuth->authorizeUrl($state));
    }

    public function callbackHandler(): void
    {
        $error = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
        if ($error !== '') {
            session_flash('oauth_error', 'Instagram authorization was denied or failed.');
            $this->callbackDebug('instagram_denied', ['instagram_error' => $error]);
            response_redirect(route_url('settings-integrations'));
        }

        $state  = trim((string) ($_GET['state'] ?? ''));
        $userId = $this->verifyState($state);

        if ($userId === null) {
            session_flash('oauth_error', 'Invalid OAuth state. Please try connecting again.');
            $this->callbackDebug('state_invalid', ['state_len' => strlen($state), 'state_prefix' => substr($state, 0, 20)]);
            response_redirect(route_url('settings-integrations'));
        }

        $user = $this->db->fetchOne(
            'SELECT id, name, username, email, role, is_active FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$userId]
        );
        if ($user !== null) {
            session_set_user($user);
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            session_flash('oauth_error', 'Authorization code missing from Instagram response.');
            $this->callbackDebug('no_code', ['userId' => $userId, 'user_in_db' => $user !== null]);
            response_redirect(route_url('settings-integrations'));
        }

        $result = $this->instagramOAuth->completeConnection($userId, $code);

        if ($result['success']) {
            session_flash('oauth_success', (string) $result['message']);
        } else {
            session_flash('oauth_error', (string) $result['message']);
        }

        $this->callbackDebug('complete', ['userId' => $userId, 'user_in_db' => $user !== null, 'result' => $result]);
        response_redirect(route_url('settings-integrations'));
    }

    private function callbackDebug(string $step, array $extra = []): void
    {
        if (!(bool) env('APP_DEBUG', false)) {
            return;
        }

        $sid      = session_id();
        $sessUser = session_get_user();
        $flash    = $_SESSION['_flash'] ?? [];

        try {
            $dbRow = $this->db->fetchOne(
                'SELECT LENGTH(data) AS dlen, (expires_at - UNIX_TIMESTAMP()) AS ttl FROM php_sessions WHERE id = ?',
                [$sid]
            );
        } catch (\Throwable $e) {
            $dbRow = ['err' => $e->getMessage()];
        }

        session_write_close();

        $nextUrl = route_url('settings-integrations');

        header('Content-Type: text/html; charset=utf-8');
        echo '<pre style="font:14px monospace;padding:20px;background:#0d1117;color:#58a6ff;line-height:1.6">';
        echo "<b style='color:#f0f6fc'>CreatorzHive — OAuth Callback Debug</b>\n\n";
        echo "<b>Step        :</b> " . htmlspecialchars($step, ENT_QUOTES, 'UTF-8') . "\n";
        echo "<b>Session ID  :</b> " . htmlspecialchars($sid, ENT_QUOTES, 'UTF-8') . "\n";
        echo "<b>Session user:</b> " . ($sessUser !== null
            ? '<span style="color:#3fb950">YES — id=' . (int) ($sessUser['id'] ?? 0)
                . ', role=' . htmlspecialchars((string) ($sessUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>'
            : '<span style="color:#f85149">NO — session has no user</span>') . "\n";

        if ($dbRow !== null && !isset($dbRow['err'])) {
            echo "<b>DB session  :</b> <span style='color:#3fb950'>FOUND — "
                . (int) ($dbRow['dlen'] ?? 0) . "B, TTL=" . (int) ($dbRow['ttl'] ?? 0) . "s</span>\n";
        } else {
            $errMsg = isset($dbRow['err'])
                ? htmlspecialchars((string) $dbRow['err'], ENT_QUOTES, 'UTF-8')
                : 'row not found';
            echo "<b>DB session  :</b> <span style='color:#f85149'>MISSING — " . $errMsg . "</span>\n";
        }

        echo "<b>Flash       :</b> " . htmlspecialchars((string) json_encode($flash), ENT_QUOTES, 'UTF-8') . "\n";

        foreach ($extra as $key => $value) {
            $label = str_pad(ucwords(str_replace('_', ' ', $key)), 12);
            echo "<b>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ":</b> "
                . htmlspecialchars((string) json_encode($value), ENT_QUOTES, 'UTF-8') . "\n";
        }

        echo "\n<a href=\"" . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . "\" "
            . "style=\"color:#e3b341;font-weight:bold;font-size:16px\">"
            . "&#8594; Continue to Settings page</a>\n";
        echo "<span style='color:#8b949e;font-size:12px'>"
            . "If that link redirects to login, the session is being lost between requests.</span>";
        echo '</pre>';
        exit;
    }

    private function buildState(int $userId): string
    {
        $nonce   = bin2hex(random_bytes(16));
        $payload = $userId . '.' . $nonce;
        $sig     = hash_hmac('sha256', $payload, (string) env('APP_SECRET', ''));

        return $payload . '.' . $sig;
    }

    private function verifyState(string $state): ?int
    {
        $parts = explode('.', $state, 3);
        if (count($parts) !== 3) {
            return null;
        }

        [$userIdStr, $nonce, $sig] = $parts;
        $payload     = $userIdStr . '.' . $nonce;
        $expectedSig = hash_hmac('sha256', $payload, (string) env('APP_SECRET', ''));

        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        $userId = (int) $userIdStr;

        return $userId > 0 ? $userId : null;
    }
}
