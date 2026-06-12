# migrate.php — Explained

**File:** `scripts/migrate.php`

---

## Purpose

Dual-purpose script: applies the database schema + SQL migrations on first run, and provides job queue maintenance commands for ongoing operations.

---

## Usage

```bash
php scripts/migrate.php              # Apply schema + migrations (default)
php scripts/migrate.php status       # Show job counts by status
php scripts/migrate.php failed       # List up to 50 failed jobs
php scripts/migrate.php retry <id>   # Reset failed job to pending
php scripts/migrate.php flush        # Delete all completed + failed rows
```

---

## Migration Mode (default)

When run with no arguments (or `migrate`):

1. Opens a direct PDO connection via `cli_pdo()` (no OOP stack needed)
2. Executes `database/schema.sql` via `$pdo->exec()`
3. Finds all `database/migrations/*.sql` files, sorts them alphabetically, runs each in order
4. Prints `[OK] <filename>` for each executed file, `[SKIP]` for empty/missing files

> SQL files are run in a single `exec()` call per file — no transaction wrapping. Failures throw a PDO exception.

---

## Job Queue Commands

These subcommands call `cli_boot_oop(true)` (full OOP + compat stack), then delegate to `job_runner_*` functions:

| Command | Function | Output |
|---------|----------|--------|
| `status` | `job_runner_stats_by_status()` | Count per status (pending/running/completed/failed) |
| `failed` | `job_runner_list_failed(50)` | Table of failed jobs: ID, queue, class, attempts, error |
| `retry <id>` | `job_runner_retry_failed($id)` | Resets job to pending; exits 1 if not found |
| `flush` | `job_runner_flush_finished()` | Returns count of deleted rows |

---

## Bootstrap

- Queue commands: `cli_boot_oop(true)` — full OOP + procedural
- Migrate command: `cli_pdo()` — bare PDO connection only (no DI container needed for raw SQL)

---

## Related Files

| File | Relationship |
|------|-------------|
| `database/schema.sql` | Main schema applied first |
| `database/migrations/*.sql` | Applied in alphabetical order after schema |
| `backend/helpers/cli_bootstrap.php` | `cli_pdo()`, `cli_boot_oop()` |
| `backend/core/job_runner.php` | `job_runner_*` functions used by queue commands |
