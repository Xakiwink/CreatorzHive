<?php

declare(strict_types=1);

namespace CreatorzHive\Middleware;

use function http_view;
use function response_json;
use function router_is_api_request;
use function session_get_user;

final class RoleMiddleware
{
    public function requireRoles(string ...$roles): void
    {
        $user = session_get_user();

        if ($user === null) {
            response_json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => [],
            ], 401);
        }

        $role = (string) ($user['role'] ?? '');
        if (!in_array($role, $roles, true)) {
            response_json([
                'success' => false,
                'message' => 'Forbidden',
                'errors' => [],
            ], 403);
        }
    }

    public function requireNonAdmin(): void
    {
        $user = session_get_user();

        if ($user === null) {
            response_json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => [],
            ], 401);
        }

        if ((string) ($user['role'] ?? '') === 'admin') {
            $message = 'Admins are not allowed to access content routes.';
            if (!router_is_api_request()) {
                http_view('errors/403-admin', [
                    'message' => $message,
                ]);
            }

            response_json([
                'success' => false,
                'message' => $message,
                'errors' => [],
            ], 403);
        }
    }
}
