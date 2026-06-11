<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\AuditLogRepository;
use CreatorzHive\Repositories\UserRepository;
use CreatorzHive\Services\AdminService;
use CreatorzHive\Services\AuthService;
use CreatorzHive\Services\PlatformApiSecretsService;
use CreatorzHive\Services\SocialApiService;
use function request_all;
use function request_get;
use function request_json_body;
use function request_post;
use function session_get_user;
use function validator_validate;

final class AdminUserController extends AbstractController
{
    /** @var UserRepository */
    private $users;

    /** @var AdminService */
    private $admin;

    /** @var AuditLogRepository */
    private $audit;

    /** @var PlatformApiSecretsService */
    private $secrets;

    /** @var SocialApiService */
    private $socialApi;

    /** @var AuthService */
    private $auth;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        UserRepository $users,
        AdminService $admin,
        AuditLogRepository $audit,
        PlatformApiSecretsService $secrets,
        SocialApiService $socialApi,
        AuthService $auth
    ) {
        parent::__construct($views, $json, $db);
        $this->users = $users;
        $this->admin = $admin;
        $this->audit = $audit;
        $this->secrets = $secrets;
        $this->socialApi = $socialApi;
        $this->auth = $auth;
    }

    public function usersPage(): void
    {
        $this->views->render('settings/admin-users');
    }

    public function usersIndex(): void
    {
        $limit = (int) request_get('limit', 100);
        $offset = (int) request_get('offset', 0);
        $rows = $this->users->listAll($limit, $offset);
        $total = $this->users->countAll();

        $this->json->success([
            'users' => $rows,
            'total' => $total,
        ], 'Users loaded');
    }

    public function platformOverview(): void
    {
        $this->json->success([
            'settings' => $this->admin->settingsGetAll(),
            'summary' => $this->admin->platformSummary(),
            'integrations' => $this->admin->integrationStatuses(),
            'platform_credentials' => $this->secrets->allGroupsPublic(),
        ], 'Admin overview loaded');
    }

    public function platformCredentials(): void
    {
        $group = strtolower(trim((string) request_get('group', 'meta')));
        $public = $this->secrets->groupPublic($group);
        if ($public === []) {
            $this->json->error('Unknown credential group', 422);

            return;
        }

        $this->json->success($public, 'Platform credentials loaded');
    }

    public function updatePlatformCredentials(): void
    {
        $actor = session_get_user();
        $payload = array_merge(request_all(), request_json_body());
        $group = strtolower(trim((string) ($payload['group'] ?? 'meta')));

        if ($this->secrets->group($group) === null) {
            $this->json->error('Unknown credential group', 422);

            return;
        }

        $result = $this->secrets->applyGroupUpdate($group, $payload);
        if ($result['saved'] === [] && $result['cleared'] === []) {
            $this->json->error('No credential changes submitted. Enter a value or mark a field to clear.', 422);

            return;
        }

        $this->audit->logCreate(
            $actor !== null ? (int) ($actor['id'] ?? 0) : null,
            'admin.platform_credentials.updated',
            'platform_credentials',
            null,
            ['group' => $group],
            ['saved' => $result['saved'], 'cleared' => $result['cleared']]
        );

        $validation = $this->admin->validateSavedCredentials($group, $result['saved']);
        $message = 'Platform credentials updated';
        if ($validation['warnings'] !== []) {
            $message .= ' (' . implode(' ', $validation['warnings']) . ')';
        }

        $this->json->success([
            'group' => $group,
            'saved' => $result['saved'],
            'cleared' => $result['cleared'],
            'credentials' => $this->secrets->groupPublic($group),
            'validation' => $validation,
        ], $message);
    }

    public function settingsUpdate(): void
    {
        $actor = session_get_user();
        $payload = array_merge(request_all(), request_json_body());
        $current = $this->admin->settingsGetAll();

        $next = $current;
        if (array_key_exists('registration_enabled', $payload)) {
            $next['registration_enabled'] = (string) $payload['registration_enabled'] === '1';
        }
        if (array_key_exists('require_email_verification', $payload)) {
            $next['require_email_verification'] = (string) $payload['require_email_verification'] === '1';
        }
        if (array_key_exists('forgot_password_enabled', $payload)) {
            $next['forgot_password_enabled'] = (string) $payload['forgot_password_enabled'] === '1';
        }
        if (array_key_exists('admin_note', $payload)) {
            $next['admin_note'] = substr(trim((string) $payload['admin_note']), 0, 500);
        }
        if (array_key_exists('site_display_name', $payload)) {
            $next['site_display_name'] = substr(trim((string) $payload['site_display_name']), 0, 120);
            if ($next['site_display_name'] === '') {
                $next['site_display_name'] = 'CreatorzHive';
            }
        }
        if (array_key_exists('support_email', $payload)) {
            $raw = trim((string) $payload['support_email']);
            if ($raw === '') {
                $next['support_email'] = '';
            } elseif (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                $next['support_email'] = strtolower($raw);
            } else {
                $this->json->error('Support email must be empty or a valid address', 422);

                return;
            }
        }
        if (array_key_exists('maintenance_mode', $payload)) {
            $next['maintenance_mode'] = (string) $payload['maintenance_mode'] === '1';
        }
        if (array_key_exists('maintenance_message', $payload)) {
            $next['maintenance_message'] = substr(trim((string) $payload['maintenance_message']), 0, 2000);
        }
        if (array_key_exists('max_upload_mb', $payload)) {
            $mb = (int) $payload['max_upload_mb'];
            if ($mb < 1 || $mb > 128) {
                $this->json->error('Max upload must be between 1 and 128 MB', 422);

                return;
            }
            $next['max_upload_mb'] = $mb;
        }
        foreach (array_keys($this->admin->integrationProviders()) as $provider) {
            $key = 'integration_enabled_' . $provider;
            if (array_key_exists($key, $payload)) {
                $next[$key] = (string) $payload[$key] === '1';
            }
        }

        if (!$this->admin->settingsSave($next)) {
            $this->json->error('Could not save admin settings', 500);

            return;
        }

        $this->audit->logCreate(
            $actor !== null ? (int) ($actor['id'] ?? 0) : null,
            'admin.settings.updated',
            'app_settings',
            null,
            $current,
            $next
        );

        $this->json->success(['settings' => $next], 'Admin settings updated');
    }

    public function integrationTest(): void
    {
        $platform = strtolower(trim((string) request_get('platform', '')));
        $providers = $this->admin->integrationProviders();
        if ($platform === '' || !isset($providers[$platform])) {
            $this->json->error('Invalid platform', 422);

            return;
        }

        $provider = $providers[$platform];
        $token = $this->secrets->resolveEnv((string) $provider['token_env']);
        if ($token === '') {
            $this->json->error('Token is not configured for ' . $provider['label'] . '. Set it under Meta API credentials or in .env.', 422);

            return;
        }

        $res = $this->socialApi->httpRequest(
            'GET',
            (string) $provider['test_url'],
            ['Authorization: Bearer ' . $token]
        );
        if (!$res['ok']) {
            $this->json->error(
                'Connection test failed for ' . $provider['label'] . ' (HTTP ' . (int) ($res['status'] ?? 0) . ').',
                400
            );

            return;
        }

        $this->json->success([
            'platform' => $platform,
            'status' => (int) ($res['status'] ?? 200),
        ], 'Connection test passed for ' . $provider['label'] . '.');
    }

    public function auditLogsIndex(): void
    {
        $limit = (int) request_get('limit', 100);
        $rows = $this->audit->logListRecent($limit);
        $this->json->success(['logs' => $rows], 'Audit logs loaded');
    }

    public function usersStore(): void
    {
        $actor = session_get_user();
        $payload = array_merge(request_all(), request_json_body());
        $payload['name'] = trim((string) ($payload['name'] ?? ''));
        $payload['username'] = strtolower(trim((string) ($payload['username'] ?? '')));
        $payload['email'] = strtolower(trim((string) ($payload['email'] ?? '')));
        $payload['role'] = trim((string) ($payload['role'] ?? 'creator'));
        $payload['email_verified'] = (int) ((string) ($payload['email_verified'] ?? '0') === '1');
        $payload['is_active'] = (int) ((string) ($payload['is_active'] ?? '1') === '1');

        $validation = validator_validate($payload, [
            'name' => 'required|max:255',
            'username' => 'required|min:3|max:100|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'in:creator,brand,admin',
        ]);
        if (!$validation['valid']) {
            $this->json->error('Validation failed', 422, $validation['errors']);

            return;
        }

        if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', (string) $payload['username'])) {
            $this->json->error('Validation failed', 422, [
                'username' => ['Username may only contain letters, numbers, dots, dashes, and underscores.'],
            ]);

            return;
        }

        $userId = $this->users->create([
            'name' => $payload['name'],
            'username' => $payload['username'],
            'email' => $payload['email'],
            'password' => $this->auth->hashPassword((string) $payload['password']),
            'role' => $payload['role'],
        ]);

        $this->users->update($userId, [
            'email_verified' => $payload['email_verified'],
            'is_active' => $payload['is_active'],
        ]);

        $row = $this->users->findById($userId);
        if ($row === null) {
            $this->json->error('User could not be created', 500);

            return;
        }
        unset($row['password']);
        $this->audit->logCreate(
            $actor !== null ? (int) ($actor['id'] ?? 0) : null,
            'admin.user.created',
            'user',
            $userId,
            [],
            [
                'email' => (string) ($row['email'] ?? ''),
                'role' => (string) ($row['role'] ?? ''),
            ]
        );

        $this->json->success(['user' => $row], 'User created');
    }

    public function usersUpdate(): void
    {
        $actor = session_get_user();
        $userId = (int) request_post('id', 0);
        if ($userId <= 0) {
            $this->json->error('User id is required', 422);

            return;
        }

        $existing = $this->users->findById($userId);
        if ($existing === null) {
            $this->json->error('User not found', 404);

            return;
        }

        $payload = array_merge(request_all(), request_json_body());
        $update = [];

        if (array_key_exists('name', $payload)) {
            $update['name'] = trim((string) $payload['name']);
        }
        if (array_key_exists('username', $payload)) {
            $username = strtolower(trim((string) $payload['username']));
            if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
                $this->json->error('Validation failed', 422, [
                    'username' => ['Username format is invalid.'],
                ]);

                return;
            }
            $dupe = $this->users->findByUsername($username);
            if ($dupe !== null && (int) $dupe['id'] !== $userId) {
                $this->json->error('Validation failed', 422, [
                    'username' => ['Username has already been taken.'],
                ]);

                return;
            }
            $update['username'] = $username;
        }
        if (array_key_exists('email', $payload)) {
            $email = strtolower(trim((string) $payload['email']));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->json->error('Validation failed', 422, ['email' => ['The email must be a valid email.']]);

                return;
            }
            $dupe = $this->users->findByEmail($email);
            if ($dupe !== null && (int) $dupe['id'] !== $userId) {
                $this->json->error('Validation failed', 422, [
                    'email' => ['Email has already been taken.'],
                ]);

                return;
            }
            $update['email'] = $email;
        }
        if (array_key_exists('role', $payload)) {
            $role = trim((string) $payload['role']);
            if (!in_array($role, ['creator', 'brand', 'admin'], true)) {
                $this->json->error('Validation failed', 422, ['role' => ['Invalid role.']]);

                return;
            }
            $update['role'] = $role;
        }
        if (array_key_exists('email_verified', $payload)) {
            $update['email_verified'] = (int) ((string) $payload['email_verified'] === '1');
        }
        if (array_key_exists('is_active', $payload)) {
            $update['is_active'] = (int) ((string) $payload['is_active'] === '1');
        }
        if (array_key_exists('password', $payload)) {
            $password = (string) $payload['password'];
            if (trim($password) !== '') {
                if (strlen($password) < 8) {
                    $this->json->error('Validation failed', 422, [
                        'password' => ['Password must be at least 8 characters.'],
                    ]);

                    return;
                }
                $update['password'] = $this->auth->hashPassword($password);
            }
        }

        if ($update === []) {
            $this->json->error('No fields to update', 422);

            return;
        }

        $this->users->update($userId, $update);
        $row = $this->users->findById($userId);
        if ($row === null) {
            $this->json->error('User not found after update', 404);

            return;
        }
        unset($row['password']);
        $this->audit->logCreate(
            $actor !== null ? (int) ($actor['id'] ?? 0) : null,
            'admin.user.updated',
            'user',
            $userId,
            [],
            $update
        );

        $this->json->success(['user' => $row], 'User updated');
    }

    public function usersDestroy(): void
    {
        $actor = session_get_user();
        $userId = (int) request_post('id', 0);
        if ($userId <= 0) {
            $this->json->error('User id is required', 422);

            return;
        }

        $current = session_get_user();
        if ($current !== null && (int) ($current['id'] ?? 0) === $userId) {
            $this->json->error('You cannot delete your own admin account.', 422);

            return;
        }

        $existing = $this->users->findById($userId);
        if ($existing === null) {
            $this->json->error('User not found', 404);

            return;
        }

        $this->db->delete('users', 'id = :id', ['id' => $userId]);
        $this->audit->logCreate(
            $actor !== null ? (int) ($actor['id'] ?? 0) : null,
            'admin.user.deleted',
            'user',
            $userId
        );

        $this->json->success(null, 'User deleted');
    }

    public function usersVerify(): void
    {
        $actor = session_get_user();
        $userId = (int) request_post('id', 0);
        if ($userId <= 0) {
            $this->json->error('User id is required', 422);

            return;
        }

        $existing = $this->users->findById($userId);
        if ($existing === null) {
            $this->json->error('User not found', 404);

            return;
        }

        $this->users->verifyEmail($userId);
        $row = $this->users->findById($userId);
        if ($row === null) {
            $this->json->error('User not found after verify', 404);

            return;
        }
        unset($row['password']);
        $this->audit->logCreate(
            $actor !== null ? (int) ($actor['id'] ?? 0) : null,
            'admin.user.verified',
            'user',
            $userId
        );

        $this->json->success(['user' => $row], 'User verified');
    }
}
