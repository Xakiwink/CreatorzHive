# Container.php — Explained

**File:** `src/Core/Container.php`
**Namespace:** `CreatorzHive\Core`

---

## Purpose

A minimal **dependency injection container** (service locator). Stores object instances and factory callables. When a service is first resolved, its factory is called and the result is cached as a singleton.

---

## Class: Container

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$instances` | `array<string, object>` | Cached singleton instances |
| `$factories` | `array<string, callable>` | Factory functions for lazy construction |

### Methods

#### `set(string $id, object $instance): void`
Registers a pre-built singleton directly. Used for simple objects that don't need lazy construction (e.g., `AppConfig`, `JsonResponder`).

**Example:**
```php
$container->set(AppConfig::class, new AppConfig());
```

#### `factory(string $id, callable $factory): void`
Registers a factory callable. The factory receives the container as its argument, allowing access to other registered services. The factory is only called once; the result is cached.

**Example:**
```php
$container->factory(AuthService::class, static function (Container $c): AuthService {
    return new AuthService($c->get(Connection::class));
});
```

#### `get(string $id): object`
Resolves a service by its class string ID.
1. Returns cached singleton if exists
2. Calls factory if registered, caches result, returns it
3. Throws `RuntimeException` if neither exists

**Example:**
```php
$auth = $container->get(AuthService::class);
```

#### `has(string $id): bool`
Returns `true` if the ID is registered (either as singleton or factory). Does not resolve the service.

---

## Design Decisions

- **No auto-wiring**: Dependencies must be explicitly wired in `AppServiceProvider`. This is intentional — explicit is better than magic for a PHP 7.4 project.
- **Singleton by default**: First resolution caches forever. No transient/scoped lifetimes.
- **Type safety**: Factory must return an `object`. Throws if not.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Application.php` | Creates and owns the Container |
| `src/Providers/AppServiceProvider.php` | Calls `set()` and `factory()` to register all services |
| All Controller, Service, Repository classes | Resolved from this container |
