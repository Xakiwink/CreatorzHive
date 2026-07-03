<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $base = '';

    public static function setBase(string $path): void
    {
        self::$base = rtrim($path, '/');
    }

    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        if (!isset($data['csrf'])) {
            $token = Session::get('_csrf', '');
            if ($token === '') {
                $token = bin2hex(random_bytes(16));
                Session::set('_csrf', $token);
            }
            $data['csrf'] = $token;
        }

        $content = self::capture($view, $data);

        if ($layout !== '') {
            $layoutFile = self::$base . '/layouts/' . $layout . '.php';
            if (is_file($layoutFile)) {
                $data['content'] = $content;
                extract($data);
                include $layoutFile;
                return;
            }
        }

        echo $content;
    }

    public static function capture(string $view, array $data = []): string
    {
        extract($data);
        ob_start();
        $viewFile = self::$base . '/' . $view . '.php';
        if (!is_file($viewFile)) {
            ob_end_clean();
            return '<p>View not found: ' . htmlspecialchars($view, ENT_QUOTES) . '</p>';
        }
        include $viewFile;
        return ob_get_clean() ?: '';
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
