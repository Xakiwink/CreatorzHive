# seed.php — Explained

**File:** `scripts/seed.php`

---

## Purpose

Inserts demo/test data into the database. Can run all seeds in order, run a single seed file, or drop and recreate the database from scratch before seeding.

---

## Usage

```bash
php scripts/seed.php                  # Run all seeds in order
php scripts/seed.php --fresh          # Drop DB, recreate schema + migrations, then seed all
php scripts/seed.php users            # Run only database/seeds/users.sql
php scripts/seed.php --fresh posts    # Fresh DB + only posts seed
```

---

## Seed Order

Fixed order for referential integrity:
1. `users.sql`
2. `posts.sql`
3. `analytics.sql`
4. `deals.sql`
5. `notifications.sql`

---

## `--fresh` Mode

1. Connects to MySQL **without** a database (admin DSN)
2. Drops `DB_DATABASE` if it exists
3. Creates it with `utf8mb4_unicode_ci` collation
4. Reconnects with the new database
5. Applies `database/schema.sql` (falls back to `schema.sql` in root)
6. Applies all `database/migrations/*.sql` in alphabetical order
7. Continues to seed

> Falls back to root-level `schema.sql` if `database/schema.sql` is missing.

---

## Single Seed (`php scripts/seed.php <name>`)

Resolves `database/seeds/<name>.sql` — `.sql` extension is added automatically if missing. Runs just that file then exits.

---

## Connection

Uses DB credentials from env: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Calls `cli_load_env()` to load `.env` first. Does **not** use `cli_boot_oop()` — creates PDO directly.

---

## Related Files

| File | Relationship |
|------|-------------|
| `database/seeds/` | Seed SQL files consumed here |
| `database/schema.sql` | Applied in `--fresh` mode |
| `scripts/build-posts-seed.php` | Generator that writes `database/seeds/posts.sql` |
| `scripts/build-analytics-seed.php` | Generator that writes `database/seeds/analytics.sql` |
| `backend/helpers/cli_bootstrap.php` | `cli_load_env()`, `base_path()` |
