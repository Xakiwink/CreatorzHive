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
            response_redirect(route_url('settings-integrations'));
        }

        $state  = trim((string) ($_GET['state'] ?? ''));
        $userId = $this->verifyState($state);

        if ($userId === null) {
            session_flash('oauth_error', 'Invalid OAuth state. Please try connecting again.');
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
            response_redirect(route_url('settings-integrations'));
        }

        $result = $this->instagramOAuth->completeConnection($userId, $code);

        if ($result['success']) {
            session_flash('oauth_success', (string) $result['message']);
        } else {
            session_flash('oauth_error', (string) $result['message']);
        }

        response_redirect(route_url('settings-integrations'));
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
