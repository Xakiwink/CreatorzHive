<?php

declare(strict_types=1);

/**
 * Optional browser / SPA CORS for JSON API responses.
 * Set API_CORS_ORIGINS in .env to a comma-separated list of allowed Origin values
 * (e.g. https://app.example.com,http://localhost:5173). Empty = disabled.
 */
function api_cors_allowed_origins(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $raw = trim((string) env('API_CORS_ORIGINS', ''));
    if ($raw === '') {
        return $cache = [];
    }

    $parts = array_map('trim', explode(',', $raw));

    return $cache = array_values(array_filter($parts, static function ($o) {
        return $o !== '';
    }));
}

function api_cors_request_origin(): string
{
    return trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
}

function api_cors_origin_is_allowed(string $origin): bool
{
    if ($origin === '') {
        return false;
    }

    return in_array($origin, api_cors_allowed_origins(), true);
}

/** Emit Access-Control-* headers when Origin matches configured allowlist. */
function api_cors_emit_headers(): void
{
    $origin = api_cors_request_origin();
    if (!api_cors_origin_is_allowed($origin)) {
        return;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Expose-Headers: Content-Type');
}

/**
 * Respond to CORS preflight without requiring a registered route.
 * Call from front controller before router_dispatch when API_CORS_ORIGINS is set.
 */
function api_cors_handle_preflight(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'OPTIONS') {
        return;
    }

    if (api_cors_allowed_origins() === []) {
        return;
    }

    $origin = api_cors_request_origin();
    if (!api_cors_origin_is_allowed($origin)) {
        return;
    }

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}
