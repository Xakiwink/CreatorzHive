# CreatorzHive — InfinityFree Deployment Guide

**Domain**: https://creatorzhive.infinityfree.io  
**Platform**: InfinityFree Free Hosting  
**Document root**: `/htdocs/` (project files go directly here, not in a subdirectory)

---

## Quick Summary

CreatorzHive requires only:
- PHP 7.4+
- MySQL database
- Apache (pre-configured on InfinityFree)

No SSH, no cron daemon, no special server permissions needed.

**Total setup time**: ~30 minutes

---

## Pre-Deployment Checklist

### 1. Install PHP Dependencies (locally)

```bash
composer install --no-dev --optimize-autoloader
```

This generates the `vendor/` folder. Upload it with the rest of the project.

### 2. Generate Secrets (locally)

```bash
php -r 'echo "APP_SECRET=" . bin2hex(random_bytes(32)) . "\n";'
php -r 'echo "WEBHOOK_SECRET=" . bin2hex(random_bytes(32)) . "\n";'
```

Save both values — you will paste them into `.env` on the server.

---

## Step 1: Create Database in InfinityFree

1. Log in to InfinityFree control panel
2. Go to **MySQL Manager** → **New Database**
3. Note the database name, username, and password (auto-generated)
4. Note the **DB Host** — it will be something like `sql211.infinityfree.com`

---

## Step 2: Upload Files via FTP

### Get FTP Credentials

In InfinityFree control panel → **FTP Manager** → **New Account**. Note the host, username, and password.

### Upload Structure

Connect with FileZilla (or similar) and upload the entire project **directly into `/htdocs/`**:

```
/htdocs/
├── backend/
├── database/
├── frontend/
├── public/
├── scripts/
├── src/
├── tests/
├── vendor/          ← must include this
├── .htaccess
├── index.php
├── setup.php
├── webhook/
└── ...
```

Do **not** create a `creatorzhive/` subfolder inside `/htdocs/`. Files go directly at the root.

**Note**: The `vendor/` folder is large (~50–200MB). The upload may take 10–30 minutes — do not interrupt it.

### Create the `.env` File

After uploading, create a file named `.env` in `/htdocs/` (copy `.env.example` and fill in your values):

```env
APP_NAME=CreatorzHive
APP_URL=https://creatorzhive.infinityfree.io
APP_ENV=production
APP_DEBUG=false

DB_HOST=sql211.infinityfree.com
DB_PORT=3306
DB_DATABASE=if0_42295215_your_db_name
DB_USERNAME=if0_42295215
DB_PASSWORD=your_db_password

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME=CreatorzHive

APP_SECRET=paste_generated_secret_here
SESSION_SECURE=true
WEBHOOK_SECRET=paste_generated_webhook_secret_here

SOCIAL_API_MOCK_FALLBACK=true
INSTAGRAM_APP_ID=
INSTAGRAM_APP_SECRET=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

---

## Step 3: Import the Database

1. In InfinityFree control panel → **MySQL Manager** → **phpMyAdmin**
2. Select your database in the left sidebar
3. Click the **Import** tab
4. Choose file: `database/schema.sql`
5. Click **Go**

The schema.sql contains all tables, indexes, triggers, and initial data. One import — no separate seed files.

**If you get a `CREATE VIEW` error**: InfinityFree does not grant VIEW privileges on free plans. The current schema.sql has no views — if you see this error, you are using an outdated file. Re-download `database/schema.sql` from the repo.

---

## Step 4: Run the Setup Wizard

1. Visit: `https://creatorzhive.infinityfree.io/setup.php`
2. Fill in: admin email, name, and password
3. Click **Complete Setup**
4. After setup completes, **delete `public/setup.php`** via FTP for security

---

## Step 5: Configure Background Jobs (UptimeRobot)

InfinityFree has no reliable cron. Use UptimeRobot (free) to call the webhook every minute:

1. Go to [uptimerobot.com](https://uptimerobot.com) → create free account
2. **Add New Monitor** → type: **HTTP(s)**
3. URL: `https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=YOUR_WEBHOOK_SECRET`
4. Monitoring interval: **5 minutes** (free tier minimum)

This triggers background jobs: scheduled post publishing, analytics sync, notification delivery.

---

## Step 6: Verify the Installation

1. Visit `https://creatorzhive.infinityfree.io/` — homepage loads without errors
2. Log in with the admin account you created in setup
3. Dashboard shows stats and navigation
4. Go to **Settings → Integrations** — integration status visible

---

## Optional: Configure Social Integrations

### Instagram Business Login

See [INSTAGRAM_BUSINESS_LOGIN.md](INSTAGRAM_BUSINESS_LOGIN.md) for the full setup guide.

Summary:
1. Create a Meta Developer app (Business type) at developers.facebook.com
2. Add **Instagram** product → API setup with Instagram login
3. Set callback URI: `https://creatorzhive.infinityfree.io/?route=instagram-callback`
4. Copy App ID and App Secret into Admin → Integrations → Instagram Business Login

### YouTube / Google OAuth

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create project → enable YouTube Data API v3
3. Create OAuth 2.0 credentials (Web application)
4. Add redirect URI: `https://creatorzhive.infinityfree.io/?route=google-callback`
5. Paste Client ID and Client Secret into Admin → Integrations → YouTube

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `APP_SECRET is not configured` | Add `APP_SECRET=...` to `.env` |
| Database connection error | Verify `DB_HOST=sql211.infinityfree.com` and credentials from InfinityFree |
| `CREATE VIEW command denied` | Use the current `database/schema.sql` — it contains no views |
| 404 on all pages | Check `.htaccess` uploaded correctly and mod_rewrite is on |
| Emails not sending | Use Gmail App Password (not account password) |
| Background jobs not running | Verify UptimeRobot is calling the webhook URL with the correct secret |
| File upload fails | Verify `public/uploads/.htaccess` is present |

---

## Maintenance

- **Check weekly**: Admin → Integrations (all statuses green)
- **Token refresh**: Instagram tokens expire after ~60 days — reconnect via Settings → Integrations
- **Logs**: Admin → System (or check `backend/storage/logs/` via FTP)
