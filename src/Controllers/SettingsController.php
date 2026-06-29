<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\JobQueueRepository;
use CreatorzHive\Repositories\NotificationPreferenceRepository;
use CreatorzHive\Repositories\SocialAccountRepository;
use CreatorzHive\Repositories\UserPreferencesRepository;
use CreatorzHive\Repositories\UserRepository;
use CreatorzHive\Repositories\UserSessionRepository;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\AuthService;
use CreatorzHive\Services\InstagramOAuthService;
use CreatorzHive\Support\SettingsPageHelper;
use function request_all;
use function request_file;
use function request_post;
use function session_get_user;
use function session_id;
use function session_set_user;
use function validator_validate;

final class SettingsController extends AbstractController
{
    /** @var SettingsPageHelper */
    private $settingsPage;

    /** @var UserRepository */
    private $users;

    /** @var UserPreferencesRepository */
    private $preferences;

    /** @var UserSessionRepository */
    private $sessions;

    /** @var NotificationPreferenceRepository */
    private $notificationPrefs;

    /** @var SocialAccountRepository */
    private $socialAccounts;

    /** @var AuthService */
    private $auth;

    /** @var AdminService */
    private $admin;

    /** @var InstagramOAuthService */
    private $instagramOAuth;

    /** @var JobQueueRepository */
    private $jobs;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        SettingsPageHelper $settingsPage,
        UserRepository $users,
        UserPreferencesRepository $preferences,
        UserSessionRepository $sessions,
        NotificationPreferenceRepository $notificationPrefs,
        SocialAccountRepository $socialAccounts,
        AuthService $auth,
        AdminService $admin,
        InstagramOAuthService $instagramOAuth,
        JobQueueRepository $jobs
    ) {
        parent::__construct($views, $json, $db);
        $this->settingsPage = $settingsPage;
        $this->users = $users;
        $this->preferences = $preferences;
        $this->sessions = $sessions;
        $this->notificationPrefs = $notificationPrefs;
        $this->socialAccounts = $socialAccounts;
        $this->auth = $auth;
        $this->admin = $admin;
        $this->instagramOAuth = $instagramOAuth;
        $this->jobs = $jobs;
    }

    public function profile(): void
    {
        $this->renderSettingsPage('profile');
    }

    public function security(): void
    {
        $this->renderSettingsPage('security');
    }

    public function integrations(): void
    {
        $this->renderSettingsPage('integrations');
    }

    public function notifications(): void
    {
        $this->renderSettingsPage('notifications');
    }

    public function preferences(): void
    {
        $this->renderSettingsPage('preferences');
    }

    private function renderSettingsPage(string $panel): void
    {
        $this->views->render('settings/profile', [
            'settings_panel' => $panel,
        ]);
    }

    public function profileData(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $row = $this->users->findById((int) $user['id']);
        if ($row === null) {
            $this->json->error('User not found', 404);

            return;
        }
        $row = $this->settingsPage->publicUser($row);

        $prefs = $this->preferences->preferencesGetByUserId((int) $user['id']);

        $this->json->success([
            'user' => $row,
            'preferences' => $prefs,
        ], 'Profile loaded');
    }

    public function updateProfile(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $userId = (int) $user['id'];
        $payload = $this->settingsPage->normalizeProfilePayload(request_all());

        $v = validator_validate($payload, [
            'name' => ['required', 'max:255'],
            'username' => ['required', 'min:3', 'max:100'],
            'bio' => ['max:5000'],
            'website_url' => ['url'],
            'timezone' => ['max:100'],
        ]);
        if (!$v['valid']) {
            $this->json->error('Validation failed', 422, $v['errors']);

            return;
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $existingU = $this->users->findByUsername($username);
        if ($existingU !== null && (int) $existingU['id'] !== $userId) {
            $this->json->error('Username is already taken', 422);

            return;
        }

        $website = trim((string) ($payload['website_url'] ?? ''));
        if ($website === '') {
            $website = null;
        }

        $update = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'username' => $username,
            'bio' => isset($payload['bio']) ? (trim((string) $payload['bio']) ?: null) : null,
            'website_url' => $website,
            'timezone' => trim((string) ($payload['timezone'] ?? 'Africa/Dar_es_Salaam')) ?: 'Africa/Dar_es_Salaam',
        ];

        $file = request_file('avatar');
        if ($file !== null) {
            $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err !== UPLOAD_ERR_OK) {
                if ($err !== UPLOAD_ERR_NO_FILE) {
                    $this->json->error($this->settingsPage->avatarUploadErrorMessage($err), 422);

                    return;
                }
            } else {
                $rel = $this->settingsPage->processAvatarUpload($file, $userId);
                if ($rel !== null) {
                    $update['avatar_url'] = $rel;
                }
            }
        }

        $this->users->update($userId, $update);

        $fresh = $this->users->findById($userId);
        if ($fresh !== null) {
            $forSession = $fresh;
            unset($forSession['password']);
            session_set_user($forSession);
            $fresh = $this->settingsPage->publicUser($fresh);
        }

        $this->json->success(['user' => $fresh], 'Profile updated');
    }

    public function updatePassword(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $payload = request_all();
        $v = validator_validate($payload, [
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8'],
            'new_password_confirmation' => ['required'],
        ]);
        if (!$v['valid']) {
            $this->json->error('Validation failed', 422, $v['errors']);

            return;
        }

        if (($payload['new_password'] ?? '') !== ($payload['new_password_confirmation'] ?? '')) {
            $this->json->error('New passwords do not match', 422);

            return;
        }

        $row = $this->users->findById((int) $user['id']);
        if ($row === null || !$this->auth->checkPassword((string) $payload['current_password'], (string) $row['password'])) {
            $this->json->error('Current password is incorrect', 422);

            return;
        }

        $this->users->updatePassword((int) $user['id'], $this->auth->hashPassword((string) $payload['new_password']));
        $this->json->success(null, 'Password updated');
    }

    public function getSessions(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $current = session_id();
        $rows = $this->sessions->sessionListForUser((int) $user['id']);
        foreach ($rows as &$r) {
            $r['is_current'] = ($r['id'] ?? '') === $current;
        }

        $this->json->success(['sessions' => $rows, 'current_session_id' => $current], 'Sessions loaded');
    }

    public function revokeSession(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $sid = (string) request_post('session_id', '');
        if ($sid === '') {
            $this->json->error('session_id required', 422);

            return;
        }

        $this->sessions->sessionRevoke($sid, (int) $user['id']);
        $this->json->success(null, 'Session revoked');
    }

    public function revokeAllSessions(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $current = session_id();
        if ($current === '') {
            $this->json->error('No session', 400);

            return;
        }

        $this->sessions->sessionRevokeOthers((int) $user['id'], $current);
        $this->json->success(null, 'Other sessions revoked');
    }

    public function integrationsData(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $userId = (int) $user['id'];

        $this->json->success([
            'accounts' => $this->socialAccounts->listSummaryForUser($userId),
            'oauth_platforms' => $this->instagramOAuth->isConfigured() ? ['instagram'] : [],
        ], 'Integrations loaded');
    }

    public function connectPlatform(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $platform = strtolower(trim((string) request_post('platform', '')));
        $allowed = ['instagram', 'tiktok', 'youtube', 'twitter'];
        if (!in_array($platform, $allowed, true)) {
            $this->json->error('Invalid platform', 422);

            return;
        }
        if (!$this->admin->integrationEnabled($platform)) {
            $this->json->error(ucfirst($platform) . ' integration is currently disabled by admin.', 403);

            return;
        }

        $username = trim((string) request_post('username', ''));
        if ($username === '') {
            $username = strtolower($platform . '_' . (string) random_int(10000, 99999));
        }

        if (!preg_match('/^[A-Za-z0-9._-]{2,100}$/', $username)) {
            $this->json->error('Username can only contain letters, numbers, dot, underscore, and dash', 422);

            return;
        }

        $displayName = trim((string) request_post('display_name', ''));
        if ($displayName === '') {
            $displayName = ucfirst($platform) . ' Account';
        }

        $followerCount = max(0, (int) request_post('follower_count', random_int(500, 25000)));
        $token = trim((string) request_post('access_token', ''));
        if ($token === '') {
            $token = 'mock_' . bin2hex(random_bytes(16));
        }
        $refresh = trim((string) request_post('refresh_token', ''));
        if ($refresh === '') {
            $refresh = 'mock_refresh_' . bin2hex(random_bytes(12));
        }
        $platformUserId = trim((string) request_post('platform_user_id', ''));
        if ($platformUserId === '') {
            $platformUserId = strtolower($platform . '_' . bin2hex(random_bytes(8)));
        }
        $expiresAt = trim((string) request_post('token_expires_at', ''));
        if ($expiresAt === '') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+60 days'));
        }

        $userId = (int) $user['id'];
        $this->socialAccounts->accountUpsert($userId, [
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
            'username' => $username,
            'display_name' => $displayName,
            'avatar_url' => null,
            'access_token' => $token,
            'refresh_token' => $refresh,
            'token_expires_at' => $expiresAt,
            'follower_count' => $followerCount,
        ]);

        $account = $this->socialAccounts->findSummaryForUserPlatform($userId, $platform);

        if ($account !== null) {
            $this->jobs->dispatch('fetch_analytics', [
                'user_id' => $userId,
                'social_account_id' => (int) ($account['id'] ?? 0),
            ]);
        }

        $this->json->success(['account' => $account], 'Platform connected');
    }

    public function disconnectPlatform(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $platform = (string) request_post('platform', '');
        $allowed = ['instagram', 'tiktok', 'youtube', 'twitter'];
        if (!in_array($platform, $allowed, true)) {
            $this->json->error('Invalid platform', 422);

            return;
        }

        $this->socialAccounts->deactivate((int) $user['id'], $platform);

        $this->json->success(null, 'Platform disconnected');
    }

    public function notificationPrefs(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $userId = (int) $user['id'];
        $row = $this->notificationPrefs->preferenceGetByUserId($userId);
        if ($row === null) {
            $row = $this->notificationPrefs->defaultPreferences($userId);
        }

        $this->json->success($row, 'Preferences loaded');
    }

    public function updateNotificationPrefs(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $payload = request_all();
        $keys = [
            'email_post_published',
            'email_post_failed',
            'email_deal_updated',
            'email_invoice_paid',
            'email_weekly_summary',
            'push_post_published',
            'push_deal_updated',
        ];
        $data = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $payload)) {
                $data[$k] = (int) (bool) $payload[$k];
            }
        }
        if ($data === []) {
            $this->json->error('No fields to update', 422);

            return;
        }

        $this->notificationPrefs->preferenceUpsert((int) $user['id'], $data);
        $this->json->success(null, 'Notification preferences saved');
    }

    public function updatePreferences(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $payload = request_all();
        $v = validator_validate($payload, [
            'theme' => ['in:light,dark,system'],
            'language' => ['max:10'],
            'default_currency' => ['max:3'],
            'date_format' => ['max:20'],
            'time_format' => ['in:12h,24h'],
        ]);
        if (!$v['valid']) {
            $this->json->error('Validation failed', 422, $v['errors']);

            return;
        }

        $data = [];
        if (isset($payload['theme'])) {
            $data['theme'] = (string) $payload['theme'];
        }
        if (isset($payload['language'])) {
            $data['language'] = substr((string) $payload['language'], 0, 10);
        }
        if (isset($payload['default_currency'])) {
            $data['default_currency'] = strtoupper(substr((string) $payload['default_currency'], 0, 3));
        }
        if (isset($payload['date_format'])) {
            $data['date_format'] = (string) $payload['date_format'];
        }
        if (isset($payload['time_format'])) {
            $data['time_format'] = (string) $payload['time_format'];
        }
        if (isset($payload['week_starts_on'])) {
            $data['week_starts_on'] = (int) $payload['week_starts_on'] ? 1 : 0;
        }
        if (isset($payload['sidebar_collapsed'])) {
            $data['sidebar_collapsed'] = (int) (bool) $payload['sidebar_collapsed'];
        }

        if ($data === []) {
            $this->json->error('No fields to update', 422);

            return;
        }

        $userId = (int) $user['id'];
        $this->preferences->preferencesUpsert($userId, $data);
        $this->json->success(
            ['preferences' => $this->preferences->preferencesGetByUserId($userId)],
            'Preferences saved'
        );
    }

    public function renderPage(string $panel): void
    {
        $allowed = ['profile', 'security', 'integrations', 'notifications', 'preferences'];
        if (!in_array($panel, $allowed, true)) {
            $panel = 'profile';
        }

        $this->views->render('settings/profile', ['settings_panel' => $panel]);
    }
}
