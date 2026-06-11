# `src/Middleware/` — Request Guards

## 1. Folder Purpose

Runs before controllers to enforce authentication, CSRF, and role checks.

## 2. Files

| Class | Role |
|-------|------|
| `AuthMiddleware.php` | Requires logged-in user; reloads user from DB; fingerprint check |
| `CsrfMiddleware.php` | Validates `_token` on POST (skipped for Bearer in some cases) |
| `RoleMiddleware.php` | Restricts routes to `admin` or non-admin creators |

## 3. Wrappers

`backend/middleware/*.php` delegates to these classes for the procedural router.

## 4. Execution order

Registered per route in `backend/routes/web.php` and `api.php`, e.g. `['auth', 'csrf']`, `['auth', 'role:admin']`.

## 5. Improvement suggestions

- Log fingerprint mismatches at info level for support debugging.
- Document which API routes are CSRF-exempt.
