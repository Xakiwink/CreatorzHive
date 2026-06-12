# Auth Pages — Explained

**Files:**
- `frontend/pages/auth/login.php`
- `frontend/pages/auth/register.php`
- `frontend/pages/auth/forgot-password.php`
- `frontend/pages/auth/reset-password.php`
- `frontend/pages/auth/verify-email.php`

---

## Purpose

Static PHP page templates for the authentication flow. These pages are **not** rendered by `ViewRenderer` — they are loaded directly by the web server (or by `http_view()` which finds them via path). They bootstrap via `backend/bootstrap-web-view.php` (session + CSRF only; no DB, no OOP stack).

---

## Bootstrap Pattern

```php
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
```

This gives them:
- Session access (`session_get()`, `session_get_flash()`)
- CSRF token (`$_SESSION['_csrf_token']`)
- Helper functions (`asset_url()`, `google_auth_start_url()`)

---

## Page-by-Page

### `login.php`
- Reads `auth_error` and `auth_success` flash messages from session
- Builds Google OAuth URL via `google_auth_start_url('creator')`
- Renders a two-column layout (brand left, form right)
- Form posts to `?route=login`
- "Remember me" checkbox included (handled by `auth.js`)

### `register.php`
- Same two-column layout
- Role selection (creator / brand partner)
- Builds Google OAuth URL for each role (`?role=creator`, `?role=brand_partner`)
- Form posts to `?route=register`
- Live username availability checked by `auth.js` (400ms debounce)

### `forgot-password.php`
- Single email input
- Posts to `?route=forgot_password`
- `auth.js` enforces 60-second cooldown between submissions (persisted in `localStorage`)

### `reset-password.php`
- Receives `?token=<reset_token>` via query string
- Token embedded in hidden form field
- Posts to `?route=reset_password`

### `verify-email.php`
- Receives `?token=<verify_token>` via query string
- `auth.js` auto-submits verification on page load via `window.__VERIFY_TOKEN__`
- Token injected into page as a `window.__VERIFY_TOKEN__` JavaScript variable

---

## CSS

All auth pages use `auth.css` + `dark-mode.css` only (no `main.css` or component styles).

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/js/auth.js` | Client-side logic for all auth forms |
| `backend/bootstrap-web-view.php` | Minimal bootstrap loaded by these pages |
| `src/Controllers/AuthController.php` | Handles the form POST routes |
| `frontend/pages/auth/README.md` | Auth flow overview |
