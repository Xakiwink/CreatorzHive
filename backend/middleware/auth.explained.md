# auth.php — Explained

**File:** `backend/middleware/auth.php`

---

## Purpose

Procedural bridge to the OOP `AuthMiddleware` class. Provides a single global function that route handlers can call to enforce authentication.

---

## Function

### `auth_middleware_handle(bool $isApi = false): void`
Delegates to `AuthMiddleware::handle($isApi)` via the DI container.

- `$isApi = false` (default): unauthenticated users are redirected to the login page
- `$isApi = true`: unauthenticated users receive a 401 JSON response

Called from route definitions in `backend/routes/api.php` and `backend/routes/web.php`.

---

## Notes

- All actual authentication logic (session validation, fingerprint check, user re-fetch) lives in `src/Middleware/AuthMiddleware.php`.
- This file only exists as a bridge — the function body is one line.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Middleware/AuthMiddleware.php` | OOP class with all auth logic |
| `backend/routes/api.php` | Calls `auth_middleware_handle(true)` on protected routes |
| `backend/routes/web.php` | Calls `auth_middleware_handle()` on protected pages |
