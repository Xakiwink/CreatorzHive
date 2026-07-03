<?php

declare(strict_types=1);

function response_json(array $data, int $status = 200): void
{
    if (\defined('CREATORZHIVE_PHPUNIT') && CREATORZHIVE_PHPUNIT) {
        throw new \Tests\Support\TestResponseException($data, $status);
    }

    http_response_code($status);
    if (\function_exists('api_cors_emit_headers')) {
        api_cors_emit_headers();
    }
    header('Content-Type: application/json; charset=utf-8');
    $flags = JSON_UNESCAPED_SLASHES;
    if (\defined('JSON_INVALID_UTF8_IGNORE')) {
        $flags |= JSON_INVALID_UTF8_IGNORE;
    }
    $json = json_encode($data, $flags);
    if ($json === false) {
        $json = json_encode([
            'success' => false,
            'message' => 'Could not encode response',
            'errors' => ['json' => json_last_error_msg()],
            'data' => null,
        ], $flags);
    }
    echo $json !== false ? $json : '{"success":false,"message":"Encoding error","errors":[],"data":null}';
    exit;
}

function response_html(string $content, int $status = 200): void
{
    if (\defined('CREATORZHIVE_PHPUNIT') && CREATORZHIVE_PHPUNIT) {
        throw new \Tests\Support\TestHtmlResponseException($content, $status);
    }

    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo $content;
    exit;
}

function response_redirect(string $url, int $status = 302): void
{
    if (\defined('CREATORZHIVE_PHPUNIT') && CREATORZHIVE_PHPUNIT) {
        throw new \Tests\Support\TestRedirectException($url, $status);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    http_response_code($status);
    header('Location: ' . $url);
    exit;
}

function response_not_found_json(string $message = 'Not Found'): void
{
    response_json(['success' => false, 'message' => $message, 'errors' => []], 404);
}

function response_forbidden_json(string $message = 'Forbidden'): void
{
    response_json(['success' => false, 'message' => $message, 'errors' => []], 403);
}

function response_server_error_json(string $message = 'Internal Server Error'): void
{
    response_json(['success' => false, 'message' => $message, 'errors' => []], 500);
}
