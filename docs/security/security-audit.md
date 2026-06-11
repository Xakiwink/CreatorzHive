# CreatorzHive — Security Audit

> **Version:** 1.0 | **Date:** 2026-06-10 | **Auditor:** Architecture Review

---

## Executive Summary

CreatorzHive implements several solid security controls: CSRF protection, session fingerprinting, AES-256-CBC token encryption, bcrypt password hashing, and PDO prepared statements throughout. Several areas require attention before production deployment, particularly around session cookie security, APP_SECRET enforcement, and environment file exposure.

---

## 1. Authentication

### 1.1 Password Storage
**Status:** GOOD

Passwords are hashed using `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` in `AuthService::hashPassword()`. Cost factor 12 is appropriate.

`dummyPasswordCheck()` performs a dummy hash comparison when the user is not found, preventing timing-based user enumeration attacks.

### 1.2 Session Management
**Status:** MEDIUM RISK

**What's good:**
- `session_regenerate_id(true)` called on login (prevents session fixation)
- `httponly: true` on session cookies (blocks JavaScript access)
- `samesite: Strict` on session cookies (blocks CSRF via cross-site requests)
- Session fingerprinting: SHA-256(truncated_IP + lowercase_UA) stored per session

**Risks:**
1. **`SESSION_SECURE=false` default** — In `.env.example` the default is `false`. If deployed over HTTP this exposes the session cookie to network sniffing. **Fix:** Set `SESSION_SECURE=true` in production and enforce HTTPS.
2. **PHP native file sessions** — Default file-based sessions on shared hosting risk session file access by other users. The `sessions` DB table exists but is not wired as the session handler. **Fix:** Implement custom session handler using the `sessions` table for production.
3. **Session lifetime** — Default `SESSION_LIFETIME=120` minutes. Long-lived sessions increase attack window.

### 1.3 Google OAuth
**Status:** GOOD

State parameter is used to prevent CSRF during OAuth flow. Code exchange happens server-side.

### 1.4 Meta OAuth
**Status:** MEDIUM RISK

State parameter used. However, the state is not validated cryptographically in the current implementation — it should be compared to a session-stored value. **Fix:** Store `$_SESSION['oauth_state']` before redirect, validate on callback.

### 1.5 Login Rate Limiting
**Status:** GOOD

Token-bucket rate limiting on login endpoint using `rate_limits` table via `AuthRateLimitService`. No Redis required. IP-based key: `ip:{ip}:login`.

---

## 2. Authorization

### 2.1 Route Middleware
**Status:** GOOD

All routes declare middleware: `['auth']`, `['auth', 'non_admin']`, `['auth', 'role:admin']`. The middleware system enforces these before controllers execute.

- `AuthMiddleware::handle()` — checks session user, re-validates against DB, checks `is_active`
- `RoleMiddleware` — enforces role requirements
- `non_admin` — blocks admin-role users from creator routes

### 2.2 Data Ownership Enforcement
**Status:** MEDIUM RISK

Repositories accept `user_id` as a parameter, but controllers must pass `session_get_user()['id']`. This is done correctly in the current controllers.

**Risk:** Insecure Direct Object Reference (IDOR) — if a controller fetches a post by `?id=5` without confirming `user_id` matches the session user, another user could read/modify it.

**Recommendation:** Audit all repository `findById()` calls to ensure they include `AND user_id = :user_id` in the WHERE clause. Add a `findByIdAndUser(int $id, int $userId)` method to all repositories as the default.

### 2.3 Admin-Only Endpoints
**Status:** GOOD

Admin routes use `['auth', 'role:admin']` middleware. `role_middleware_require('admin')` checks `session_get_user()['role']`.

---

## 3. CSRF Protection

### 3.1 Token Generation
**Status:** GOOD

`csrf_generate_token()` uses `generateToken(64)` which calls `bin2hex(random_bytes(32))` — cryptographically secure.

### 3.2 Token Validation
**Status:** GOOD

`csrf_validate_post()` uses `hash_equals()` (timing-safe comparison). Token is stored in `$_SESSION['_csrf_token']`.

### 3.3 Frontend Integration
**Status:** GOOD

`window.__CSRF__` is set from PHP in `partials/app_script_globals.php`. JavaScript includes it in all POST requests via `fetch()`.

---

## 4. SQL Injection

### 4.1 Prepared Statements
**Status:** GOOD

All database access goes through `Connection::query()` with named parameters. `PDO::ATTR_EMULATE_PREPARES = false` ensures real prepared statements, not client-side emulation.

`Connection::insert()`, `update()`, and `delete()` use column backtick-quoting via `quoteColumn()`.

**No raw string interpolation detected in queries.**

### 4.2 Dynamic ORDER BY / Sort
**Status:** GOOD

`Connection::sortDirection()` whitelists `ASC`/`DESC`. Column names for sorting should also be whitelisted in repositories.

---

## 5. XSS Protection

### 5.1 HTTP Headers
**Status:** GOOD

`backend/index.php` sets:
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

### 5.2 Output Escaping
**Status:** MEDIUM RISK

PHP template files in `frontend/pages/` must use `htmlspecialchars()` when echoing user-provided data. Audit required.

JavaScript receives JSON from API endpoints — this is safe from XSS by default since JSON is not HTML. However, if any JS dynamically inserts API content into the DOM via `innerHTML`, this is an XSS risk.

**Recommendation:**
1. Grep for `innerHTML` in JS files and replace with `textContent` where possible.
2. Add `Content-Security-Policy` header to restrict script sources.

### 5.3 CSP Header
**Status:** MISSING

No `Content-Security-Policy` header is currently set.

**Recommendation:** Add CSP header, at minimum:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;
```

---

## 6. API Key & Secret Storage

### 6.1 Platform OAuth Tokens
**Status:** GOOD

`social_accounts.access_token` and `refresh_token` are encrypted with AES-256-CBC using `TokenCrypto`. The encryption key is derived from `APP_SECRET` via SHA-256. Stored tokens have `czenc1:` prefix to identify encrypted values.

### 6.2 APP_SECRET
**Status:** HIGH RISK

The `APP_SECRET` environment variable is the root encryption key for all stored OAuth tokens. If empty, `TokenCrypto` falls back to a hardcoded insecure key:
```php
$raw = 'creatorzhive-insecure-dev-key-set-APP_SECRET-in-env';
```

**Risk:** If deployed to production without setting `APP_SECRET`, all stored tokens are encrypted with a publicly known key.

**Fix:** Make `APP_SECRET` required in production. Add a startup check that exits with a fatal error if `APP_ENV=production` and `APP_SECRET` is empty.

### 6.3 Environment File
**Status:** MEDIUM RISK

The `.env` file is in the project root. If the web server's document root is the project root (not `public/`), `.env` would be accessible via HTTP.

**Fix:** Always set document root to `public/`. Apache `.htaccess` in `public/` already protects this if configured correctly.

### 6.4 Admin Credential Storage
**Status:** GOOD

Admin-managed API credentials (Meta App ID/Secret, etc.) are stored encrypted in the DB via `PlatformApiSecretsService`. Uses `TokenCrypto::encryptDb()`.

---

## 7. File Upload Security

**Status:** MEDIUM RISK

`MediaUploadHelper.php` handles uploads.

**What's good:**
- MIME type whitelist: `ALLOWED_IMAGE_TYPES` and `ALLOWED_VIDEO_TYPES` checked
- File size limit: `UPLOAD_MAX_SIZE = 10MB`
- Files stored in `public/uploads/` with MD5-hashed filenames

**Risks:**
1. **MIME type validation via `$_FILES['type']`** — This is the client-supplied MIME type, not verified by the server. Should use `finfo_file()` or `mime_content_type()` to check actual file magic bytes.
2. **No script execution prevention** — Uploaded files could contain PHP code. Apache should be configured to not execute files in `uploads/`. Add `.htaccess` to `public/uploads/` with `php_flag engine off`.
3. **Filename collision** — MD5 of filename is not guaranteed unique. Should use `uniqid()` + `random_bytes()` for storage names.

---

## 8. Input Validation

**Status:** GOOD

`backend/core/validator.php` provides `validate_required()`, `validate_email()`, `validate_length()`, etc. Used consistently across controllers.

`sanitize()` helper strips tags and trims whitespace from all user inputs.

---

## 9. Error Handling & Information Disclosure

**Status:** MEDIUM RISK

`APP_DEBUG=true` is the default in `.env.example`.

**Risk:** In production with `APP_DEBUG=true`, full exception stack traces are exposed to the browser.

**Fix:** Always set `APP_DEBUG=false` in production. `error_handler.php` should check this flag before rendering detailed errors.

Error logs are stored in `backend/storage/logs/error-{date}.log` — not accessible via web if document root is `public/`.

---

## 10. Security Headers Checklist

| Header | Status | Value Set |
|--------|--------|-----------|
| X-Content-Type-Options | ✅ Set | nosniff |
| X-Frame-Options | ✅ Set | SAMEORIGIN |
| X-XSS-Protection | ✅ Set | 1; mode=block |
| Referrer-Policy | ✅ Set | strict-origin-when-cross-origin |
| Content-Security-Policy | ❌ Missing | Not set |
| Strict-Transport-Security | ❌ Missing | Not set |
| Permissions-Policy | ❌ Missing | Not set |

---

## 11. Dependency Security

### Composer Dependencies
- `phpmailer/phpmailer ^6.8` — Actively maintained. No known critical CVEs.
- `phpunit/phpunit ^9.6` — Dev-only. No production risk.

### Frontend Dependencies
- `chart.js` — Self-hosted. Check for updates against Chart.js GitHub.
- No CDN dependencies. Zero third-party external script calls. ✅

---

## Summary: Risk Registry

| Risk | Severity | Component | Fix |
|------|----------|-----------|-----|
| `APP_SECRET` empty in production | **CRITICAL** | TokenCrypto | Enforce non-empty in production |
| SESSION_SECURE=false default | **HIGH** | Session | Set true + enforce HTTPS |
| Missing Content-Security-Policy | **HIGH** | All pages | Add CSP header |
| MIME type client-supplied only | **HIGH** | MediaUploadHelper | Use `finfo_file()` |
| Meta OAuth state not validated | **MEDIUM** | OauthController | Store + compare state in session |
| IDOR risk in repo queries | **MEDIUM** | Repositories | Always filter by user_id |
| PHP native file sessions | **MEDIUM** | Session | Use DB session handler |
| APP_DEBUG=true default | **MEDIUM** | Config | Set false in production |
| No uploads/ script execution lock | **MEDIUM** | Apache config | Add .htaccess to uploads/ |
| Missing HSTS header | **LOW** | HTTP headers | Add HSTS in production |
| No CSP on admin pages | **LOW** | Admin | Add admin-specific CSP |
| Rate_limits stale row cleanup | **LOW** | DB maintenance | Scheduled cleanup job |
