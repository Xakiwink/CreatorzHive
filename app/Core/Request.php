<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function page(): string
    {
        return trim((string) ($_GET['page'] ?? ''));
    }

    public static function get(string $key, string $default = ''): string
    {
        return trim((string) ($_GET[$key] ?? $default));
    }

    public static function post(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }

    public static function postArray(string $key): array
    {
        $v = $_POST[$key] ?? [];
        return is_array($v) ? $v : [];
    }

    public static function csrf(): string
    {
        return (string) ($_POST['_csrf'] ?? '');
    }

    public static function verifyCsrf(): void
    {
        $token = Session::get('_csrf', '');
        if ($token === '' || !hash_equals($token, self::csrf())) {
            http_response_code(403);
            exit('Invalid request.');
        }
    }
}
