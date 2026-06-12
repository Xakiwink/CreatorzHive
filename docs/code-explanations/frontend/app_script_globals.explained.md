# app_script_globals.php — Explained

**File:** `frontend/pages/partials/app_script_globals.php`

---

## Purpose

A PHP partial included at the bottom of every authenticated page template. Injects server-side data into JavaScript `window.*` globals so that page scripts (`app.js`, `dashboard.js`, etc.) can access user context, routing, and CSRF tokens without additional API calls.

---

## Globals Injected

| Global | Value | Source |
|--------|-------|--------|
| `window.__BASE_PATH__` | App base URL path | `base_url_path()` |
| `window.__CSRF__` | CSRF token | `session_get('_csrf_token')` |
| `window.__USER__` | Authenticated user object | `frontend_session_user_payload()` |

All values are passed through `json_encode()` — safe against XSS injection into the `<script>` block.

---

## Usage

```php
<?php require __DIR__ . '/../partials/app_script_globals.php'; ?>
```

Included after closing `</main>` but before page-specific `<script>` tags, so `window.__USER__` etc. are available when page scripts initialize.

---

## Auth Pages

Auth pages (`login.php`, `register.php`, etc.) do **not** include this partial — they bootstrap via `backend/bootstrap-web-view.php` and have no authenticated user context.

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/js/app.js` | Reads `window.__BASE_PATH__`, `window.__USER__`, `window.__CSRF__` |
| `backend/helpers/functions.php` | `base_url_path()`, `frontend_session_user_payload()` |
| `backend/core/session.php` | `session_get()` |
