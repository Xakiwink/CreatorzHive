<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\InstagramOAuthService;
use function response_redirect;
use function route_url;
use function session_flash;
use function session_get;
use function session_get_user;
use function session_remove;
use function session_set;

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

        $state = bin2hex(random_bytes(16));
        session_set('oauth_state', $state);
        session_set('oauth_user_id', (int) ($user['id'] ?? 0));

        response_redirect($this->instagramOAuth->authorizeUrl($state));
    }

    public function callbackHandler(): void
    {
        $error = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
        if ($error !== '') {
            session_flash('oauth_error', 'Instagram authorization was denied or failed.');
            response_redirect(route_url('settings-integrations'));
        }

        $state    = trim((string) ($_GET['state'] ?? ''));
        $expected = trim((string) session_get('oauth_state', ''));
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            session_flash('oauth_error', 'Invalid OAuth state. Please try connecting again.');
            response_redirect(route_url('settings-integrations'));
        }

        $userId = (int) session_get('oauth_user_id', 0);
        session_remove('oauth_state');
        session_remove('oauth_user_id');

        if ($userId < 1) {
            session_flash('oauth_error', 'OAuth session expired. Please try again.');
            response_redirect(route_url('settings-integrations'));
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
}
