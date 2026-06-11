<?php

declare(strict_types=1);

function session_start_safe(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (\defined('CREATORZHIVE_PHPUNIT') && CREATORZHIVE_PHPUNIT) {
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        session_cache_limiter('');
        session_start();

        return;
    }

    session_set_cookie_params([
        'lifetime' => (int) env('SESSION_LIFETIME', 120) * 60,
        'path' => '/',
        'secure' => (bool) env('SESSION_SECURE', false),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

function session_set(string $key, $value): void
{
    $_SESSION[$key] = $value;
}

function session_get(string $key, $default = null)
{
    return $_SESSION[$key] ?? $default;
}

function session_has(string $key): bool
{
    return isset($_SESSION[$key]);
}

function session_remove(string $key): void
{
    unset($_SESSION[$key]);
}

function session_destroy_all(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function session_flash(string $key, $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function session_get_flash(string $key)
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function session_regenerate_safe(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function session_set_user(array $user): void
{
    $_SESSION['user'] = $user;
    $_SESSION['_fingerprint'] = session_fingerprint_generate();
}

function session_get_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function session_is_logged_in(): bool
{
    return session_get_user() !== null;
}

function session_user_is_admin(): bool
{
    $user = session_get_user();

    return $user !== null && (string) ($user['role'] ?? '') === 'admin';
}

function session_fingerprint_generate(): string
{
    $ua = strtolower(trim(request_user_agent()));
    $ipScope = session_ip_scope(request_ip());

    return hash('sha256', $ua . '|' . $ipScope);
}

function session_fingerprint_is_valid(): bool
{
    if (!isset($_SESSION['_fingerprint']) || !is_string($_SESSION['_fingerprint'])) {
        return false;
    }

    return hash_equals($_SESSION['_fingerprint'], session_fingerprint_generate());
}

function session_ip_scope(string $ip): string
{
    if (strpos($ip, ':') !== false) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 4));
    }

    $parts = explode('.', $ip);
    if (count($parts) >= 3) {
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
    }

    return $ip;
}
