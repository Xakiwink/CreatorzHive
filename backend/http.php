<?php

declare(strict_types=1);

function http_json(array $data, int $status = 200): void
{
    response_json($data, $status);
}

function http_json_success($data = null, string $message = 'Success'): void
{
    http_json([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
}

function http_json_error(string $message, int $status = 400, array $errors = []): void
{
    http_json([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
    ], $status);
}

function http_view(string $template, array $data = []): void
{
    $path = base_path('frontend/pages/' . ltrim($template, '/'));

    if (is_file($path . '.php')) {
        extract($data, EXTR_SKIP);
        ob_start();
        require $path . '.php';
        response_html((string) ob_get_clean());

        return;
    }

    if (is_file($path . '.html')) {
        response_html((string) file_get_contents($path . '.html'));

        return;
    }

    error_handler_not_found('View not found: ' . $template);
}

function http_redirect(string $url): void
{
    response_redirect($url);
}

function http_is_post(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
}

function http_is_get(): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET';
}

function http_current_user(): ?array
{
    return session_get_user();
}
