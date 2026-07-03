<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

class Instagram
{
    private const AUTH_URL  = 'https://www.instagram.com/oauth/authorize';
    private const TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
    private const API       = 'https://graph.instagram.com/v25.0';

    private const SCOPES = 'instagram_business_basic,instagram_business_manage_messages,'
        . 'instagram_business_manage_comments,instagram_business_content_publish,'
        . 'instagram_business_manage_insights';

    public static function redirectUri(): string
    {
        return rtrim(Env::get('APP_URL'), '/') . '/?page=instagram-callback';
    }

    public static function authorizeUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'     => Env::get('INSTAGRAM_APP_ID'),
            'redirect_uri'  => self::redirectUri(),
            'scope'         => self::SCOPES,
            'response_type' => 'code',
            'state'         => $state,
        ]);
    }

    public static function exchangeCode(string $code): array
    {
        $res = self::postForm(self::TOKEN_URL, [
            'client_id'     => Env::get('INSTAGRAM_APP_ID'),
            'client_secret' => Env::get('INSTAGRAM_APP_SECRET'),
            'redirect_uri'  => self::redirectUri(),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$res['ok']) {
            $msg = $res['data']['error_message']
                ?? $res['data']['error_description']
                ?? ('HTTP ' . $res['status']);
            return ['ok' => false, 'error' => $msg];
        }

        $token = trim((string) ($res['data']['access_token'] ?? ''));
        if ($token === '') {
            return ['ok' => false, 'error' => 'No access_token in response: ' . json_encode($res['data'])];
        }

        return ['ok' => true, 'token' => $token];
    }

    public static function profile(string $token): array
    {
        $res = self::get(self::API . '/me', [
            'fields'       => 'id,username,name,account_type',
            'access_token' => $token,
        ]);
        return ($res['ok'] && is_array($res['data'])) ? $res['data'] : [];
    }

    public static function publish(string $token, string $igUserId, string $imageUrl, string $caption): array
    {
        $create = self::post(self::API . '/' . rawurlencode($igUserId) . '/media', $token, [
            'image_url' => $imageUrl,
            'caption'   => $caption,
        ]);

        if (!$create['ok']) {
            return ['ok' => false, 'error' => 'Media container failed: ' . ($create['data']['error']['message'] ?? 'unknown')];
        }

        $containerId = (string) ($create['data']['id'] ?? '');
        if ($containerId === '') {
            return ['ok' => false, 'error' => 'No container ID returned'];
        }

        for ($i = 0; $i < 5; $i++) {
            $status = self::get(self::API . '/' . rawurlencode($containerId), [
                'fields'       => 'status_code',
                'access_token' => $token,
            ]);
            $code = (string) ($status['data']['status_code'] ?? 'IN_PROGRESS');
            if ($code === 'FINISHED') break;
            if ($code === 'ERROR' || $code === 'EXPIRED') {
                return ['ok' => false, 'error' => 'Container status: ' . $code];
            }
            if ($i < 4) sleep(2);
        }

        $pub = self::post(self::API . '/' . rawurlencode($igUserId) . '/media_publish', $token, [
            'creation_id' => $containerId,
        ]);

        if (!$pub['ok']) {
            return ['ok' => false, 'error' => 'Publish failed: ' . ($pub['data']['error']['message'] ?? 'unknown')];
        }

        $postId = (string) ($pub['data']['id'] ?? $containerId);
        return ['ok' => true, 'post_id' => $postId];
    }

    private static function postForm(string $url, array $fields): array
    {
        return self::curl('POST', $url, http_build_query($fields), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    private static function post(string $url, string $token, array $data): array
    {
        return self::curl('POST', $url, http_build_query($data), [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    private static function get(string $url, array $params = []): array
    {
        if ($params) $url .= '?' . http_build_query($params);
        return self::curl('GET', $url);
    }

    private static function curl(string $method, string $url, string $body = '', array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'cURL not available'];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => 'curl_init failed'];
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $opts);
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'status' => 0, 'data' => [], 'error' => $err ?: 'Request failed'];
        }

        $data = json_decode((string) $raw, true);
        $ok   = $status >= 200 && $status < 300;

        return [
            'ok'     => $ok,
            'status' => $status,
            'data'   => is_array($data) ? $data : ['raw' => (string) $raw],
            'error'  => $ok ? null : 'HTTP ' . $status,
        ];
    }
}
