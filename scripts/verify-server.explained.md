# verify-server.php — Explained

**File:** `scripts/verify-server.php`

---

## Purpose

Pre-flight check script. Validates that the server environment is ready to run CreatorzHive: database connectivity, upload directory writability, and optionally an HTTP smoke test.

---

## Usage

```bash
php scripts/verify-server.php                              # DB + filesystem checks only
php scripts/verify-server.php http://localhost/creatorzhive  # + HTTP ping
```

Exit code 0 = all checks passed. Exit code 1 = at least one check failed.

---

## Checks Performed

### 1. Database
Runs `SELECT 1 AS ok` via `db_fetch()`. Prints `database: OK` or `database: FAIL — <error>`.

### 2. `uploads/avatars` Directory
- Creates the directory if missing (`mkdir -p`, mode 0775)
- Checks `is_dir()` and `is_writable()`
- Writes and deletes a `.write-test-<random>` file as a live write probe

### 3. Apache Write Warning
Checks directory ownership and permissions using `posix_getpwuid()`. If the directory is not owned by `www-data`/`apache`/`http` and doesn't have world-write, prints a warning with suggested `chmod` or `chown` commands. Checks both `uploads/avatars` and `backend/storage/logs`.

### 4. HTTP Smoke Test (optional)
If a base URL argument is provided, fetches `<base>/?route=ping` with a 5-second timeout using `file_get_contents()` with a stream context. Prints the first 200 characters of the response.

---

## Bootstrap

Uses `cli_boot_oop(false)` — loads OOP layer without procedural compat. Only needs `db_fetch()` from the compat layer, which is available because `cli_boot_oop(true)` loads procedural too. Actually calls `cli_boot_oop(false)` — this loads only OOP infrastructure, not the procedural compat bridges.

> Note: `db_fetch()` is a procedural compat function. The script works because `db_fetch` resolves through the OOP connection object.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/helpers/cli_bootstrap.php` | `cli_boot_oop()`, `cli_load_env()` |
| `backend/core/database.php` | `db_fetch()` |
| `public/uploads/avatars/` | Directory being verified |
| `backend/storage/logs/` | Secondary directory warning check |
