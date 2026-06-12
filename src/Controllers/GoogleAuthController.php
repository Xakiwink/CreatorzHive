<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\UserRepository;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\AuthService;
use CreatorzHive\Services\GoogleAuthService;
use CreatorzHive\Services\NotificationService;
use function google_auth_is_configured;
use function response_redirect;
use function route_url;
use function session_flash;
use function session_get;
use function session_get_user;
use function session_regenerate_safe;
use function session_remove;
use function session_set;
use function session_set_user;

final class GoogleAuthController extends AbstractController
{
    /** @var GoogleAuthService */
    private $google;

    /** @var UserRepository */
    private $users;

    /** @var AuthService */
    private $auth;

    /** @var AdminService */
    private $admin;

    /** @var NotificationService */
    private $notifications;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        GoogleAuthService $google,
        UserRepository $users,
        AuthService $auth,
        AdminService $admin,
        NotificationService $notifications
    ) {
        parent::__construct($views, $json, $db);
        $this->google = $google;
        $this->users = $users;
        $this->auth = $auth;
        $this->admin = $admin;
        $this->notifications = $notifications;
    }

    public function start(): void
    {
        if (session_get_user() !== null) {
            response_redirect(route_url('dashboard'));
        }

        if (!google_auth_is_configured()) {
            session_flash('auth_error', 'Google sign-in is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env.');
            response_redirect(route_url('login'));
        }

        $role = strtolower(trim((string) ($_GET['role'] ?? 'creator')));
        if (!\in_array($role, ['creator', 'brand'], true)) {
            $role = 'creator';
        }

        // Force a new Set-Cookie header so any existing SameSite=Strict cookie is replaced with Lax before the cross-site redirect.
        session_regenerate_safe();

        $state = bin2hex(random_bytes(16));
        session_set('google_auth_state', $state);
        session_set('google_auth_role', $role);

        response_redirect($this->google->authorizeUrl($state));
    }

    public function callback(): void
    {
        $error = trim((string) ($_GET['error_description'] ?? $_GET['error'] ?? ''));
        if ($error !== '') {
            session_flash('auth_error', 'Google sign-in was cancelled or denied.');
            response_redirect(route_url('login'));
        }

        $state = trim((string) ($_GET['state'] ?? ''));
        $expected = trim((string) session_get('google_auth_state', ''));
        session_remove('google_auth_state');

        if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
            session_flash('auth_error', 'Invalid Google sign-in state. Please try again.');
            response_redirect(route_url('login'));
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            session_flash('auth_error', 'Google did not return an authorization code.');
            response_redirect(route_url('login'));
        }

        if (!google_auth_is_configured()) {
            session_flash('auth_error', 'Google sign-in is not configured on this server.');
            response_redirect(route_url('login'));
        }

        $exchange = $this->google->exchangeCode($code);
        if (!$exchange['ok']) {
            session_flash('auth_error', 'Google sign-in failed: ' . ($exchange['error'] ?? 'token error'));
            response_redirect(route_url('login'));
        }

        $profileRes = $this->google->fetchUserProfile((string) $exchange['access_token']);
        if (!$profileRes['ok']) {
            session_flash('auth_error', $profileRes['error'] ?? 'Could not read your Google profile.');
            response_redirect(route_url('login'));
        }

        $profile = $profileRes['profile'];
        $googleId = (string) $profile['google_id'];
        $email = (string) $profile['email'];

        $user = $this->users->findByGoogleId($googleId);
        if ($user === null) {
            $user = $this->users->findByEmail($email);
            if ($user !== null) {
                $this->users->linkGoogleId((int) $user['id'], $googleId);
                $user = $this->users->findById((int) $user['id']);
            }
        }

        if ($user === null) {
            $user = $this->registerFromGoogle($profile);
            if ($user === null) {
                response_redirect(route_url('login'));
            }
        } else {
            $this->maybeUpdateProfileFromGoogle((int) $user['id'], $profile);
        }

        if (!$this->establishSession($user)) {
            response_redirect(route_url('login'));
        }

        session_remove('google_auth_role');
        response_redirect(route_url('dashboard'));
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return array<string, mixed>|null
     */
    private function registerFromGoogle(array $profile)
    {
        if (!$this->admin->settingBool('registration_enabled', true)) {
            session_flash('auth_error', 'New registrations are temporarily disabled.');
            return null;
        }

        $role = strtolower(trim((string) session_get('google_auth_role', 'creator')));
        if (!\in_array($role, ['creator', 'brand'], true)) {
            $role = 'creator';
        }

        $name = (string) ($profile['name'] ?? '');
        if ($name === '') {
            $name = trim((string) ($profile['given_name'] ?? '') . ' ' . (string) ($profile['family_name'] ?? ''));
        }
        if ($name === '') {
            $name = 'Google User';
        }

        $username = $this->users->suggestAvailableUsername((string) $profile['email'], $name);
        $emailVerified = !empty($profile['email_verified']) ? 1 : 0;

        $avatar = (string) ($profile['picture'] ?? '');
        if ($avatar !== '' && \strlen($avatar) > 500) {
            $avatar = '';
        }

        $userId = $this->users->createOAuthUser([
            'name' => $name,
            'username' => $username,
            'email' => (string) $profile['email'],
            'password' => $this->auth->hashPassword(bin2hex(random_bytes(32))),
            'role' => $role,
            'google_id' => (string) $profile['google_id'],
            'email_verified' => $emailVerified,
            'avatar_url' => $avatar !== '' ? $avatar : null,
        ]);

        $user = $this->users->findById($userId);
        if ($user !== null) {
            $this->notifications->welcomeUser($userId, $name);
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function maybeUpdateProfileFromGoogle(int $userId, array $profile): void
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            return;
        }

        $update = [];
        if (!empty($profile['email_verified'])) {
            $update['email_verified'] = 1;
        }
        $picture = trim((string) ($profile['picture'] ?? ''));
        if ($picture !== '' && ($user['avatar_url'] ?? '') === '' && \strlen($picture) <= 500) {
            $update['avatar_url'] = $picture;
        }
        if ($update !== []) {
            $this->users->update($userId, $update);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function establishSession(array $user): bool
    {
        if ((int) ($user['is_active'] ?? 0) !== 1) {
            session_flash('auth_error', 'This account has been disabled.');
            return false;
        }

        if ($this->admin->settingBool('require_email_verification', true) && (int) ($user['email_verified'] ?? 0) !== 1) {
            session_flash('auth_error', 'Please verify your email before signing in.');
            return false;
        }

        session_regenerate_safe();
        unset($user['password']);
        session_set_user($user);
        $this->users->updateLastLogin((int) $user['id']);

        return true;
    }
}
