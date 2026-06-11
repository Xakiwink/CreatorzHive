# Deployment (shared hosting / VPS)

## 1. Upload files

Upload the project over FTP/SFTP or clone from Git on the server. Keep the same directory layout (`public/`, `backend/`, `frontend/`, `database/`, etc.).

## 2. Document root

Configure the web server so the **document root** is the `public/` folder. The front controller is `public/index.php`. Do not expose `backend/`, `database/`, or `.env` as browsable URLs (the included `.htaccess` rules help when using Apache).

## 3. Environment

- Copy `.env.example` to `.env` on the server.
- Set `APP_ENV=production` and **`APP_DEBUG=false`**.
- Use strong `DB_*` credentials and restrict MySQL remote access if possible.
- Configure **SMTP** (`MAIL_*`) for verification and password reset emails.

## 4. Dependencies and database

On the server (SSH):

```bash
composer install --no-dev --optimize-autoloader
php scripts/migrate.php
```

Run `php scripts/seed.php` only on non-production databases if you need demo data.

## 5. Permissions

Ensure the web server user can write to:

- `backend/storage/` (and subfolders such as logs and uploads if used)

Do **not** grant write permission to `backend/` or `database/` unless your deployment specifically requires it.

## 6. Cron

Add a cron entry so queued jobs run (adjust path and PHP binary):

```cron
* * * * * /usr/bin/php /var/www/creatorzhive/scripts/cron.php >> /var/www/creatorzhive/backend/storage/logs/cron.log 2>&1
```

## 7. HTTPS and cookies

Use HTTPS in production. You can set `SESSION_SECURE=true` in `.env` when your site is served only over TLS.

## 8. Smoke test

- Open `/index.php?route=login` (or your configured base URL).
- Log in, create a draft post, schedule one post, and confirm a row appears in `job_queue` (or wait for cron).
- Confirm API responses return JSON with `Content-Type: application/json` for JSON routes.
