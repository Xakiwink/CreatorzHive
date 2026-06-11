<?php

declare(strict_types=1);

namespace CreatorzHive\Services;



use function admin_service_integration_enabled;
use function admin_service_integration_providers;
use function admin_service_settings_defaults;
use function admin_service_settings_get_all;
use function admin_service_settings_path;
use function meta_oauth_is_configured;
use function meta_oauth_redirect_uri;
use function platform_api_secrets_platform_token_configured;
use function platform_api_secrets_resolve_env;
use function platform_api_secrets_token_source_for_platform;
use function social_api_service_http_request;
use function storage_path;
use CreatorzHive\Core\Database\Connection;

final class AdminService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function settingsDefaults()
    {
        return [
                'registration_enabled' => true,
                'require_email_verification' => true,
                'forgot_password_enabled' => true,
                'admin_note' => '',
                'site_display_name' => 'CreatorzHive',
                'support_email' => '',
                'maintenance_mode' => false,
                'maintenance_message' => '',
                'max_upload_mb' => 2,
                'integration_enabled_instagram' => true,
                'integration_enabled_tiktok' => true,
                'integration_enabled_youtube' => true,
                'integration_enabled_twitter' => true,
                'integration_enabled_facebook' => true,
            ];
    }

    public function settingsPath()
    {
        return storage_path('app-settings.json');
    }

    public function settingsGetAll()
    {
        $defaults = admin_service_settings_defaults();
            $path = admin_service_settings_path();
            if (!is_file($path)) {
                return $defaults;
            }
        
            $raw = @file_get_contents($path);
            if (!is_string($raw) || trim($raw) === '') {
                return $defaults;
            }
        
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return $defaults;
            }
        
            return array_merge($defaults, $decoded);
    }

    public function settingsSave(array $settings)
    {
        $path = admin_service_settings_path();
            $dir = dirname($path);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return false;
            }
        
            $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return false;
            }
        
            return @file_put_contents($path, $json . PHP_EOL, LOCK_EX) !== false;
    }

    public function settingBool(string $key, bool $default)
    {
        $all = admin_service_settings_get_all();
            $value = $all[$key] ?? $default;
        
            return (bool) $value;
    }

    public function platformSummary()
    {
        $usersTotal = (int) ($this->db->fetchOne('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
            $usersActive = (int) ($this->db->fetchOne('SELECT COUNT(*) AS c FROM users WHERE is_active = 1')['c'] ?? 0);
            $usersUnverified = (int) ($this->db->fetchOne('SELECT COUNT(*) AS c FROM users WHERE email_verified = 0')['c'] ?? 0);
            $sessionsActive = (int) ($this->db->fetchOne('SELECT COUNT(*) AS c FROM sessions')['c'] ?? 0);
            $jobsPending = (int) ($this->db->fetchOne("SELECT COUNT(*) AS c FROM job_queue WHERE status IN ('pending','running')")['c'] ?? 0);
        
            return [
                'users_total' => $usersTotal,
                'users_active' => $usersActive,
                'users_unverified' => $usersUnverified,
                'sessions_active' => $sessionsActive,
                'jobs_pending' => $jobsPending,
            ];
    }

    public function integrationProviders()
    {
        return [
                'instagram' => [
                    'label' => 'Instagram',
                    'token_env' => 'INSTAGRAM_ACCESS_TOKEN',
                    'test_url' => 'https://graph.facebook.com/v20.0/me?fields=id',
                ],
                'tiktok' => [
                    'label' => 'TikTok',
                    'token_env' => 'TIKTOK_ACCESS_TOKEN',
                    'test_url' => 'https://open.tiktokapis.com/v2/user/info/',
                ],
                'youtube' => [
                    'label' => 'YouTube',
                    'token_env' => 'YOUTUBE_ACCESS_TOKEN',
                    'test_url' => 'https://www.googleapis.com/youtube/v3/channels?part=id&mine=true',
                ],
                'twitter' => [
                    'label' => 'X / Twitter',
                    'token_env' => 'TWITTER_BEARER_TOKEN',
                    'test_url' => 'https://api.twitter.com/2/users/me',
                ],
                'facebook' => [
                    'label' => 'Facebook',
                    'token_env' => 'FACEBOOK_ACCESS_TOKEN',
                    'test_url' => 'https://graph.facebook.com/v20.0/me?fields=id',
                ],
            ];
    }

    public function integrationEnabled(string $platform)
    {
        $platform = strtolower(trim($platform));
            $all = admin_service_settings_get_all();
            $key = 'integration_enabled_' . $platform;
            if (!array_key_exists($key, $all)) {
                return true;
            }
        
            return (bool) $all[$key];
    }

    public function integrationStatuses()
    {
        $providers = admin_service_integration_providers();
            $statuses = [];
            foreach ($providers as $slug => $config) {
                $expiringSoon = (int) ($this->db->fetchOne(
                    'SELECT COUNT(*) AS c FROM social_accounts WHERE platform = :p AND is_active = 1 AND token_expires_at IS NOT NULL AND token_expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY)',
                    ['p' => $slug]
                )['c'] ?? 0);
                $connected = (int) ($this->db->fetchOne(
                    'SELECT COUNT(*) AS c FROM social_accounts WHERE platform = :p AND is_active = 1',
                    ['p' => $slug]
                )['c'] ?? 0);
        
                $statuses[] = [
                    'platform' => $slug,
                    'label' => (string) $config['label'],
                    'enabled' => admin_service_integration_enabled($slug),
                    'token_configured' => platform_api_secrets_platform_token_configured($slug),
                    'token_source' => platform_api_secrets_token_source_for_platform($slug),
                    'connected_accounts' => $connected,
                    'expiring_soon' => $expiringSoon,
                ];
            }
        
            return $statuses;
    }

    public function validateSavedCredentials(string $group, array $savedFieldKeys)
    {
        $warnings = [];
            $group = strtolower(trim($group));
        
            $tokenFieldsToPlatform = [
                'instagram_access_token' => 'instagram',
                'facebook_access_token' => 'facebook',
                'tiktok_access_token' => 'tiktok',
                'youtube_access_token' => 'youtube',
                'twitter_bearer_token' => 'twitter',
            ];
        
            foreach ($savedFieldKeys as $fieldKey) {
                $platform = $tokenFieldsToPlatform[$fieldKey] ?? '';
                if ($platform === '') {
                    continue;
                }
        
                $providers = admin_service_integration_providers();
                if (!isset($providers[$platform])) {
                    continue;
                }
        
                $token = platform_api_secrets_resolve_env((string) $providers[$platform]['token_env']);
                if ($token === '') {
                    $warnings[] = ucfirst($platform) . ' token is empty after save.';
                    continue;
                }
        
                $res = social_api_service_http_request(
                    'GET',
                    (string) $providers[$platform]['test_url'],
                    ['Authorization: Bearer ' . $token]
                );
                if (!$res['ok']) {
                    $warnings[] = ucfirst($platform) . ' connection test failed (HTTP ' . (int) ($res['status'] ?? 0) . ').';
                }
            }
        
            if ($group === 'meta' && in_array('meta_app_id', $savedFieldKeys, true) && meta_oauth_is_configured()) {
                $redirect = meta_oauth_redirect_uri();
                if ($redirect === '') {
                    $warnings[] = 'Meta OAuth redirect URI could not be determined.';
                }
            }
        
            return ['ok' => $warnings === [], 'warnings' => $warnings];
    }

}
