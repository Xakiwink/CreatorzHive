<?php
declare(strict_types=1);

namespace App\Core;

class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded || !is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \"'");
            if ($key === '') continue;
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            @putenv("$key=$value");
        }

        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        $v = $_ENV[$key] ?? getenv($key) ?: ($_SERVER[$key] ?? null);
        return ($v === null || $v === false) ? $default : (string) $v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = strtolower(self::get($key));
        if ($v === 'true'  || $v === '1') return true;
        if ($v === 'false' || $v === '0') return false;
        return $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return $v !== '' ? (int) $v : $default;
    }
}
