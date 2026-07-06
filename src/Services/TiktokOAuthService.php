<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use function base_url_path;
use function env;
use function job_runner_dispatch;
use function platform_api_secrets_resolve;
use function social_account_upsert;
use function social_api_service_http_request;
use CreatorzHive\Core\Database\Connection;

final class TiktokOAuthService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function isConfigured(): bool
    {
        return $this->clientKey() !== '' && $this->clientSecret() !== '';
    }

    public function redirectUri(): string
    {
        $override = trim((string) env('TIKTOK_OAUTH_REDIRECT_URI', ''));
        if ($override !== '') {
            return $override;
        }

        $base = rtrim((string) env('APP_URL', 'http://localhost'), '/');
        $path = base_url_path();

        return $base . ($path === '' ? '' : $path) . '/?route=tiktok-callback';
    }

    public function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    public function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    public function authorizeUrl(string $state, string $codeVerifier): string
    {
        $params = [
            'client_key'            => $this->clientKey(),
            'redirect_uri'          => $this->redirectUri(),
            'response_type'         => 'code',
            'scope'                 => 'user.info.basic,user.info.stats,video.publish',
            'state'                 => $state,
            'code_challenge'        => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ];

        return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query($params);
    }

    public function exchangeCode(string $code, string $codeVerifier): array
    {
        $res = $this->postForm('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key'    => $this->clientKey(),
            'client_secret' => $this->clientSecret(),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUri(),
            'code_verifier' => $codeVerifier,
        ]);

        if (!$res['ok']) {
            return ['success' => false, 'error' => 'Could not exchange authorization code (HTTP ' . (int) ($res['status'] ?? 0) . ').'];
        }

        $token = trim((string) ($res['data']['access_token'] ?? ''));
        if ($token === '') {
            return ['success' => false, 'error' => 'TikTok did not return an access token.'];
        }

        return [
            'success'       => true,
            'access_token'  => $token,
            'refresh_token' => trim((string) ($res['data']['refresh_token'] ?? '')),
            'expires_in'    => (int) ($res['data']['expires_in'] ?? 86400),
            'open_id'       => trim((string) ($res['data']['open_id'] ?? '')),
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $res = $this->postForm('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key'    => $this->clientKey(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$res['ok']) {
            return ['success' => false, 'error' => 'Token refresh failed (HTTP ' . (int) ($res['status'] ?? 0) . ').'];
        }

        $token = trim((string) ($res['data']['access_token'] ?? ''));
        if ($token === '') {
            return ['success' => false, 'error' => 'TikTok did not return a refreshed access token.'];
        }

        return [
            'success'      => true,
            'access_token' => $token,
            'expires_in'   => (int) ($res['data']['expires_in'] ?? 86400),
        ];
    }

    public function fetchUserInfo(string $accessToken): array
    {
        $res = social_api_service_http_request(
            'GET',
            'https://open.tiktokapis.com/v2/user/info/?fields=open_id,display_name,avatar_url,follower_count',
            ['Authorization: Bearer ' . $accessToken]
        );

        if (!$res['ok']) {
            return [];
        }

        $user = $res['data']['data']['user'] ?? [];

        return is_array($user) ? $user : [];
    }

    public function completeConnection(int $userId, string $code, string $codeVerifier): array
    {
        $exchange = $this->exchangeCode($code, $codeVerifier);
        if (!$exchange['success']) {
            return ['success' => false, 'message' => (string) ($exchange['error'] ?? 'Token exchange failed.')];
        }

        $accessToken = (string) $exchange['access_token'];
        $refreshToken = (string) ($exchange['refresh_token'] ?? '');

        $profile = $this->fetchUserInfo($accessToken);
        $openId = trim((string) ($profile['open_id'] ?? $exchange['open_id'] ?? ''));
        if ($openId === '') {
            return ['success' => false, 'message' => 'Could not read TikTok account details.'];
        }

        $displayName = trim((string) ($profile['display_name'] ?? ''));
        $username = $displayName !== '' ? $displayName : ('tt_' . $openId);
        $avatar = trim((string) ($profile['avatar_url'] ?? ''));
        $followers = (int) ($profile['follower_count'] ?? 0);

        $expiresAt = gmdate('Y-m-d H:i:s', time() + max(3600, (int) ($exchange['expires_in'] ?? 86400)));

        social_account_upsert($userId, [
            'platform'         => 'tiktok',
            'platform_user_id' => $openId,
            'username'         => $username,
            'display_name'     => $displayName !== '' ? $displayName : $username,
            'avatar_url'       => $avatar !== '' ? $avatar : null,
            'access_token'     => $accessToken,
            'refresh_token'    => $refreshToken,
            'token_expires_at' => $expiresAt,
            'follower_count'   => $followers,
        ]);

        $account = $this->db->fetchOne(
            'SELECT id FROM social_accounts WHERE user_id = :uid AND platform = :p LIMIT 1',
            ['uid' => $userId, 'p' => 'tiktok']
        );
        if ($account !== null) {
            job_runner_dispatch('fetch_analytics', [
                'user_id' => $userId,
                'social_account_id' => (int) ($account['id'] ?? 0),
            ]);
        }

        return ['success' => true, 'message' => 'TikTok account connected successfully.'];
    }

    private function postForm(string $url, array $fields): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'cURL not available'];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'curl_init failed'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'status' => $status, 'data' => [], 'error' => $err];
        }

        $data = json_decode((string) $raw, true);

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'data'   => is_array($data) ? $data : ['raw' => (string) $raw],
            'error'  => $status >= 200 && $status < 300 ? null : 'HTTP ' . $status,
        ];
    }

    private function clientKey(): string
    {
        $key = trim((string) env('TIKTOK_CLIENT_KEY', ''));
        if ($key !== '') {
            return $key;
        }

        return trim((string) platform_api_secrets_resolve('tiktok_client_key'));
    }

    private function clientSecret(): string
    {
        $secret = trim((string) env('TIKTOK_CLIENT_SECRET', ''));
        if ($secret !== '') {
            return $secret;
        }

        return trim((string) platform_api_secrets_resolve('tiktok_client_secret'));
    }
}
