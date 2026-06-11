# Local development setup

## Requirements

- PHP 8.1 or newer (7.4 may run but is not targeted)
- MySQL 8.0
- Apache with `mod_rewrite` **or** Nginx with equivalent URL rewriting
- Composer 2.x

## Steps

1. **Clone** this repository to your machine.

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

   Frontend fonts and Chart.js are **self-hosted** (no Google Fonts or jsDelivr at runtime). If `frontend/fonts/` or `frontend/assets/chart.js/` is missing:

   ```bash
   bash scripts/download-frontend-vendor.sh
   ```

3. **Environment file**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` and set at least: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and mail settings if you use email features.

4. **Create the database** in MySQL (empty database is enough).

5. **Run migrations** to create tables and apply SQL migrations:

   ```bash
   php scripts/migrate.php
   ```

6. **Optional: full reset and seed** (drops and recreates the database from `database/schema.sql`, then runs seeds — use only on a dev database):

   ```bash
   php scripts/seed.php --fresh
   ```

   Or, if the schema is already applied:

   ```bash
   php scripts/seed.php
   ```

7. **PHPUnit**  
   By default, tests use the same database as `DB_DATABASE` in `.env` (suitable for local dev). For isolation, create a separate database, set `DB_DATABASE_TEST` in `.env` to that name, migrate it, then run:

   ```bash
   ./vendor/bin/phpunit
   ```

8. **Web server**  
   **Preferred:** point the virtual host **document root** to the `public/` directory (not the project root). Example Apache `DocumentRoot`:

   ```
   /path/to/creatorzhive/public
   ```

   If your URL is `http://localhost/creatorzhive/` and Apache’s document root is the **project folder** (parent of `public/`), you will see a directory listing unless a default index exists. This repo includes a root **`index.php`** that loads `public/index.php`, plus **`DirectoryIndex index.php`** in `.htaccess`, so `/creatorzhive/` should open the app. Ensure **`AllowOverride All`** (or at least `FileInfo`) is enabled for that directory so `.htaccess` is applied; otherwise only the root `index.php` helps and rewrites may not run.

9. **Cron** (scheduled jobs / publish queue):

   ```cron
   * * * * * php /path/to/creatorzhive/scripts/cron.php
   ```

10. **Marketing landing page**  
    The static landing page lives at `frontend/index.html`. With document root set to `public/`, copy or symlink it if you want a dedicated URL, e.g. `public/home.html`, or open the file directly while designing. Links on that page assume the app is served from the site root (`/index.php?route=…`). If you use a subdirectory, adjust those URLs or set `APP_BASE_PATH` / `APP_URL` consistently with your server config.

11. **Log in** using seeded demo accounts (after `scripts/seed.php`):

    - Admin — `admin@creatorzhive.com` / `Admin@1234`
    - Creator — `david@creatorzhive.com` / `Creator@1234`
    - Brand — `brand@creatorzhive.com` / `Brand@1234`

## Sign in with Google

1. In [Google Cloud Console](https://console.cloud.google.com/), create an **OAuth 2.0 Client ID** of type **Web application** (not only API keys used for YouTube).
2. Add an **Authorized redirect URI** that matches your app:

   ```
   {APP_URL}{APP_BASE_PATH}/?route=google-callback
   ```

   Example for a subdirectory install: `http://localhost/creatorzhive/?route=google-callback`

3. Set in `.env`:

   - `GOOGLE_CLIENT_ID`
   - `GOOGLE_CLIENT_SECRET`

   Optional: `GOOGLE_AUTH_REDIRECT_URI` if the auto-built callback URL does not match what you registered in Google.

4. Run migrations so `users.google_id` exists:

   ```bash
   php scripts/migrate.php
   ```

When credentials are set, login and register show **Continue with Google**. Existing accounts with the same email are linked on first Google sign-in; new users are created when registration is enabled.

## Social API integration

To enable live social publishing and analytics (instead of mock fallback), set the social keys in `.env`:

- `SOCIAL_API_MOCK_FALLBACK=false`
- `INSTAGRAM_ACCESS_TOKEN`, `INSTAGRAM_BUSINESS_ID`
- `FACEBOOK_ACCESS_TOKEN`, `FACEBOOK_PAGE_ID`
- `TIKTOK_ACCESS_TOKEN`
- `YOUTUBE_ACCESS_TOKEN`, `YOUTUBE_CHANNEL_ID`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` (YouTube token refresh; also required for **Continue with Google** on login/register)
- `TWITTER_BEARER_TOKEN`

Then connect accounts in Settings -> Integrations. You can pass real tokens through the `connect_platform` API body:

- `platform` (required)
- `username` (required)
- `platform_user_id` (optional but recommended)
- `access_token` (optional; if omitted, system uses env token or mock token)
- `refresh_token` (optional)
- `token_expires_at` (optional datetime string)

When cron runs (`scripts/cron.php`), publish + analytics jobs will use live API requests per platform.
