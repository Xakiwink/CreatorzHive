<?php

declare(strict_types=1);

namespace CreatorzHive\Middleware;

use CreatorzHive\Repositories\UserRepository;
use function response_json;
use function response_redirect;
use function route_url;
use function session_destroy_all;
use function session_get_user;
use function session_set_user;

final class AuthMiddleware
{
    /** @var UserRepository */
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function handle(bool $isApi = false): void
    {
        $sessionUser = session_get_user();
        $userId = (int) ($sessionUser['id'] ?? 0);
        $user = $userId > 0 ? $this->users->findById($userId) : null;

        if ($user === null) {
            if ($isApi) {
                response_json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => [],
                ], 401);
            }

            response_redirect(route_url('login'));
        }

        unset($user['password']);
        session_set_user($user);

        $isActive = $user['is_active'] ?? true;
        if ((int) $isActive !== 1 && $isActive !== true) {
            if ($isApi) {
                response_json([
                    'success' => false,
                    'message' => 'Account inactive',
                    'errors' => [],
                ], 403);
            }

            session_destroy_all();
            response_redirect(route_url('login'));
        }
    }
}
