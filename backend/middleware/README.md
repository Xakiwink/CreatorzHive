# `backend/middleware/` — Procedural Middleware Bridges

## 1. Folder Purpose

Thin procedural wrapper functions that delegate to the OOP `src/Middleware/` classes. Routes call these global functions directly; the functions resolve the OOP middleware from the DI container and invoke it.

## 2. Files Overview

| File | Functions | OOP Class |
|------|-----------|-----------|
| `auth.php` | `auth_middleware_handle(bool $isApi)` | `AuthMiddleware::handle()` |
| `role.php` | `role_middleware_require(...$roles)`, `role_middleware_require_non_admin()` | `RoleMiddleware::require()` |
| `csrf.php` | `csrf_generate_token()`, `csrf_token()`, `csrf_validate_post()` | `CsrfMiddleware` methods |

## 3. Design Notes

- All three files follow the same pattern: resolve class from `Application::getInstance()->getContainer()`, call method
- `auth_middleware_handle(false)` = web mode (redirects to login on failure); `true` = API mode (returns 401 JSON)
- CSRF validation is skipped if the request has an `Authorization: Bearer` header — this allows API clients with JWT tokens to bypass CSRF checks
- `csrf_token()` returns the current session token (generating one if absent)

## 4. Related Files

| File | Relationship |
|------|-------------|
| `src/Middleware/AuthMiddleware.php` | OOP auth implementation |
| `src/Middleware/RoleMiddleware.php` | OOP role check implementation |
| `src/Middleware/CsrfMiddleware.php` | OOP CSRF token management |
| `backend/routes/web.php` | Calls `auth_middleware_handle()` on protected routes |
| `backend/routes/api.php` | Calls `auth_middleware_handle(true)` on API routes |
