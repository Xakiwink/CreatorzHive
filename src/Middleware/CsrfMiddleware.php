<?php

declare(strict_types=1);

namespace CreatorzHive\Middleware;

use function request_post;
use function response_json;
use function session_get;
use function session_has;
use function session_set;

final class CsrfMiddleware
{
    public function validatePost(): void
    {
        $authHeader = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (stripos($authHeader, 'Bearer ') === 0) {
            return;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        $token = (string) request_post('_token', '');
        $sessionToken = (string) session_get('_csrf_token', '');

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            response_json([
                'success' => false,
                'message' => 'Invalid CSRF token',
                'errors' => [],
            ], 403);
        }
    }

    public function generateToken(): string
    {
        if (!session_has('_csrf_token')) {
            session_set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) session_get('_csrf_token');
    }

    public function token(): string
    {
        return $this->generateToken();
    }
}
