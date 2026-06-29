# CreatorzHive InfinityFree Deployment Guide

**Target**: https://creatorzhive.infinityfree.io/  
**Platform**: InfinityFree Free Hosting  
**Date**: 2026-06-11

---

## Quick Summary

This guide covers deploying CreatorzHive to InfinityFree shared hosting. The application is fully compatible with shared hosting — it requires only:
- PHP 7.4+
- MySQL database
- Apache web server (pre-configured)
- No SSH, no custom cron, no special permissions needed

**Total setup time**: ~30 minutes

---

## Pre-Deployment Checklist

Before uploading, prepare these on your local machine:

### 1. Install PHP Dependencies

```bash
cd /path/to/creatorzhive
composer install --no-dev --optimize-autoloader
```

This creates the `vendor/` folder (~3MB) and registers autoloaders.

### 2. Verify Database Schema

```bash
php scripts/migrate.php          # Test locally first
php scripts/seed.php --fresh    # Optional: load demo data
```

This ensures your local schema is up-to-date.

### 3. Generate Secrets

```bash
# Generate APP_SECRET (encryption key for OAuth tokens, admin credentials)
php -r 'echo "APP_SECRET=" . bin2hex(random_bytes(32)) . "\n";'

# Generate WEBHOOK_SECRET (for background job processing)
php -r 'echo "WEBHOOK_SECRET=" . bin2hex(random_bytes(32)) . "\n";'
```

Save these values — you'll need them on InfinityFree.

---

## Step 1: Create InfinityFree Account & Database

1. Go to [InfinityFree.net](https://infinityfree.net) and create account
2. In control panel, go to **MySQL Manager**
3. Click **New Database** and create a database (note the name, typically `username_dbname`)
4. Note the MySQL credentials displayed

---

## Step 2: Upload Project via FTP

### Get FTP Credentials

From InfinityFree control panel:
- Go to **FTP Manager**
- Click **New Account** and create FTP access
- Note: FTP Host, Username, Password
 
### Upload Files

Use an FTP client (FileZilla, Cyberduck, etc.):

1. Connect with FTP credentials
2. Navigate to the `public_html` directory
3. Upload entire CreatorzHive folder including `vendor/`:
   ```
   public_html/
   ├── creatorzhive/              (entire project)
   │   ├── public/
   │   ├── src/
   │   ├── backend/
   │   ├── frontend/
   │   ├── vendor/                ← MUST include this
   │   ├── database/
   │   ├── .env                   ← Create this next
   │   └── ...
   ```

**Important**: Upload is slow (~500MB for vendor/). This can take 10-30 minutes. Do NOT interrupt.

### Create .env File on Server

After upload completes:

1. In FTP, navigate to `public_html/creatorzhive/`
2. Open `.env.example` (read-only copy)
3. Create NEW file: `.env` with these contents (replace with YOUR values):

```env
APP_NAME=CreatorzHive
APP_URL=https://creatorzhive.infinityfree.io
APP_ENV=production
APP_DEBUG=false

# Database credentials from InfinityFree MySQL Manager
DB_HOST=localhost          # usually "localhost"
DB_PORT=3306
DB_DATABASE=username_dbname  # from InfinityFree MySQL
DB_USERNAME=username_dbuser  # from InfinityFree MySQL
DB_PASSWORD=your_db_password # from InfinityFree MySQL

# Email (Gmail recommended - use an App Password, not your real password)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_16_char_app_password    # NOT your Gmail password!
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME=CreatorzHive
 
# Security (from Step 1 pre-deployment)
APP_SECRET=<paste_generated_secret>
SESSION_SECURE=true
WEBHOOK_SECRET=<paste_generated_webhook_secret>

# Optional: Social integrations (fill in later if needed)
SOCIAL_API_MOCK_FALLBACK=true
INSTAGRAM_APP_ID=
INSTAGRAM_APP_SECRET=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

**Save and upload the .env file.**

---

## Step 3: Run Setup Wizard

1. Open browser: `https://creatorzhive.infinityfree.io/setup.php`
2. Follow the form:
   - ✓ Run database migrations (required)
   - ☐ Seed demo data (optional)
   - Enter admin email, name, password
3. Click "Complete Setup"
4. **After setup completes**, delete `public/setup.php` via FTP for security

---

## Step 4: Set Up Background Job Processing

CreatorzHive needs background jobs for:
- Publishing posts to social platforms
- Fetching analytics
- Sending notifications
- Cleaning up orphaned files

### Option A: UptimeRobot (Free, Recommended)

1. Go to [UptimeRobot.com](https://uptimerobot.com)
2. Create account (free tier included)
3. Click "Add New Monitor"
4. Choose: **Cron Job**
5. Fill in:
   - **Cron Expression**: `*/1 * * * *` (every minute)
   - **URL**: `https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=<YOUR_WEBHOOK_SECRET>`
   - Replace `<YOUR_WEBHOOK_SECRET>` with the value from your .env
6. Click "Create"
7. UptimeRobot will call your webhook every minute

### Option B: EasyCron.com (Alternative)

Similar setup at [EasyCron.com](https://www.easycron.com) with same webhook URL.

### Option C: InfinityFree Built-in Cron

InfinityFree may offer cron functionality in the control panel. If available, point it to the webhook URL.

**Test**: After 1 minute, check if jobs process by going to Settings → and confirming integrations work.

---

## Step 5: Verify Installation

### Basic Checks

1. **Homepage**: https://creatorzhive.infinityfree.io/ loads without errors ✓
2. **Login**: Email/password login works ✓
3. **Dashboard**: Shows with some data ✓
4. **Google OAuth** (if configured): Sign-in redirects work ✓

### Test Content Creation

1. Log in as admin
2. Go to Planner
3. Create a post (title + content)
4. Add a media file (upload an image)
5. Publish to Drafts
6. Verify post appears in Planner

### Test Social Integration

1. Go to Settings → Integrations
2. Click "Connect Meta/Instagram" or "Connect Google"
3. Follow OAuth flow
4. Verify account connects

### Test Background Jobs

1. Go to Settings → Integrations
2. Status should show green checkmarks (jobs are running)
3. If red, check webhook setup

---

## Optional: Configure Social Integrations

### Meta/Instagram OAuth

Requires Meta Developer app (free tier):

1. Go to [Meta Developers](https://developers.facebook.com/)
2. Create app → Business type
3. Add product: "Facebook Login"
4. In Settings → Basic: copy App ID and App Secret
5. Add to CreatorzHive:
   - Admin → Integrations
   - Enter Meta App ID + Secret
   - Save

### Google OAuth

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create project
3. Enable: YouTube Data API, Google+ API
4. Create OAuth 2.0 credentials (Web application)
5. Add redirect URI: `https://creatorzhive.infinityfree.io/?route=google-callback`
6. Copy Client ID + Client Secret
7. Add to CreatorzHive Admin → Integrations

---

## Troubleshooting

### Application shows: "APP_SECRET is not configured"

- Edit `.env` file
- Add: `APP_SECRET=<value_from_step_2>`
- Save and reload browser

### Database connection error

- Verify .env has correct credentials (copy/paste from InfinityFree)
- Check MySQL Manager shows database created
- Try again (InfinityFree MySQL sometimes takes 10 minutes to activate)

### Email not sending

- Use Gmail with an App Password (not your real password)
- Generate at: https://myaccount.google.com/apppasswords
- Add to MAIL_PASSWORD in .env

### Background jobs not running

- Check UptimeRobot status (verify webhook is being called)
- If not running, regenerate WEBHOOK_SECRET and update UptimeRobot
- Check admin panel → Integrations (should show green status)

### File upload fails

- Verify `public/uploads/.htaccess` exists
- InfinityFree should auto-create `public/uploads/` as writable

### Login redirects to Google instead of showing login form

- If Google OAuth not configured, disable it:
  - Edit `.env`: `GOOGLE_CLIENT_ID=` (empty)
  - Reload

---

## Maintenance

### Daily

- Check admin panel → Integration status (should be green)
- If red, check webhook (UptimeRobot)

### Weekly

- Verify email notifications arrive
- Monitor dashboard → check job queue

### Monthly

- Generate new APP_SECRET (rotate encryption key)
- Update social platform tokens if expired

---

## Support & Troubleshooting

For issues:
1. Check `.env` file (most issues are configuration)
2. Review error logs in browser console (F12 → Console tab)
3. Check InfinityFree MySQL Manager (database exists?)
4. Verify FTP upload completed (vendor/ folder exists?)

---

## Next Steps After Deployment

1. **Customize Settings**
   - Admin → Settings
   - Update app name, contact email, etc.

2. **Add Content**
   - Create first posts
   - Connect social accounts
   - Schedule posts

3. **Monitor Usage**
   - Check analytics dashboard
   - Monitor job queue
   - Watch for any errors

4. **Invite Users**
   - Users can register at https://creatorzhive.infinityfree.io
   - Or admin can create users manually

---

**Deployment successful!** 🎉
