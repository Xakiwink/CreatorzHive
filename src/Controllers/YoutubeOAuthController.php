<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\YoutubeOAuthService;
use function response_redirect;
use function route_url;
use function session_flash;
use function session_get;
use function session_get_user;
use function session_remove;
use function session_set;
use function youtube_oauth_is_configured;

final class YoutubeOAuthController extends AbstractController
{
    /** @var YoutubeOAuthService */
    private $youtube;

    /** @var AdminService */
    private $admin;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        YoutubeOAuthService $youtube,
        AdminService $admin
    ) {
        parent::__construct($views, $json, $db);
        $this->youtube = $youtube;
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

        if (!$this->admin->integrationEnabled('youtube')) {
            session_flash('oauth_error', 'YouTube integration is disabled.');
            response_redirect(route_url('settings-integrations'));
        }

        if (!youtube_oauth_is_configured()) {
            session_flash('oauth_error', 'Google OAuth client ID and secret must be configured by an admin before connecting YouTube.');
            response_redirect(route_url('settings-integrations'));
        }

        $state = bin2hex(random_bytes(16));
        session_set('youtube_oauth_state', $state);
        session_set('youtube_oauth_user_id', (int) ($user['id'] ?? 0));

        response_redirect($this->youtube->authorizeUrl($state));
    }

    public function callbackHandler(): void
    {
        $error = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
        if ($error !== '') {
            session_flash('oauth_error', 'YouTube authorization was denied or failed.');
            response_redirect(route_url('settings-integrations'));
        }

        $state = trim((string) ($_GET['state'] ?? ''));
        $expected = trim((string) session_get('youtube_oauth_state', ''));
        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            session_flash('oauth_error', 'Invalid OAuth state. Please try connecting again.');
            response_redirect(route_url('settings-integrations'));
        }

        $userId = (int) session_get('youtube_oauth_user_id', 0);
        session_remove('youtube_oauth_state');
        session_remove('youtube_oauth_user_id');

        if ($userId < 1) {
            session_flash('oauth_error', 'OAuth session expired. Please try again.');
            response_redirect(route_url('settings-integrations'));
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            session_flash('oauth_error', 'Authorization code missing from Google.');
            response_redirect(route_url('settings-integrations'));
        }

        $result = $this->youtube->completeConnection($userId, $code);
        if ($result['success']) {
            session_flash('oauth_success', (string) $result['message']);
        } else {
            session_flash('oauth_error', (string) $result['message']);
        }

        response_redirect(route_url('settings-integrations'));
    }
}
