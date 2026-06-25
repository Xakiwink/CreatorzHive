<?php

declare(strict_types=1);

namespace CreatorzHive\Services;



use function base_url_path;
use function env;
use function job_runner_dispatch;
use function meta_oauth_fetch_pages;
use function meta_oauth_save_facebook_page;
use function meta_oauth_redirect_uri;
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
        if ($platform === 'instagram') {
            return 'instagram_business_basic,instagram_business_content_publish,instagram_business_manage_insights';
        }

        return 'pages_manage_posts,pages_read_engagement,pages_show_list';
    }

    public function authorizeUrl(string $platform, string $state)
    {
        $appId = platform_api_secrets_resolve('meta_app_id');
        $params = [
            'client_id' => $appId,
            'redirect_uri' => meta_oauth_redirect_uri(),
            'state' => $state,
            'scope' => $this->scopes($platform),
            'response_type' => 'code',
        ];

        if ($platform === 'instagram') {
            return 'https://api.instagram.com/oauth/authorize?' . http_build_query($params);
        }

        return 'https://www.facebook.com/v25.0/dialog/oauth?' . http_build_query($params);
    }

    public function exchangeCode(string $code, string $platform = 'facebook')
    {
        $appId = platform_api_secrets_resolve('meta_app_id');
        $secret = platform_api_secrets_resolve('meta_app_secret');
        $redirect = meta_oauth_redirect_uri();

        if ($platform === 'instagram') {
            $res = social_api_service_http_request(
                'POST',
                'https://api.instagram.com/oauth/access_token',
                [],
                [
                    'client_id' => $appId,
                    'client_secret' => $secret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirect,
                    'code' => $code,
                ]
            );
        } else {
            $res = social_api_service_http_request(
                'GET',
                'https://graph.facebook.com/v25.0/oauth/access_token?' . http_build_query([
                    'client_id' => $appId,
                    'client_secret' => $secret,
                    'redirect_uri' => $redirect,
                    'code' => $code,
                ])
            );
        }

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
            'user_id' => (string) ($res['data']['user_id'] ?? ''),
        ];
    }

    public function longLivedToken(string $shortToken, string $platform = 'facebook')
    {
        $secret = platform_api_secrets_resolve('meta_app_secret');

        if ($platform === 'instagram') {
            $res = social_api_service_http_request(
                'GET',
                'https://graph.instagram.com/access_token?' . http_build_query([
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => $secret,
                    'access_token' => $shortToken,
                ])
            );
        } else {
            $appId = platform_api_secrets_resolve('meta_app_id');
            $res = social_api_service_http_request(
                'GET',
                'https://graph.facebook.com/v25.0/oauth/access_token?' . http_build_query([
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $appId,
                    'client_secret' => $secret,
                    'fb_exchange_token' => $shortToken,
                ])
            );
        }

        if (!$res['ok']) {
            return ['success' => false, 'error' => 'Could not obtain long-lived token.'];
        }

        $token = trim((string) ($res['data']['access_token'] ?? ''));
        if ($token === '') {
            return ['success' => false, 'error' => 'Long-lived token missing from Meta response.'];
        }

        return ['success' => true, 'access_token' => $token];
    }

    public function fetchInstagramUser(string $accessToken)
    {
        $url = 'https://graph.instagram.com/v21.0/me?' . http_build_query([
            'fields' => 'id,username,name',
            'access_token' => $accessToken,
        ]);

        $res = social_api_service_http_request('GET', $url);
        if (!$res['ok']) {
            return [];
        }

        return is_array($res['data']) ? $res['data'] : [];
    }

    public function fetchPages(string $userAccessToken)
    {
        $url = 'https://graph.facebook.com/v25.0/me/accounts?' . http_build_query([
                'fields' => 'id,name,username,access_token',
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

            $this->upsertSocialAccount($userId, [
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
        if (!in_array($platform, $this->allowedPlatforms(), true)) {
            return ['success' => false, 'message' => 'Unsupported platform for Meta OAuth.'];
        }

        if ($platform === 'instagram') {
            return $this->completeInstagramConnection($userId, $code);
        }

        return $this->completeFacebookConnection($userId, $code);
    }

    private function completeInstagramConnection(int $userId, string $code)
    {
        $exchange = $this->exchangeCode($code, 'instagram');
        if (!$exchange['success']) {
            return ['success' => false, 'message' => (string) ($exchange['error'] ?? 'Token exchange failed.')];
        }

        $shortToken = (string) $exchange['access_token'];
        $long = $this->longLivedToken($shortToken, 'instagram');
        $userToken = $long['success'] ? (string) $long['access_token'] : $shortToken;

        $igUser = $this->fetchInstagramUser($userToken);
        if ($igUser === []) {
            return ['success' => false, 'message' => 'Could not retrieve Instagram account info.'];
        }

        $igId = trim((string) ($igUser['id'] ?? ''));
        $username = trim((string) ($igUser['username'] ?? ''));
        if ($username === '') {
            $username = 'ig_' . $igId;
        }

        $this->upsertSocialAccount($userId, [
            'platform' => 'instagram',
            'platform_user_id' => $igId,
            'username' => $username,
            'display_name' => (string) ($igUser['name'] ?? $username),
            'access_token' => $userToken,
            'refresh_token' => '',
            'token_expires_at' => date('Y-m-d H:i:s', strtotime('+55 days')),
        ]);

        return ['success' => true, 'message' => 'Instagram connected successfully.'];
    }

    private function completeFacebookConnection(int $userId, string $code)
    {
        $exchange = $this->exchangeCode($code, 'facebook');
        if (!$exchange['success']) {
            return ['success' => false, 'message' => (string) ($exchange['error'] ?? 'Token exchange failed.')];
        }

        $userToken = (string) $exchange['access_token'];
        $long = $this->longLivedToken($userToken, 'facebook');
        if ($long['success']) {
            $userToken = (string) $long['access_token'];
        }

        $pages = meta_oauth_fetch_pages($userToken);
        if ($pages === []) {
            return ['success' => false, 'message' => 'No Facebook Pages found. Link a Page to your Meta app and try again.'];
        }

        $saved = false;
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
}
