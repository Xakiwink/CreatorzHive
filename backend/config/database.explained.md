# database.php — Explained

**File:** `backend/config/database.php`

---

## Purpose

Thin config shim that ensures `backend/core/database.php` is available. No constants or settings are defined here — database connection parameters are read from environment variables at connection time inside `db_get_pdo()`.

---

## What It Does

1. Auto-loads `helpers/functions.php` if `env()` is not defined (allows direct inclusion from CLI scripts)
2. Requires `backend/core/database.php` where all actual database functions live

---

## Connection Parameters

The actual database connection parameters live in `backend/core/database.php` and are read from these environment variables:

| Env Var | Default |
|---------|---------|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `creatorz_hive` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | `''` |

---

## Notes

- This file exists primarily to support CLI scripts that need only the database layer (e.g., `scripts/migrate.php`) without loading the full application stack.
- In the main HTTP flow, `backend/bootstrap-procedural.php` loads `core/database.php` directly.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/database.php` | All database functions live here |
| `scripts/migrate.php` | May include this config file directly |
