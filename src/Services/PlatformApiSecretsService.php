<?php

declare(strict_types=1);

namespace CreatorzHive\Services;



use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Security\TokenCrypto;
use function env;
use function storage_path;

final class PlatformApiSecretsService
{
    /** @var Connection */
    private $db;

    /** @var TokenCrypto */
    private $crypto;

    public function __construct(Connection $db, TokenCrypto $crypto)
    {
        $this->db = $db;
        $this->crypto = $crypto;
    }

    public function storagePath()
    {
        if (!empty($GLOBALS['_cz_platform_secrets_test_path'])) {
                return (string) $GLOBALS['_cz_platform_secrets_test_path'];
            }
        
            return storage_path('platform-api-secrets.json');
    }

    public function credentialGroups()
    {
        return [
                'meta' => [
                    'label' => 'Meta (Instagram & Facebook)',
                    'description' => 'Graph API credentials for Instagram Business and Facebook Pages.',
                    'fields' => [
                        'meta_app_id' => [
                            'label' => 'Meta App ID',
                            'env' => 'META_APP_ID',
                            'secret' => false,
                            'help' => 'From Meta Developer Console → App settings → Basic.',
                        ],
                        'meta_app_secret' => [
                            'label' => 'Meta App Secret',
                            'env' => 'META_APP_SECRET',
                            'secret' => true,
                            'help' => 'Used for server-side OAuth and token exchange (future).',
                        ],
                        'instagram_access_token' => [
                            'label' => 'Instagram access token',
                            'env' => 'INSTAGRAM_ACCESS_TOKEN',
                            'secret' => true,
                            'platform' => 'instagram',
                            'help' => 'Long-lived user or system user token with instagram_basic, pages_show_list, etc.',
                        ],
                        'instagram_business_id' => [
                            'label' => 'Instagram Business account ID',
                            'env' => 'INSTAGRAM_BUSINESS_ID',
                            'secret' => false,
                            'platform' => 'instagram',
                            'help' => 'IG User ID for the Business/Creator account (Graph API).',
                        ],
                        'facebook_access_token' => [
                            'label' => 'Facebook Page access token',
                            'env' => 'FACEBOOK_ACCESS_TOKEN',
                            'secret' => true,
                            'platform' => 'facebook',
                            'help' => 'Page access token from Meta Business Suite or Graph API Explorer.',
                        ],
                        'facebook_page_id' => [
                            'label' => 'Facebook Page ID',
                            'env' => 'FACEBOOK_PAGE_ID',
                            'secret' => false,
                            'platform' => 'facebook',
                            'help' => 'Numeric Page ID used when publishing to Facebook.',
                        ],
                    ],
                ],
                'tiktok' => [
                    'label' => 'TikTok',
                    'description' => 'TikTok Content Posting API credentials.',
                    'fields' => [
                        'tiktok_access_token' => [
                            'label' => 'TikTok access token',
                            'env' => 'TIKTOK_ACCESS_TOKEN',
                            'secret' => true,
                            'platform' => 'tiktok',
                            'help' => 'OAuth access token from TikTok for Developers.',
                        ],
                        'tiktok_privacy_level' => [
                            'label' => 'Default privacy level',
                            'env' => 'TIKTOK_PRIVACY_LEVEL',
                            'secret' => false,
                            'help' => 'e.g. SELF_ONLY, MUTUAL_FOLLOW_FRIENDS, PUBLIC_TO_EVERYONE',
                        ],
                    ],
                ],
                'youtube' => [
                    'label' => 'YouTube / Google',
                    'description' => 'YouTube Data API and Google OAuth credentials.',
                    'fields' => [
                        'youtube_access_token' => [
                            'label' => 'YouTube access token',
                            'env' => 'YOUTUBE_ACCESS_TOKEN',
                            'secret' => true,
                            'platform' => 'youtube',
                            'help' => 'OAuth token with youtube.upload scope.',
                        ],
                        'youtube_channel_id' => [
                            'label' => 'YouTube channel ID',
                            'env' => 'YOUTUBE_CHANNEL_ID',
                            'secret' => false,
                            'platform' => 'youtube',
                            'help' => 'Default channel when a creator account has no platform_user_id.',
                        ],
                        'youtube_privacy_status' => [
                            'label' => 'Default upload privacy',
                            'env' => 'YOUTUBE_PRIVACY_STATUS',
                            'secret' => false,
                            'help' => 'private, unlisted, or public',
                        ],
                        'google_client_id' => [
                            'label' => 'Google OAuth client ID',
                            'env' => 'GOOGLE_CLIENT_ID',
                            'secret' => false,
                            'help' => 'For token refresh (server-side).',
                        ],
                        'google_client_secret' => [
                            'label' => 'Google OAuth client secret',
                            'env' => 'GOOGLE_CLIENT_SECRET',
                            'secret' => true,
                            'help' => 'Keep confidential; used for refresh_token exchange.',
                        ],
                    ],
                ],
                'twitter' => [
                    'label' => 'X (Twitter)',
                    'description' => 'X API v2 bearer token for reads and posting.',
                    'fields' => [
                        'twitter_bearer_token' => [
                            'label' => 'Bearer token',
                            'env' => 'TWITTER_BEARER_TOKEN',
                            'secret' => true,
                            'platform' => 'twitter',
                            'help' => 'From the X Developer Portal → Keys and tokens.',
                        ],
                    ],
                ],
            ];
    }

    public function allGroupsPublic()
    {
        $list = [];
            foreach (array_keys($this->credentialGroups()) as $groupKey) {
                $list[] = $this->groupPublic((string) $groupKey);
            }
        
            return $list;
    }

    public function group(string $group)
    {
        $group = strtolower(trim($group));
            $groups = $this->credentialGroups();
        
            return $groups[$group] ?? null;
    }

    public function fieldDefinition(string $fieldKey)
    {
        foreach ($this->credentialGroups() as $group) {
                if (isset($group['fields'][$fieldKey]) && is_array($group['fields'][$fieldKey])) {
                    return $group['fields'][$fieldKey];
                }
            }
        
            return null;
    }

    public function encrypt(string $plaintext)
    {
        return $this->crypto->pack($plaintext);
    }

    public function decrypt(string $encoded)
    {
        return $this->crypto->unpack($encoded);
    }

    public function loadStore()
    {
        $path = $this->storagePath();
            if (!is_file($path)) {
                return ['v' => 1, 'fields' => []];
            }
        
            $raw = @file_get_contents($path);
            if (!is_string($raw) || trim($raw) === '') {
                return ['v' => 1, 'fields' => []];
            }
        
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return ['v' => 1, 'fields' => []];
            }
        
            if (!isset($decoded['fields']) || !is_array($decoded['fields'])) {
                $decoded['fields'] = [];
            }
        
            return $decoded;
    }

    public function saveStore(array $store)
    {
        $path = $this->storagePath();
            $dir = dirname($path);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return false;
            }
        
            $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return false;
            }
        
            $written = @file_put_contents($path, $json . PHP_EOL, LOCK_EX);
            if ($written === false) {
                return false;
            }
        
            @chmod($path, 0600);
        
            return true;
    }

    public function storedValue(string $fieldKey)
    {
        $store = $this->loadStore();
            $encrypted = trim((string) (($store['fields'][$fieldKey] ?? '')));
        
            if ($encrypted === '') {
                return '';
            }
        
            return $this->decrypt($encrypted);
    }

    public function resolve(string $fieldKey)
    {
        $definition = $this->fieldDefinition($fieldKey);
            if ($definition === null) {
                return '';
            }
        
            $ui = $this->storedValue($fieldKey);
            if ($ui !== '') {
                return $ui;
            }
        
            $envKey = (string) ($definition['env'] ?? '');
        
            return $envKey !== '' ? trim((string) env($envKey, '')) : '';
    }

    public function resolveEnv(string $envKey)
    {
        foreach ($this->credentialGroups() as $group) {
                foreach ($group['fields'] as $fieldKey => $def) {
                    if ((string) ($def['env'] ?? '') === $envKey) {
                        return $this->resolve((string) $fieldKey);
                    }
                }
            }
        
            return trim((string) env($envKey, ''));
    }

    public function mask(string $value)
    {
        $value = trim($value);
            if ($value === '') {
                return '';
            }
        
            $len = strlen($value);
            if ($len <= 4) {
                return str_repeat('•', $len);
            }
        
            return str_repeat('•', min(8, $len - 4)) . substr($value, -4);
    }

    public function fieldStatus(string $fieldKey)
    {
        $definition = $this->fieldDefinition($fieldKey);
            if ($definition === null) {
                return [];
            }
        
            $ui = $this->storedValue($fieldKey);
            $envKey = (string) ($definition['env'] ?? '');
            $envVal = $envKey !== '' ? trim((string) env($envKey, '')) : '';
            $effective = $ui !== '' ? $ui : $envVal;
        
            $source = 'none';
            if ($ui !== '') {
                $source = 'ui';
            } elseif ($envVal !== '') {
                $source = 'env';
            }
        
            return [
                'key' => $fieldKey,
                'label' => (string) ($definition['label'] ?? $fieldKey),
                'help' => (string) ($definition['help'] ?? ''),
                'is_secret' => (bool) ($definition['secret'] ?? false),
                'configured' => $effective !== '',
                'source' => $source,
                'preview' => $effective !== '' ? $this->mask($effective) : '',
            ];
    }

    public function groupPublic(string $groupKey)
    {
        $group = $this->group($groupKey);
            if ($group === null) {
                return [];
            }
        
            $fields = [];
            foreach ($group['fields'] as $fieldKey => $def) {
                $fields[] = $this->fieldStatus((string) $fieldKey);
            }
        
            return [
                'group' => $groupKey,
                'label' => (string) ($group['label'] ?? $groupKey),
                'description' => (string) ($group['description'] ?? ''),
                'fields' => $fields,
            ];
    }

    public function primaryTokenField(string $platform)
    {
        $map = [
                'instagram' => 'instagram_access_token',
                'facebook' => 'facebook_access_token',
                'tiktok' => 'tiktok_access_token',
                'youtube' => 'youtube_access_token',
                'twitter' => 'twitter_bearer_token',
            ];
        
            $platform = strtolower(trim($platform));
        
            return $map[$platform] ?? null;
    }

    public function tokenSourceForPlatform(string $platform)
    {
        $fieldKey = $this->primaryTokenField($platform);
            if ($fieldKey === null) {
                return 'none';
            }
        
            return $this->sourceForField($fieldKey);
    }

    public function sourceForField(string $fieldKey)
    {
        if ($this->storedValue($fieldKey) !== '') {
                return 'ui';
            }
        
            $definition = $this->fieldDefinition($fieldKey);
            if ($definition === null) {
                return 'none';
            }
        
            $envKey = (string) ($definition['env'] ?? '');
            if ($envKey !== '' && trim((string) env($envKey, '')) !== '') {
                return 'env';
            }
        
            return 'none';
    }

    public function platformTokenConfigured(string $platform)
    {
        $platform = strtolower(trim($platform));
            foreach ($this->credentialGroups() as $group) {
                foreach ($group['fields'] as $fieldKey => $def) {
                    if ((string) ($def['platform'] ?? '') !== $platform) {
                        continue;
                    }
                    if (!empty($def['secret']) && $this->resolve((string) $fieldKey) !== '') {
                        return true;
                    }
                }
            }
        
            $envMap = [
                'instagram' => 'INSTAGRAM_ACCESS_TOKEN',
                'facebook' => 'FACEBOOK_ACCESS_TOKEN',
                'tiktok' => 'TIKTOK_ACCESS_TOKEN',
                'youtube' => 'YOUTUBE_ACCESS_TOKEN',
                'twitter' => 'TWITTER_BEARER_TOKEN',
            ];
            $envKey = $envMap[$platform] ?? '';
        
            return $envKey !== '' && $this->resolveEnv($envKey) !== '';
    }

    public function applyGroupUpdate(string $groupKey, array $payload)
    {
        $group = $this->group($groupKey);
            if ($group === null) {
                return ['saved' => [], 'cleared' => []];
            }
        
            $store = $this->loadStore();
            $saved = [];
            $cleared = [];
        
            foreach ($group['fields'] as $fieldKey => $def) {
                $fieldKey = (string) $fieldKey;
                $clearKey = 'clear_' . $fieldKey;
                $valueKey = 'credential_' . $fieldKey;
        
                if (array_key_exists($clearKey, $payload) && (string) $payload[$clearKey] === '1') {
                    unset($store['fields'][$fieldKey]);
                    $cleared[] = $fieldKey;
                    continue;
                }
        
                if (!array_key_exists($valueKey, $payload)) {
                    continue;
                }
        
                $value = trim((string) $payload[$valueKey]);
                if ($value === '') {
                    continue;
                }
        
                $maxLen = !empty($def['secret']) ? 4096 : 512;
                $value = substr($value, 0, $maxLen);
                $store['fields'][$fieldKey] = $this->encrypt($value);
                $saved[] = $fieldKey;
            }
        
            if ($saved !== [] || $cleared !== []) {
                $this->saveStore($store);
            }
        
            return ['saved' => $saved, 'cleared' => $cleared];
    }

}
