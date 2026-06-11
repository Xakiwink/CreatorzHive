<?php

declare(strict_types=1);

namespace CreatorzHive\Core;

use RuntimeException;

/**
 * Minimal dependency injection container (service locator).
 */
final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $factories = [];

    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function factory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $instance = ($this->factories[$id])($this);
            if (!is_object($instance)) {
                throw new RuntimeException("Factory for {$id} did not return an object.");
            }
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new RuntimeException("Service not registered: {$id}");
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]);
    }
}
