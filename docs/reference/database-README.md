# `database/` — Schema & Migrations

## 1. Folder Purpose

MySQL schema definition, incremental migrations, and seed data.

## 2. Files

| Path | Purpose |
|------|---------|
| `schema.sql` | Full schema for fresh installs |
| `migrations/*.sql` | Incremental changes (e.g. `users.google_id`) |
| `seeds/` | Demo data via `scripts/seed.php` |

## 3. Auth-related tables

- `users` — `google_id` VARCHAR(64) UNIQUE for Google OAuth
- `email_verifications` — signup verify tokens
- `password_resets` — reset tokens
- `sessions` — optional server session tracking
- `rate_limits` — login throttling

## 4. Commands

```bash
php scripts/migrate.php
php scripts/seed.php
php scripts/seed.php --fresh   # destructive reset
```

## 5. Improvement suggestions

- Migration versioning table if not already tracked by migrate script.
- Document rollback strategy per migration file.
