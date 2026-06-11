# Application.php — Explained

**File:** `src/Core/Application.php`
**Namespace:** `CreatorzHive\Core`

---

## Purpose

`Application` is the **composition root** — the single entry point that bootstraps the entire OOP layer. It creates the DI container, triggers dependency registration, and stores global references for compatibility with the procedural layer.

Called once per request from `backend/bootstrap-oop.php` via `Application::boot()`.

---

## Imports

| Import | Why |
|--------|-----|
| `CreatorzHive\Config\AppConfig` | Typed configuration (passed to Container) |
| `CreatorzHive\Core\Database\Connection` | PDO wrapper — extracted from container to set global |
| `CreatorzHive\Providers\AppServiceProvider` | Registers all service bindings |

---

## Class: Application

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$container` | `Container` | The DI container. Private — only accessible via `container()` or `get()` |

### Methods

#### `__construct(Container $container)` — private
Stores the container. Private constructor enforces `boot()` factory pattern.

#### `static boot(): self` — public static
The main entry point. Called once from `bootstrap-oop.php`.

Steps:
1. Creates `Container` instance
2. Calls `AppServiceProvider::register($container)` — wires all bindings
3. Creates `Application` instance
4. Stores `$GLOBALS['cz_container']` — allows procedural compat code to resolve services
5. Stores `$GLOBALS['cz_app']` — global app instance reference
6. Extracts PDO connection to `$GLOBALS['_cz_pdo']` — used by procedural `db_query()` functions
7. Returns the `Application` instance

**Returns:** `Application` (the new instance)

#### `container(): Container`
Returns the DI container.

#### `get(string $id): object`
Shorthand for `$container->get($id)`. Resolves any registered service by class name.

**Example:**
```php
$app = Application::instance();
$authService = $app->get(AuthService::class);
```

#### `static instance(): ?Application`
Returns the current Application instance from `$GLOBALS['cz_app']`, or `null` if not bootstrapped.

---

## Security Implications

- Stores PDO connection in `$GLOBALS['_cz_pdo']` — accessible to any procedural code. This is intentional for compat but increases the attack surface if procedural code is compromised.
- `boot()` should only be called once. Calling it twice would re-register all services, which is harmless but wasteful.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/bootstrap-oop.php` | Calls `Application::boot()` |
| `src/Providers/AppServiceProvider.php` | Called by `boot()` to register all services |
| `src/Core/Container.php` | Created by `boot()` |
| `src/Core/Database/Connection.php` | Extracted from container to set `$GLOBALS['_cz_pdo']` |
| `backend/core/database.php` | Uses `$GLOBALS['_cz_pdo']` for procedural DB access |
| `backend/helpers/functions.php` | `app_connection()` calls `Application::instance()` |
