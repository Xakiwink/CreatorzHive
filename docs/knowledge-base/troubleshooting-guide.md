# CreatorzHive — Troubleshooting Guide

> Solutions to common problems encountered during development and production.

---

## 1. Installation Problems

### Problem: `composer install` fails
```
Your requirements could not be resolved...
```
**Cause:** PHP version mismatch.
**Fix:**
```bash
php --version  # Must be >= 7.4
# If wrong PHP version:
update-alternatives --config php  # Ubuntu
```

### Problem: Database connection failed on startup
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```
**Fix:**
1. Check `DB_USERNAME` and `DB_PASSWORD` in `.env`
2. Verify MySQL is running: `systemctl status mysql`
3. Test connection: `mysql -u root -p -h 127.0.0.1`
4. Note: Use `127.0.0.1` not `localhost` to force TCP (not socket)

### Problem: Migration fails — table already exists
```
Table 'users' already exists
```
**Fix:** The schema uses `CREATE TABLE IF NOT EXISTS` — this shouldn't happen. If it does:
```bash
mysql -u root -p -e "DROP DATABASE creatorz_hive; CREATE DATABASE creatorz_hive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php scripts/migrate.php
```

### Problem: `vendor/autoload.php not found`
**Fix:** Run `composer install` from the project root.

---

## 2. Authentication Problems

### Problem: Login succeeds but redirects back to login page
**Cause:** Session not persisting (usually a PHP session directory or cookie issue).
**Fix:**
1. Check PHP session path is writable: `php -r "echo session_save_path();"`
2. On Apache: ensure `AllowOverride All` is set
3. On subdirectory installs: verify `APP_URL` includes the path (e.g., `http://localhost/creatorzhive`)

### Problem: "Session validation failed" after login
**Cause:** Session fingerprint mismatch. Usually happens when IP or User-Agent changes between requests.
**Fix (development):** This is expected on VPN changes. Clear browser cookies and log in again.
**Fix (production):** Ensure load balancers use sticky sessions or implement DB-backed session handler.

### Problem: Google Sign-In button not showing
**Cause:** `GOOGLE_CLIENT_ID` or `GOOGLE_CLIENT_SECRET` not set in `.env`.
**Fix:** Set both variables and clear browser cache.

### Problem: Google OAuth "redirect_uri_mismatch"
**Cause:** The callback URL doesn't match what's registered in Google Cloud Console.
**Fix:**
1. Go to Google Cloud Console → Your Project → Credentials → OAuth 2.0 Client
2. Add to Authorized Redirect URIs: `{APP_URL}/?route=google-callback`
3. Wait 5 minutes for changes to propagate

### Problem: "Too many attempts" on login
**Cause:** Rate limiter triggered. Uses token bucket in `rate_limits` table.
**Fix (dev):** Delete the rate limit record:
```sql
DELETE FROM rate_limits WHERE `key` LIKE 'ip:%:login';
```

---

## 3. Email Problems

### Problem: Verification/reset emails not arriving
**Fix:**
1. Check `backend/storage/logs/mail-{date}.log` for errors
2. Verify SMTP settings in `.env`
3. For Gmail: use App Password, not account password
4. For development: use Mailtrap (mailtrap.io)

### Problem: `Connection: stream_socket_client(): SSL operation failed`
**Cause:** SMTP SSL/TLS configuration mismatch.
**Fix:**
- Port 587: use STARTTLS (default for most SMTP)
- Port 465: use SSL/SMTPS
- Check PHPMailer docs for your provider

---

## 4. Publishing Problems

### Problem: Post stays "scheduled" and never publishes
**Cause:** Webhook not being called, or job_queue not being processed.
**Fix:**
1. Verify UptimeRobot is calling the webhook URL with the correct `WEBHOOK_SECRET`
2. Check job_queue: `SELECT * FROM job_queue WHERE status='pending' ORDER BY created_at DESC LIMIT 10;`
3. If status is 'failed': `SELECT error_message FROM job_queue WHERE status='failed';`
4. Check logs in `backend/storage/logs/`

### Problem: "Instagram token/business id missing"
**Cause:** No Instagram account connected, or token expired.
**Fix:**
1. Go to Settings → Integrations
2. Disconnect and reconnect Instagram
3. Ensure `INSTAGRAM_APP_ID` and `INSTAGRAM_APP_SECRET` are configured in Admin → Integrations

### Problem: Posts publish with `mock_XXXXXX` platform IDs
**Cause:** `SOCIAL_API_MOCK_FALLBACK=true` is set.
**Fix:** For real publishing, set `SOCIAL_API_MOCK_FALLBACK=false` and configure actual API credentials.

### Problem: `cURL extension is not enabled`
**Cause:** PHP cURL extension not installed.
**Fix:**
```bash
apt-get install php-curl
systemctl restart apache2  # or php-fpm
```

---

## 5. Media Upload Problems

### Problem: Upload fails with "File too large"
**Cause:** File exceeds `UPLOAD_MAX_SIZE` (10MB) OR PHP's own limits.
**Fix:**
1. App limit: Adjust `UPLOAD_MAX_SIZE` in `backend/config/app.php`
2. PHP limit: Edit `php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 25M
   ```
3. Restart PHP/Apache after changes

### Problem: Uploaded images return 404
**Cause:** `APP_URL` doesn't include subdirectory path, or `APP_UPLOADS_PUBLIC_PREFIX` misconfigured.
**Fix:**
1. Verify `APP_URL=http://localhost/creatorzhive` (not just `http://localhost`)
2. Try setting `APP_UPLOADS_PUBLIC_PREFIX=1` if document root is project root

### Problem: Thumbnail not generated
**Cause:** PHP GD or Imagick extension not installed.
**Fix:**
```bash
apt-get install php-gd
systemctl restart apache2
```

---

## 6. Frontend Problems

### Problem: Blank page with no error
**Cause:** PHP fatal error with `APP_DEBUG=false`.
**Fix:**
1. Temporarily set `APP_DEBUG=true`
2. Check `backend/storage/logs/error-{date}.log`
3. Check Apache error log: `tail -f /var/log/apache2/error.log`

### Problem: CSS/JS not loading (404)
**Cause:** `APP_URL` or `base_url_path()` is returning wrong path.
**Fix:**
1. Check `APP_URL` in `.env` matches your actual URL exactly
2. Clear browser cache
3. Check browser Network tab for actual 404 URL vs expected

### Problem: CSRF token mismatch on form submit
**Fix:**
1. Clear browser cookies (old session with old token)
2. Check that `window.__CSRF__` is set in the page source
3. Ensure POST body includes `_csrf_token` field

### Problem: `window.__USER__` is null in JavaScript
**Cause:** Template didn't include `frontend/pages/partials/app_script_globals.php`.
**Fix:** Ensure all authenticated pages include the globals partial.

---

## 7. Analytics Problems

### Problem: Analytics shows zeros
**Cause:** No analytics sync has run yet, or no social accounts connected.
**Fix:**
1. Connect a social account (Settings → Integrations)
2. Manually trigger analytics: `php scripts/cron.php --queue=analytics`
3. Or seed demo data: `POST ?route=seed_analytics`

### Problem: Chart.js graphs not rendering
**Cause:** `frontend/assets/chart.js/chart.umd.min.js` missing.
**Fix:**
```bash
bash scripts/download-frontend-vendor.sh
```
Or manually download Chart.js and place it at the expected path.

---

## 8. Admin Panel Problems

### Problem: "Forbidden — Admin access only"
**Cause:** Logged-in user's `role` is not `admin`.
**Fix:**
```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```
Logout and log back in.

### Problem: Platform credentials not saving
**Cause:** `APP_SECRET` not set; `TokenCrypto` falls back to insecure key but otherwise works. Or DB connection issue.
**Fix:** Set `APP_SECRET` in `.env` and restart.

---

## 9. Development Utilities

### Reset all data (dev only)
```bash
mysql -u root -p -e "DROP DATABASE creatorz_hive; CREATE DATABASE creatorz_hive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php scripts/migrate.php
```

### View job queue
```sql
SELECT id, queue, job_class, status, attempts, error_message, created_at
FROM job_queue
ORDER BY created_at DESC
LIMIT 20;
```

### Trigger the job webhook manually
```bash
curl "https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=YOUR_WEBHOOK_SECRET"
```

### Check error logs
```bash
ls backend/storage/logs/
tail -100 backend/storage/logs/error-$(date +%Y-%m-%d).log
tail -100 backend/storage/logs/mail-$(date +%Y-%m-%d).log
```

### Test database connectivity
```
GET /?route=ping          → {"pong": true}
GET /?route=db-test       → DB connection status (admin only)
```

### Check which user is logged in
```
GET /?route=api_me        → Returns current user + CSRF token
```

---

## 10. Common Error Messages

| Error Message | Cause | Fix |
|--------------|-------|-----|
| `Service not registered: …` | DI container not bootstrapped | Check bootstrap-oop.php loads before use |
| `Factory for … did not return an object` | AppServiceProvider factory error | Check constructor signature matches registration |
| `Cannot open lock file` | `backend/storage/` not writable | `chmod 775 backend/storage/` |
| `CSRF token mismatch` | Old/missing CSRF token | Clear cookies, refresh page |
| `Unauthorized (401)` | Session expired or invalid | Log in again |
| `Account inactive (403)` | User `is_active=0` | Admin re-activates user |
| `Route not found` | Unknown `?route=` value | Check web.php and api.php route registrations |
