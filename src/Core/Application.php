<?php

declare(strict_types=1);

namespace CreatorzHive\Core;

use CreatorzHive\Config\AppConfig;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Providers\AppServiceProvider;

/**
 * Application bootstrap and service wiring (composition root).
 */
final class Application
{
    /** @var Container */
    private $container;

    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    public static function boot(): self
    {
        $container = new Container();
        AppServiceProvider::register($container);

        $app = new self($container);
        $GLOBALS['cz_container'] = $container;
        $GLOBALS['cz_app'] = $app;

        $pdo = $container->get(Connection::class)->pdo();
        $GLOBALS['_cz_pdo'] = $pdo;

        return $app;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function get(string $id): object
    {
        return $this->container->get($id);
    }

    public static function instance(): ?self
    {
        return $GLOBALS['cz_app'] ?? null;
    }
}
