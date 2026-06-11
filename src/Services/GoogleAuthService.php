<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use function base_url_path;
use function env;

final class GoogleAuthService
{
    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function redirectUri(): string
    {
        $override = trim((string) env('GOOGLE_AUTH_REDIRECT_URI', ''));
        if ($override !== '') {
            return $override;
        }

        $base = rtrim((string) env('APP_URL', 'http://localhost'), '/');
        $path = base_url_path();

        return $base . ($path === '' ? '' : $path) . '/?route=google-callback';
    }

    public function authorizeUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * @return array{ok: bool, access_token?: string, id_token?: string, error?: string}
     */
    public function exchangeCode(string $code): array
    {
        $res = $this->httpFormPost('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'error' => (string) ($res['error'] ?? 'Token exchange failed')];
        }

        $data = $res['data'] ?? [];
        $token = trim((string) ($data['access_token'] ?? ''));
        if ($token === '') {
            return ['ok' => false, 'error' => 'Google did not return an access token'];
        }

        return [
            'ok' => true,
            'access_token' => $token,
            'id_token' => trim((string) ($data['id_token'] ?? '')),
        ];
    }

    /**
     * @return array{ok: bool, profile?: array<string, mixed>, error?: string}
     */
    public function fetchUserProfile(string $accessToken): array
    {
        $res = $this->httpGet(
            'https://openidconnect.googleapis.com/v1/userinfo',
            ['Authorization: Bearer ' . $accessToken]
        );

        if (!$res['ok']) {
            return ['ok' => false, 'error' => (string) ($res['error'] ?? 'Could not load Google profile')];
        }

        $data = $res['data'] ?? [];
        $sub = trim((string) ($data['sub'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($sub === '' || $email === '') {
            return ['ok' => false, 'error' => 'Google account must have a verified email address'];
        }

        return [
            'ok' => true,
            'profile' => [
                'google_id' => $sub,
                'email' => $email,
                'name' => trim((string) ($data['name'] ?? '')),
                'given_name' => trim((string) ($data['given_name'] ?? '')),
                'family_name' => trim((string) ($data['family_name'] ?? '')),
                'picture' => trim((string) ($data['picture'] ?? '')),
                'email_verified' => !empty($data['email_verified']),
            ],
        ];
    }

    private function clientId(): string
    {
        $id = trim((string) env('GOOGLE_CLIENT_ID', ''));
        if ($id !== '') {
            return $id;
        }

        if (\function_exists('platform_api_secrets_resolve')) {
            return trim((string) platform_api_secrets_resolve('google_client_id'));
        }

        return '';
    }

    private function clientSecret(): string
    {
        $secret = trim((string) env('GOOGLE_CLIENT_SECRET', ''));
        if ($secret !== '') {
            return $secret;
        }

        if (\function_exists('platform_api_secrets_resolve')) {
            return trim((string) platform_api_secrets_resolve('google_client_secret'));
        }

        return '';
    }

    /**
     * @param array<string, string> $fields
     *
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    private function httpFormPost(string $url, array $fields): array
    {
        if (!\function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL extension is not enabled'];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Could not initialize HTTP client'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        return $this->finishCurl($ch);
    }

    /**
     * @param list<string> $headers
     *
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    private function httpGet(string $url, array $headers = []): array
    {
        if (!\function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL extension is not enabled'];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Could not initialize HTTP client'];
        }

        $headers[] = 'Accept: application/json';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        return $this->finishCurl($ch);
    }

    /**
     * @param resource $ch
     *
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    private function finishCurl($ch): array
    {
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => $err !== '' ? $err : 'HTTP request failed'];
        }

        $json = json_decode((string) $raw, true);
        if (!\is_array($json)) {
            return ['ok' => false, 'error' => 'Invalid JSON from Google'];
        }

        if ($status < 200 || $status >= 300) {
            $msg = (string) ($json['error_description'] ?? $json['error'] ?? 'HTTP ' . $status);

            return ['ok' => false, 'error' => $msg];
        }

        return ['ok' => true, 'data' => $json];
    }
}
