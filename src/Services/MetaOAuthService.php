<?php

declare(strict_types=1);

namespace CreatorzHive\Services;



use function base_url_path;
use function env;
use function job_runner_dispatch;
use function meta_oauth_allowed_platforms;
use function meta_oauth_exchange_code;
use function meta_oauth_fetch_pages;
use function meta_oauth_long_lived_token;
use function meta_oauth_redirect_uri;
use function meta_oauth_save_facebook_page;
use function meta_oauth_save_instagram_account;
use function meta_oauth_scopes;
use function meta_oauth_upsert_social_account;
use function platform_api_secrets_resolve;
use function social_account_upsert;
use function social_api_service_http_request;
use CreatorzHive\Core\Database\Connection;

final class MetaOAuthService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function isConfigured()
    {
        return platform_api_secrets_resolve('meta_app_id') !== ''
                && platform_api_secrets_resolve('meta_app_secret') !== '';
    }

    public function redirectUri()
    {
        $override = trim((string) env('META_OAUTH_REDIRECT_URI', ''));
            if ($override !== '') {
                return $override;
            }
        
            $base = rtrim((string) env('APP_URL', 'http://localhost'), '/');
            $path = base_url_path();
        
            return $base . ($path === '' ? '' : $path) . '/?route=oauth-callback';
    }

    public function allowedPlatforms()
    {
        return ['instagram', 'facebook'];
    }

    public function scopes(string $platform)
    {
        $platform = strtolower(trim($platform));
            $base = 'pages_show_list,pages_read_engagement';
        
            if ($platform === 'instagram') {
                return 'instagram_basic,instagram_content_publish,instagram_manage_insights,business_management,' . $base;
            }
        
            return 'pages_manage_posts,pages_read_engagement,' . $base;
    }

    public function authorizeUrl(string $platform, string $state)
    {
        $appId = platform_api_secrets_resolve('meta_app_id');
            $params = [
                'client_id' => $appId,
                'redirect_uri' => meta_oauth_redirect_uri(),
                'state' => $state,
                'scope' => meta_oauth_scopes($platform),
                'response_type' => 'code',
            ];
        
            return 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query($params);
    }

    public function exchangeCode(string $code)
    {
        $appId = platform_api_secrets_resolve('meta_app_id');
            $secret = platform_api_secrets_resolve('meta_app_secret');
            $redirect = meta_oauth_redirect_uri();
        
            $url = 'https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query([
                'client_id' => $appId,
                'client_secret' => $secret,
                'redirect_uri' => $redirect,
                'code' => $code,
            ]);
        
            $res = social_api_service_http_request('GET', $url);
            if (!$res['ok']) {
                return ['success' => false, 'error' => 'Could not exchange authorization code (HTTP ' . (int) ($res['status'] ?? 0) . ').'];
            }
        
            $token = trim((string) ($res['data']['access_token'] ?? ''));
            if ($token === '') {
                return ['success' => false, 'error' => 'Meta did not return an access token.'];
            }
        
            return [
                'success' => true,
                'access_token' => $token,
                'expires_in' => (int) ($res['data']['expires_in'] ?? 0),
            ];
    }

    public function longLivedToken(string $shortToken)
    {
        $appId = platform_api_secrets_resolve('meta_app_id');
            $secret = platform_api_secrets_resolve('meta_app_secret');
        
            $url = 'https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query([
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $secret,
                'fb_exchange_token' => $shortToken,
            ]);
        
            $res = social_api_service_http_request('GET', $url);
            if (!$res['ok']) {
                return ['success' => false, 'error' => 'Could not obtain long-lived token.'];
            }
        
            $token = trim((string) ($res['data']['access_token'] ?? ''));
            if ($token === '') {
                return ['success' => false, 'error' => 'Long-lived token missing from Meta response.'];
            }
        
            return ['success' => true, 'access_token' => $token];
    }

    public function fetchPages(string $userAccessToken)
    {
        $url = 'https://graph.facebook.com/v20.0/me/accounts?' . http_build_query([
                'fields' => 'id,name,username,access_token,instagram_business_account{id,username,name}',
                'access_token' => $userAccessToken,
                'limit' => 50,
            ]);
        
            $res = social_api_service_http_request('GET', $url);
            if (!$res['ok']) {
                return [];
            }
        
            $data = $res['data']['data'] ?? [];
        
            return is_array($data) ? $data : [];
    }

    public function saveFacebookPage(int $userId, array $page)
    {
        $pageId = trim((string) ($page['id'] ?? ''));
            $token = trim((string) ($page['access_token'] ?? ''));
            if ($pageId === '' || $token === '') {
                return false;
            }
        
            $username = trim((string) ($page['username'] ?? ''));
            if ($username === '') {
                $username = 'page_' . $pageId;
            }
        
            meta_oauth_upsert_social_account($userId, [
                'platform' => 'facebook',
                'platform_user_id' => $pageId,
                'username' => $username,
                'display_name' => (string) ($page['name'] ?? 'Facebook Page'),
                'access_token' => $token,
                'refresh_token' => '',
                'token_expires_at' => date('Y-m-d H:i:s', strtotime('+55 days')),
            ]);
        
            return true;
    }

    public function saveInstagramAccount(int $userId, array $page, array $ig)
    {
        $igId = trim((string) ($ig['id'] ?? ''));
            $token = trim((string) ($page['access_token'] ?? ''));
            if ($igId === '' || $token === '') {
                return false;
            }
        
            $username = trim((string) ($ig['username'] ?? ''));
            if ($username === '') {
                $username = 'ig_' . $igId;
            }
        
            meta_oauth_upsert_social_account($userId, [
                'platform' => 'instagram',
                'platform_user_id' => $igId,
                'username' => $username,
                'display_name' => (string) ($ig['name'] ?? 'Instagram'),
                'access_token' => $token,
                'refresh_token' => '',
                'token_expires_at' => date('Y-m-d H:i:s', strtotime('+55 days')),
            ]);
        
            return true;
    }

    public function upsertSocialAccount(int $userId, array $data)
    {
        social_account_upsert($userId, $data);
        
            $account = $this->db->fetchOne(
                'SELECT id FROM social_accounts WHERE user_id = :uid AND platform = :p LIMIT 1',
                ['uid' => $userId, 'p' => (string) $data['platform']]
            );
            if ($account !== null) {
                job_runner_dispatch('fetch_analytics', [
                    'user_id' => $userId,
                    'social_account_id' => (int) ($account['id'] ?? 0),
                ]);
            }
    }

    public function completeConnection(int $userId, string $platform, string $code)
    {
        $platform = strtolower(trim($platform));
            if (!in_array($platform, meta_oauth_allowed_platforms(), true)) {
                return ['success' => false, 'message' => 'Unsupported platform for Meta OAuth.'];
            }
        
            $exchange = meta_oauth_exchange_code($code);
            if (!$exchange['success']) {
                return ['success' => false, 'message' => (string) ($exchange['error'] ?? 'Token exchange failed.')];
            }
        
            $userToken = (string) $exchange['access_token'];
            $long = meta_oauth_long_lived_token($userToken);
            if ($long['success']) {
                $userToken = (string) $long['access_token'];
            }
        
            $pages = meta_oauth_fetch_pages($userToken);
            if ($pages === []) {
                return ['success' => false, 'message' => 'No Facebook Pages found. Link a Page to your Meta app and try again.'];
            }
        
            $saved = false;
            if ($platform === 'facebook') {
                foreach ($pages as $page) {
                    if (meta_oauth_save_facebook_page($userId, $page)) {
                        $saved = true;
                        break;
                    }
                }
                if (!$saved) {
                    return ['success' => false, 'message' => 'Could not save Facebook Page connection.'];
                }
        
                return ['success' => true, 'message' => 'Facebook connected successfully.'];
            }
        
            foreach ($pages as $page) {
                $ig = $page['instagram_business_account'] ?? null;
                if (!is_array($ig)) {
                    continue;
                }
                if (meta_oauth_save_instagram_account($userId, $page, $ig)) {
                    $saved = true;
                    break;
                }
            }
        
            if (!$saved) {
                return [
                    'success' => false,
                    'message' => 'No Instagram Business account found on your Pages. Connect IG to a Facebook Page in Meta Business Suite.',
                ];
            }
        
            return ['success' => true, 'message' => 'Instagram connected successfully.'];
    }

}
