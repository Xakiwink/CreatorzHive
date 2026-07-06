<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\TiktokOAuthService;
use function response_redirect;
use function route_url;
use function session_flash;
use function session_get;
use function session_get_user;
use function session_remove;
use function session_set;
use function tiktok_oauth_is_configured;

final class TiktokOAuthController extends AbstractController
{
    /** @var TiktokOAuthService */
    private $tiktok;

    /** @var AdminService */
    private $admin;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        TiktokOAuthService $tiktok,
        AdminService $admin
    ) {
        parent::__construct($views, $json, $db);
        $this->tiktok = $tiktok;
        $this->admin = $admin;
    }

    public function connectStart(): void
    {
        $user = session_get_user();
        if ($user === null) {
            response_redirect(route_url('login'));
        }

        $role = (string) ($user['role'] ?? '');
        if ($role === 'admin') {
            session_flash('oauth_error', 'Admins cannot connect creator social accounts. Use a creator account.');
            response_redirect(route_url('settings-integrations'));
        }

        if (!$this->admin->integrationEnabled('tiktok')) {
            session_flash('oauth_error', 'TikTok integration is disabled.');
            response_redirect(route_url('settings-integrations'));
        }

        if (!tiktok_oauth_is_configured()) {
            session_flash('oauth_error', 'TikTok client key and secret must be configured by an admin before connecting TikTok.');
            response_redirect(route_url('settings-integrations'));
        }

        $state = bin2hex(random_bytes(16));
        $codeVerifier = $this->tiktok->generateCodeVerifier();
        session_set('tiktok_oauth_state', $state);
        session_set('tiktok_oauth_verifier', $codeVerifier);
        session_set('tiktok_oauth_user_id', (int) ($user['id'] ?? 0));

        response_redirect($this->tiktok->authorizeUrl($state, $codeVerifier));
    }

    public function callbackHandler(): void
    {
        $error = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
        if ($error !== '') {
            session_flash('oauth_error', 'TikTok authorization was denied or failed.');
            response_redirect(route_url('settings-integrations'));
        }

        $state = trim((string) ($_GET['state'] ?? ''));
        $expected = trim((string) session_get('tiktok_oauth_state', ''));
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            session_flash('oauth_error', 'Invalid OAuth state. Please try connecting again.');
            response_redirect(route_url('settings-integrations'));
        }

        $userId = (int) session_get('tiktok_oauth_user_id', 0);
        $codeVerifier = trim((string) session_get('tiktok_oauth_verifier', ''));
        session_remove('tiktok_oauth_state');
        session_remove('tiktok_oauth_verifier');
        session_remove('tiktok_oauth_user_id');

        if ($userId < 1 || $codeVerifier === '') {
            session_flash('oauth_error', 'OAuth session expired. Please try again.');
            response_redirect(route_url('settings-integrations'));
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            session_flash('oauth_error', 'Authorization code missing from TikTok.');
            response_redirect(route_url('settings-integrations'));
        }

        $result = $this->tiktok->completeConnection($userId, $code, $codeVerifier);
        if ($result['success']) {
            session_flash('oauth_success', (string) $result['message']);
        } else {
            session_flash('oauth_error', (string) $result['message']);
        }

        response_redirect(route_url('settings-integrations'));
    }
}
