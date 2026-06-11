<?php

declare(strict_types=1);

/**
 * Recursively trim and strip tags from string values in request input.
 *
 * @param mixed $value Raw value from $_GET, $_POST, or nested array
 *
 * @return mixed Sanitized value (strings cleaned; arrays mapped recursively)
 */
function request_sanitize($value)
{
    if (is_array($value)) {
        return array_map('request_sanitize', $value);
    }

    if (is_string($value)) {
        return trim(strip_tags($value));
    }

    return $value;
}

function request_get(string $key, $default = null)
{
    if (!isset($_GET[$key])) {
        return $default;
    }

    return request_sanitize($_GET[$key]);
}

function request_post(string $key, $default = null)
{
    if (!isset($_POST[$key])) {
        return $default;
    }

    return request_sanitize($_POST[$key]);
}

function request_all(): array
{
    $data = array_merge($_GET, $_POST);
    $sanitized = [];

    foreach ($data as $key => $value) {
        $sanitized[$key] = request_sanitize($value);
    }

    return $sanitized;
}

function request_only(array $keys): array
{
    $all = request_all();
    $subset = [];

    foreach ($keys as $key) {
        if (array_key_exists($key, $all)) {
            $subset[$key] = $all[$key];
        }
    }

    return $subset;
}

function request_has(string $key): bool
{
    $value = request_post($key, request_get($key));

    if ($value === null) {
        return false;
    }

    if (is_string($value)) {
        return trim($value) !== '';
    }

    return !empty($value);
}

function request_file(string $key): ?array
{
    return isset($_FILES[$key]) ? $_FILES[$key] : null;
}

function request_ip(): string
{
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);
        $candidate = trim((string) $parts[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
            return $candidate;
        }
    }

    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    return filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '0.0.0.0';
}

function request_user_agent(): string
{
    return (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
}

function request_is_json(): bool
{
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

    return stripos($contentType, 'application/json') !== false;
}

function request_json_body(): array
{
    $body = file_get_contents('php://input');

    if (!is_string($body) || trim($body) === '') {
        return [];
    }

    $decoded = json_decode($body, true);

    return is_array($decoded) ? $decoded : [];
}
