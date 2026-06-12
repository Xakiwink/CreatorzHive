# `scripts/` — CLI Utilities

## 1. Folder Purpose

Command-line tools for migrations, seeds, cron, and maintenance.

## 2. Scripts

| Script | Purpose |
|--------|---------|
| `migrate.php` | Apply `schema.sql` + migrations |
| `seed.php` | Seed demo users/data |
| `cron.php` | Process `job_queue` (run every minute) |
| `verify-server.php` | Environment checks |
| `encrypt-social-tokens.php` | Encrypt tokens at rest |
| `download-frontend-vendor.sh` | Fetch Chart.js/fonts |

## 3. Cron

```cron
* * * * * php /path/to/creatorzhive/scripts/cron.php
```

## 4. Improvement suggestions

- Lock file to prevent overlapping cron runs.
- Structured logging to stdout for systemd/journald.
