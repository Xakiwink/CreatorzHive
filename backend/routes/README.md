# `backend/routes/` — Route Definitions

## 1. Folder Purpose

Registers all HTTP routes using `router_register()`. Two files split routes by type: web (HTML pages) and API (JSON endpoints).

## 2. Files Overview

| File | Route Count | Auth | Response Type |
|------|-------------|------|---------------|
| `web.php` | ~23 GET routes | Mixed | HTML views via `ViewRenderer` |
| `api.php` | ~80+ POST/GET routes | Almost all auth-required | JSON via `JsonResponder` |

## 3. Web Routes (`web.php`)

- All GET-only
- Protected routes call `auth_middleware_handle(false)` — redirect to login on failure
- OAuth callbacks (`/oauth-callback`, `/google-callback`) have no auth middleware — the OAuth code itself establishes the session
- `settings` and `settings-profile` both dispatch to the same controller method

## 4. API Routes (`api.php`)

- Most call `auth_middleware_handle(true)` — return 401 JSON on failure
- Admin-only routes additionally call `role_middleware_require('admin')`
- Routes map a `?route=<name>` key to a `Controller::method` string
- Dispatched by `router_dispatch()` in `backend/index.php`

## 5. Route Key Format

```
?route=dashboard_data   → DashboardController::data()
?route=create_post      → PostController::create()
?route=deal_update_status → DealController::updateStatus()
```

## 6. Related Files

| File | Relationship |
|------|-------------|
| `backend/core/router.php` | `router_register()`, `router_dispatch()` |
| `backend/middleware/auth.php` | `auth_middleware_handle()` called in routes |
| `backend/middleware/role.php` | `role_middleware_require()` for admin routes |
| `src/Controllers/` | Dispatch targets |
