<?php

declare(strict_types=1);

function error_handler_wants_json(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $route = (string) ($_GET['route'] ?? '');
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

    return stripos($accept, 'application/json') !== false
        || strpos($route, 'api/') === 0
        || strpos($uri, '/api/') !== false
        || $route !== '';
}

function error_handler_redact_secrets(string $text): string
{
    $text = preg_replace('/password\s*=\s*[^\s]+/i', 'password=[REDACTED]', $text) ?? $text;
    $text = preg_replace('/token\s*=\s*[^\s]+/i', 'token=[REDACTED]', $text) ?? $text;
    $text = preg_replace('/secret\s*=\s*[^\s]+/i', 'secret=[REDACTED]', $text) ?? $text;

    return $text;
}

function error_handler_log(\Throwable $e): void
{
    $dir = storage_path('logs');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return;
    }

    $logFile = $dir . '/error-' . date('Y-m-d') . '.log';

    $message = sprintf(
        "[%s] %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        error_handler_redact_secrets($e->getMessage()),
        $e->getFile(),
        $e->getLine()
    );

    @file_put_contents($logFile, $message, FILE_APPEND);
}

function error_handler_register(): void
{
    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(static function (\Throwable $e): void {
        error_handler_server_error_throwable($e);
    });
}

function error_handler_not_found(string $message = '404 Not Found'): void
{
    if (error_handler_wants_json()) {
        response_json([
            'success' => false,
            'message' => $message,
            'errors' => [],
        ], 404);
    }

    $path = base_path('frontend/pages/errors/404.html');
    if (is_file($path)) {
        response_html((string) file_get_contents($path), 404);
    }

    response_html('<h1>404 Not Found</h1>', 404);
}

function error_handler_server_error_throwable(\Throwable $e): void
{
    if (\defined('CREATORZHIVE_PHPUNIT') && CREATORZHIVE_PHPUNIT) {
        foreach (
            [
                'Tests\\Support\\TestResponseException',
                'Tests\\Support\\TestHtmlResponseException',
                'Tests\\Support\\TestRedirectException',
            ] as $testEx
        ) {
            if (class_exists($testEx, true) && $e instanceof $testEx) {
                throw $e;
            }
        }
    }

    try {
        error_handler_log($e);
        } catch (\Throwable $ignored) {
    }

    $debug = (bool) env('APP_DEBUG', true);

    if (error_handler_wants_json()) {
        $payload = [
            'success' => false,
            'message' => $debug ? $e->getMessage() : 'Internal Server Error',
            'errors' => $debug ? ['trace' => explode("\n", $e->getTraceAsString())] : [],
        ];

        response_json($payload, 500);
    }

    $path = base_path('frontend/pages/errors/500.html');
    if (is_file($path) && !$debug) {
        response_html((string) file_get_contents($path), 500);
    }

    $content = $debug
        ? '<h1>Internal Server Error</h1><pre>' . htmlspecialchars((string) $e) . '</pre>'
        : '<h1>Internal Server Error</h1>';

    response_html($content, 500);
}
