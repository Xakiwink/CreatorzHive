# cli_bootstrap.php — Explained

**File:** `backend/helpers/cli_bootstrap.php`

---

## Purpose

Shared bootstrap for CLI scripts (`scripts/cron.php`, `scripts/migrate.php`, `scripts/seed.php`, etc.). Provides reusable functions to boot the application stack from the command line.

---

## Functions

### `cli_project_root(): string`
Returns the absolute project root path by going two directories up from `backend/helpers/`. Used to construct paths to other project files.

### `cli_load_env(): void`
Loads `helpers/functions.php` and calls `load_env()` to populate environment variables from `.env`.

### `cli_boot_oop(bool $procedural = true): void`
Boots the full application stack for CLI use:
1. Guard: returns immediately if already called (static `$booted` flag)
2. Loads `vendor/autoload.php` if present
3. Loads `bootstrap-oop.php` → runs `Application::boot()` → creates DI container
4. Loads `core/database.php` for procedural DB functions
5. If `$procedural = true`: also loads `bootstrap-procedural.php` (all compat bridges, middleware stubs, router)

Pass `$procedural = false` for scripts that only need the OOP layer (e.g., migration scripts that don't need the router or CSRF middleware).

### `cli_pdo(): PDO`
Returns a PDO connection for schema/migration scripts:
1. Calls `cli_boot_oop(false)` (OOP layer only, no procedural stack)
2. Tries to get `Connection` from DI container and returns its PDO
3. Falls back to `db_connection()` (direct PDO creation from env vars)

---

## Usage Pattern

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../backend/helpers/cli_bootstrap.php';
cli_load_env();
cli_boot_oop();
// ... use OOP services and repositories
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `scripts/cron.php` | Uses `cli_boot_oop()` |
| `scripts/migrate.php` | Uses `cli_pdo()` |
| `backend/bootstrap-oop.php` | Loaded by `cli_boot_oop()` |
| `backend/bootstrap-procedural.php` | Conditionally loaded by `cli_boot_oop()` |
