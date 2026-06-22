<?php

declare(strict_types=1);

if (!function_exists('load_env')) {
    function load_env(?string $envPath = null): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $path = $envPath ?? base_path('.env');

        if (!is_file($path) || !is_readable($path)) {
            $loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            $loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            $value = trim($value, "\"'");

            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            putenv(sprintf('%s=%s', $key, $value));

            // Force set in $_SERVER for environments where putenv() doesn't work
            $_SERVER[$key] = $value;
        }

        $loaded = true;
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        // Check $_ENV first, then getenv(), then $_SERVER (fallback for restricted servers)
        $value = $_ENV[$key] ?? getenv($key) ?? $_SERVER[$key] ?? null;

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        switch (strtolower((string) $value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
            default:
                return $value;
        }
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $public = base_path('public');

        return $path === '' ? $public : $public . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('base_url_path')) {
    /**
     * URL path prefix for subdirectory installs (no trailing slash).
     * Set APP_URL=http://localhost/myapp or APP_BASE_PATH=myapp
     */
    function base_url_path(): string
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $override = trim((string) env('APP_BASE_PATH', ''), '/');
        if ($override !== '') {
            $cached = '/' . $override;

            return $cached;
        }

        $parsed = parse_url((string) env('APP_URL', ''), PHP_URL_PATH);
        $path = is_string($parsed) ? trim($parsed, '/') : '';

        $cached = $path === '' ? '' : '/' . $path;

        return $cached;
    }
}

if (!function_exists('asset_url')) {
    /** Absolute web path to static files under project root, e.g. /creatorzhive/frontend/css/main.css */
    function asset_url(string $path): string
    {
        $base = base_url_path();
        $path = ltrim($path, '/');

        return ($base === '' ? '' : $base) . '/' . $path;
    }
}

if (!function_exists('upload_url_needs_public_segment')) {
    /**
     * True when the web server document root is above public/ (e.g. project root).
     * Then browser URLs must include /public/… so /base/public/uploads/… maps to disk.
     * Override with APP_UPLOADS_PUBLIC_PREFIX=0|1|true|false|auto (default auto).
     */
    function upload_url_needs_public_segment(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $override = env('APP_UPLOADS_PUBLIC_PREFIX', 'auto');
        if ($override === true || $override === 1) {
            return $cached = true;
        }
        if ($override === false || $override === 0) {
            return $cached = false;
        }
        $os = is_string($override) ? strtolower(trim($override)) : '';
        if ($os === '1' || $os === 'true' || $os === 'yes') {
            return $cached = true;
        }
        if ($os === '0' || $os === 'false' || $os === 'no') {
            return $cached = false;
        }

        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && (string) $_SERVER['DOCUMENT_ROOT'] !== ''
            ? realpath((string) $_SERVER['DOCUMENT_ROOT'])
            : false;
        $publicDir = realpath(public_path());
        if ($docRoot === false || $publicDir === false) {
            return $cached = false;
        }

        return $cached = ($docRoot !== $publicDir);
    }
}

if (!function_exists('upload_url')) {
    /** Public URL for files stored under public/ (typically public/uploads/…). */
    function upload_url(string $relativePath): string
    {
        $path = trim($relativePath);
        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        $path = ltrim($path, '/');
        if (upload_url_needs_public_segment() && strpos($path, 'public/') !== 0) {
            $path = 'public/' . $path;
        }
        $base = base_url_path();

        return ($base === '' ? '' : $base) . '/' . $path;
    }
}

if (!function_exists('frontend_user_payload')) {
    /**
     * Normalized session user for window.__USER__ on app pages (sidebar avatar, role, etc.).
     *
     * @param array<string, mixed> $user
     *
     * @return array{id: int, name: string, username: string, email: string, role: string, avatar_url: string}
     */
    function frontend_user_payload(array $user): array
    {
        if (class_exists(\CreatorzHive\Core\Application::class)) {
            $app = \CreatorzHive\Core\Application::instance();
            if ($app !== null) {
                $container = $app->container();
                if ($container->has(\CreatorzHive\Support\UserPayloadFormatter::class)) {
                    return $container->get(\CreatorzHive\Support\UserPayloadFormatter::class)->forApi($user);
                }
            }
        }

        return (new \CreatorzHive\Support\UserPayloadFormatter())->forApi($user);
    }
}

if (!function_exists('frontend_session_user_payload')) {
    /**
     * Normalized logged-in user for window.__USER__ on app pages.
     *
     * @return array{id: int, name: string, username: string, email: string, role: string, avatar_url: string}
     */
    function frontend_session_user_payload(): array
    {
        return frontend_user_payload(session_get_user() ?? []);
    }
}

if (!function_exists('route_url')) {
    /** Front-controller URL with ?route=… for subdirectory installs */
    function route_url(string $route, array $query = []): string
    {
        $base = base_url_path();
        $params = array_merge(['route' => $route], $query);

        return ($base === '' ? '' : $base) . '/?' . http_build_query($params);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $storage = base_path('backend/storage');

        return $path === '' ? $storage : $storage . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('dd')) {
    function dd(...$vars): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        foreach ($vars as $var) {
            var_dump($var);
        }
        exit(1);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'n-a';
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }
}

if (!function_exists('generateToken')) {
    function generateToken(int $length = 64): string
    {
        if ($length < 2) {
            $length = 2;
        }

        $bytes = (int) ceil($length / 2);

        return substr(bin2hex(random_bytes($bytes)), 0, $length);
    }
}

if (!function_exists('google_auth_is_configured')) {
    function google_auth_is_configured(): bool
    {
        $id = trim((string) env('GOOGLE_CLIENT_ID', ''));
        $secret = trim((string) env('GOOGLE_CLIENT_SECRET', ''));
        if ($id !== '' && $secret !== '') {
            return true;
        }

        if (\class_exists(\CreatorzHive\Core\Application::class)) {
            $app = \CreatorzHive\Core\Application::instance();
            if ($app !== null) {
                $container = $app->container();
                if ($container->has(\CreatorzHive\Services\GoogleAuthService::class)) {
                    return $container->get(\CreatorzHive\Services\GoogleAuthService::class)->isConfigured();
                }
            }
        }

        return false;
    }
}

if (!function_exists('google_auth_start_url')) {
    function google_auth_start_url(string $role = 'creator'): string
    {
        return route_url('google-auth', $role !== '' ? ['role' => $role] : []);
    }
}

if (!function_exists('meta_auth_start_url')) {
    function meta_auth_start_url(string $platform = 'instagram'): string
    {
        return route_url('oauth-connect', ['platform' => $platform]);
    }
}

if (!function_exists('app_connection')) {
    /**
     * OOP database connection when the application container is booted.
     */
    function app_connection(): ?\CreatorzHive\Core\Database\Connection
    {
        if (!class_exists(\CreatorzHive\Core\Application::class)) {
            return null;
        }

        $app = \CreatorzHive\Core\Application::instance();

        return $app !== null ? $app->get(\CreatorzHive\Core\Database\Connection::class) : null;
    }
}
