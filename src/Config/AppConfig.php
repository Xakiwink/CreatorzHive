<?php

declare(strict_types=1);

namespace CreatorzHive\Config;

/**
 * Application configuration loaded from environment (SRP: config only).
 */
final class AppConfig
{
    private string $name;
    private string $version;
    private string $url;
    private string $env;
    private bool $debug;
    private string $timezone;
    private int $uploadMaxSize;
    /** @var list<string> */
    private array $allowedImageTypes;
    /** @var list<string> */
    private array $allowedVideoTypes;

    public function __construct()
    {
        $this->name = 'CreatorzHive';
        $this->version = '1.0.0';
        $this->url = (string) $this->envString('APP_URL', 'http://localhost/creatorzhive');
        $this->env = (string) $this->envString('APP_ENV', 'development');
        $this->debug = filter_var($this->envString('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN);
        $this->timezone = 'Africa/Dar_es_Salaam';
        $this->uploadMaxSize = 10 * 1024 * 1024;
        $this->allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $this->allowedVideoTypes = ['video/mp4', 'video/webm'];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function env(): string
    {
        return $this->env;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function uploadMaxSize(): int
    {
        return $this->uploadMaxSize;
    }

    /** @return list<string> */
    public function allowedImageTypes(): array
    {
        return $this->allowedImageTypes;
    }

    /** @return list<string> */
    public function allowedVideoTypes(): array
    {
        return $this->allowedVideoTypes;
    }

    public function dbHost(): string
    {
        return (string) $this->envString('DB_HOST', '127.0.0.1');
    }

    public function dbPort(): string
    {
        return (string) $this->envString('DB_PORT', '3306');
    }

    public function dbDatabase(): string
    {
        return (string) $this->envString('DB_DATABASE', 'creatorz_hive');
    }

    public function dbUsername(): string
    {
        return (string) $this->envString('DB_USERNAME', 'root');
    }

    public function dbPassword(): string
    {
        return (string) $this->envString('DB_PASSWORD', '');
    }

    public function appSecret(): string
    {
        return (string) $this->envString('APP_SECRET', '');
    }

    private function envString(string $key, string $default): string
    {
        if (!function_exists('env')) {
            return $default;
        }

        $value = env($key, $default);

        return is_string($value) ? $value : $default;
    }
}
