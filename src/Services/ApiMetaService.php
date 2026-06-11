<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Middleware\CsrfMiddleware;
use CreatorzHive\Support\UserPayloadFormatter;
use function base_url_path;

final class ApiMetaService
{
    /** @var CsrfMiddleware */
    private $csrf;

    /** @var UserPayloadFormatter */
    private $userPayload;

    public function __construct(CsrfMiddleware $csrf, UserPayloadFormatter $userPayload)
    {
        $this->csrf = $csrf;
        $this->userPayload = $userPayload;
    }

    /**
     * @param array<string, mixed> $sessionUser
     *
     * @return array<string, mixed>
     */
    public function authenticatedClientMeta(array $sessionUser): array
    {
        return [
            'user' => $this->userPayload->forApi($sessionUser),
            'base_url_path' => base_url_path(),
            'csrf_token' => $this->csrf->token(),
            'csrf_field' => '_token',
            'post_content_type' => 'application/x-www-form-urlencoded',
            'note' => 'Include _token in POST bodies unless Authorization: Bearer … is used (CSRF bypass for bearer).',
        ];
    }

    /**
     * @param list<string> $middleware
     */
    public function routeVisibleForRole(array $middleware, string $role): bool
    {
        $role = strtolower(trim($role));

        foreach ($middleware as $m) {
            if ($m === 'role:admin' && $role !== 'admin') {
                return false;
            }
        }

        if (in_array('non_admin', $middleware, true) && $role === 'admin') {
            return false;
        }

        return true;
    }

    /**
     * @return list<array{method: string, route: string, middleware: list<string>}>
     */
    public function catalogRoutesForRole(string $role): array
    {
        $routes = $GLOBALS['cz_router_routes'] ?? [];
        $list = [];

        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            foreach (($routes[$method] ?? []) as $name => $def) {
                if (empty($def['is_api'])) {
                    continue;
                }
                $mw = $def['middleware'] ?? [];
                if (!$this->routeVisibleForRole($mw, $role)) {
                    continue;
                }
                $list[] = [
                    'method' => $method,
                    'route' => $name,
                    'middleware' => $mw,
                ];
            }
        }

        usort($list, static function (array $a, array $b): int {
            $c = strcmp((string) $a['route'], (string) $b['route']);

            return $c !== 0 ? $c : strcmp((string) $a['method'], (string) $b['method']);
        });

        return $list;
    }
}
