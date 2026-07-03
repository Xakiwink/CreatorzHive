<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Env, Request, Session, View};
use App\Models\SocialAccount;
use App\Services\Instagram;

class InstagramController
{
    public function settingsPage(): void
    {
        Session::requireAuth();
        $user = Session::user();

        View::render('settings/index', [
            'user'     => $user,
            'accounts' => SocialAccount::forUser((int) $user['id']),
            'success'  => Session::getFlash('success'),
            'error'    => Session::getFlash('error'),
        ]);
    }

    public function connect(): void
    {
        Session::requireAuth();
        $user   = Session::user();
        $userId = (int) $user['id'];

        $appId = Env::get('INSTAGRAM_APP_ID');
        if ($appId === '') {
            Session::flash('error', 'Instagram App ID is not configured.');
            header('Location: /?page=settings'); exit;
        }

        $state = $this->buildState($userId);
        header('Location: ' . Instagram::authorizeUrl($state)); exit;
    }

    public function callback(): void
    {
        $error = Request::get('error') ?: Request::get('error_description');
        if ($error !== '') {
            Session::flash('error', 'Instagram denied access: ' . $error);
            header('Location: /?page=settings'); exit;
        }

        $state  = Request::get('state');
        $userId = $this->verifyState($state);
        if ($userId === null) {
            Session::flash('error', 'Security check failed. Please try connecting again.');
            header('Location: /?page=settings'); exit;
        }

        $code = Request::get('code');
        if ($code === '') {
            Session::flash('error', 'No authorization code received from Instagram.');
            header('Location: /?page=settings'); exit;
        }

        $exchange = Instagram::exchangeCode($code);
        if (!$exchange['ok']) {
            Session::flash('error', 'Token exchange failed: ' . ($exchange['error'] ?? 'unknown error'));
            header('Location: /?page=settings'); exit;
        }

        $token   = $exchange['token'];
        $profile = Instagram::profile($token);

        SocialAccount::upsert($userId, 'instagram', [
            'platform_user_id' => (string) ($profile['id'] ?? ''),
            'username'         => (string) ($profile['username'] ?? ''),
            'display_name'     => (string) ($profile['name'] ?? $profile['username'] ?? ''),
            'access_token'     => $token,
            'refresh_token'    => '',
            'token_expires_at' => date('Y-m-d H:i:s', strtotime('+55 days')),
            'follower_count'   => 0,
        ]);

        Session::flash('success', 'Instagram connected successfully!');

        $sessionUser = Session::user();
        if ($sessionUser !== null && (int) $sessionUser['id'] === $userId) {
            header('Location: /?page=settings'); exit;
        }

        header('Location: /?page=login'); exit;
    }

    private function buildState(int $userId): string
    {
        $nonce   = bin2hex(random_bytes(16));
        $payload = $userId . '.' . $nonce;
        $sig     = hash_hmac('sha256', $payload, Env::get('APP_SECRET'));
        return $payload . '.' . $sig;
    }

    private function verifyState(string $state): ?int
    {
        $parts = explode('.', $state, 3);
        if (count($parts) !== 3) return null;

        [$userIdStr, $nonce, $sig] = $parts;
        $payload  = $userIdStr . '.' . $nonce;
        $expected = hash_hmac('sha256', $payload, Env::get('APP_SECRET'));

        if (!hash_equals($expected, $sig)) return null;

        $id = (int) $userIdStr;
        return $id > 0 ? $id : null;
    }
}
