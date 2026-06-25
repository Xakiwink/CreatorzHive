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

final class YoutubeOAuthService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function redirectUri(): string
    {
        $override = trim((string) env('YOUTUBE_OAUTH_REDIRECT_URI', ''));
        if ($override !== '') {
            return $override;
        }

        $base = rtrim((string) env('APP_URL', 'http://localhost'), '/');
        $path = base_url_path();

        return $base . ($path === '' ? '' : $path) . '/?route=youtube-callback';
    }

    public function authorizeUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function exchangeCode(string $code): array
    {
        $res = social_api_service_http_request(
            'POST',
            'https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            [
                'code' => $code,
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ]
        );

        if (!$res['ok']) {
            return ['success' => false, 'error' => 'Could not exchange authorization code (HTTP ' . (int) ($res['status'] ?? 0) . ').'];
        }

        $token = trim((string) ($res['data']['access_token'] ?? ''));
        if ($token === '') {
            return ['success' => false, 'error' => 'Google did not return an access token.'];
        }

        return [
            'success' => true,
            'access_token' => $token,
            'refresh_token' => trim((string) ($res['data']['refresh_token'] ?? '')),
            'expires_in' => (int) ($res['data']['expires_in'] ?? 3600),
        ];
    }

    public function fetchChannel(string $accessToken): array
    {
        $res = social_api_service_http_request(
            'GET',
            'https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics&mine=true',
            ['Authorization: Bearer ' . $accessToken]
        );

        if (!$res['ok']) {
            return [];
        }

        $items = $res['data']['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return [];
        }

        return $items[0];
    }

    public function completeConnection(int $userId, string $code): array
    {
        $exchange = $this->exchangeCode($code);
        if (!$exchange['success']) {
            return ['success' => false, 'message' => (string) ($exchange['error'] ?? 'Token exchange failed.')];
        }

        $accessToken = (string) $exchange['access_token'];
        $refreshToken = (string) ($exchange['refresh_token'] ?? '');

        $channel = $this->fetchChannel($accessToken);
        if ($channel === []) {
            return ['success' => false, 'message' => 'No YouTube channel found on this Google account.'];
        }

        $channelId = trim((string) ($channel['id'] ?? ''));
        $title = trim((string) ($channel['snippet']['title'] ?? ''));
        $username = trim((string) ($channel['snippet']['customUrl'] ?? ''));
        if ($username === '') {
            $username = 'yt_' . $channelId;
        }
        $avatar = trim((string) ($channel['snippet']['thumbnails']['default']['url'] ?? ''));

        $expiresAt = date('Y-m-d H:i:s', time() + max(3600, (int) ($exchange['expires_in'] ?? 3600)));

        social_account_upsert($userId, [
            'platform' => 'youtube',
            'platform_user_id' => $channelId,
            'username' => $username,
            'display_name' => $title !== '' ? $title : $username,
            'avatar_url' => $avatar !== '' ? $avatar : null,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresAt,
        ]);

        $account = $this->db->fetchOne(
            'SELECT id FROM social_accounts WHERE user_id = :uid AND platform = :p LIMIT 1',
            ['uid' => $userId, 'p' => 'youtube']
        );
        if ($account !== null) {
            job_runner_dispatch('fetch_analytics', [
                'user_id' => $userId,
                'social_account_id' => (int) ($account['id'] ?? 0),
            ]);
        }

        return ['success' => true, 'message' => 'YouTube channel connected successfully.'];
    }

    private function clientId(): string
    {
        $id = trim((string) env('GOOGLE_CLIENT_ID', ''));
        if ($id !== '') {
            return $id;
        }

        return trim((string) platform_api_secrets_resolve('google_client_id'));
    }

    private function clientSecret(): string
    {
        $secret = trim((string) env('GOOGLE_CLIENT_SECRET', ''));
        if ($secret !== '') {
            return $secret;
        }

        return trim((string) platform_api_secrets_resolve('google_client_secret'));
    }
}
