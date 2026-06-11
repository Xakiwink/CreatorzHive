<?php

declare(strict_types=1);

namespace CreatorzHive\Core\Http;

/**
 * JSON API envelope responses (SRP: HTTP JSON output).
 */
final class JsonResponder
{
    /**
     * @param array<string, mixed> $data
     */
    public function success(?array $data = null, string $message = 'Success', int $status = 200): void
    {
        $this->send([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param array<string, mixed> $errors
     */
    public function error(string $message, int $status = 400, array $errors = []): void
    {
        $this->send([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function send(array $payload, int $status = 200): void
    {
        if (function_exists('response_json')) {
            response_json($payload, $status);

            return;
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
