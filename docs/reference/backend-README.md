# `backend/` — Bootstrap & Legacy Bridge

## 1. Folder Purpose

Application entry (via `index.php`), environment loading, procedural router, route tables, compat globals, and storage.

## 2. Key paths

| Path | Purpose |
|------|---------|
| `index.php` | Main bootstrap: env, OOP, session, routes, dispatch |
| `bootstrap-oop.php` | `Application::boot()` |
| `bootstrap-procedural.php` | Router, jobs, compat includes |
| `bootstrap-web-view.php` | Minimal boot for standalone PHP views |
| `routes/web.php` | Page routes (login, dashboard, google-auth, …) |
| `routes/api.php` | JSON API routes |
| `compat/` | `model_*`, `auth_service_*` → OOP |
| `core/` | Router, session, database helpers |
| `middleware/` | Delegates to `src/Middleware` |
| `helpers/functions.php` | `route_url`, `google_auth_start_url`, env helpers |
| `config/app.php` | Constants from env |
| `storage/logs/` | Error logs |

## 3. Request lifecycle

See [SYSTEM_OVERVIEW.md](../SYSTEM_OVERVIEW.md#4-complete-request-lifecycle).

## 4. Improvement suggestions

- Unify `bootstrap-web-view.php` with full OOP boot for consistent helpers.
- Rotate log files in `storage/logs/`.
