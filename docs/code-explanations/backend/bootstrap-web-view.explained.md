# bootstrap-web-view.php — Explained

**File:** `backend/bootstrap-web-view.php`

---

## Purpose

Minimal bootstrap for PHP view files that Apache might execute directly (bypassing the front controller). Loads only what is needed to start a session and generate a CSRF token — no router, no database, no application logic.

---

## What It Does

1. Loads `helpers/functions.php` (for `load_env()`, `base_path()`)
2. Calls `load_env('.env')` to populate environment variables
3. Loads `config/app.php` to define constants (`APP_URL`, `APP_ENV`, etc.)
4. Sets timezone to `Africa/Dar_es_Salaam`
5. Loads `vendor/autoload.php` if present
6. Loads `backend/core/session.php` and `backend/middleware/csrf.php`
7. Calls `session_start_safe()` and `csrf_generate_token()`

---

## When It Is Used

Views under `frontend/pages/` that are accessed directly by Apache (not through `public/index.php`) include this file at the top to get session and CSRF support without the full application stack.

The full application path is `public/index.php → backend/index.php → router → controller → http_view()`. This file is only relevant if direct view access is permitted or required.

---

## What Is NOT Loaded

- Database functions
- Compat bridges
- Router
- Middleware (auth, role, CORS)
- Email / mailer

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Full bootstrap — used for all API and routed requests |
| `frontend/pages/` | Views that may include this file |
| `backend/core/session.php` | Session functions loaded here |
| `backend/middleware/csrf.php` | CSRF token generation loaded here |
