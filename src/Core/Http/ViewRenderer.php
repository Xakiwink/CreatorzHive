<?php

declare(strict_types=1);

namespace CreatorzHive\Core\Http;

/**
 * Renders PHP/HTML views under frontend/pages (SRP: view output).
 */
final class ViewRenderer
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): void
    {
        if (function_exists('http_view')) {
            http_view($template, $data);

            return;
        }

        $path = $this->resolvePath($template);
        if (is_file($path . '.php')) {
            extract($data, EXTR_SKIP);
            ob_start();
            require $path . '.php';
            $html = (string) ob_get_clean();
            echo $html;
            exit;
        }

        if (is_file($path . '.html')) {
            echo file_get_contents($path . '.html');
            exit;
        }

        throw new \RuntimeException('View not found: ' . $template);
    }

    private function resolvePath(string $template): string
    {
        $root = function_exists('base_path')
            ? base_path('frontend/pages/' . ltrim($template, '/'))
            : dirname(__DIR__, 3) . '/frontend/pages/' . ltrim($template, '/');

        return $root;
    }
}
