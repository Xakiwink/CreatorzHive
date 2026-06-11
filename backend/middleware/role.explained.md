# role.php — Explained

**File:** `backend/middleware/role.php`

---

## Purpose

Procedural bridge to the OOP `RoleMiddleware` class. Provides two global functions for role-based access control.

---

## Functions

### `role_middleware_require(string ...$roles): void`
Enforces that the current user has one of the specified roles. Variadic — accepts any number of role strings.

Example: `role_middleware_require('admin')` or `role_middleware_require('creator', 'brand')`.

### `role_middleware_require_non_admin(): void`
Enforces that the current user is NOT an admin. Used on routes that should be inaccessible to admin accounts (e.g., connecting creator social accounts).

---

## Notes

- Both functions delegate entirely to `RoleMiddleware` via the DI container.
- `RoleMiddleware::requireRoles()` terminates with a 403 response (JSON for API requests, HTML error page for web requests) if the role check fails.
- `RoleMiddleware::requireNonAdmin()` similarly terminates on failure.
- These functions are called within route handler closures in `backend/routes/api.php` after `auth_middleware_handle()`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Middleware/RoleMiddleware.php` | OOP class with all role-check logic |
| `backend/routes/api.php` | Calls these functions on admin-only and non-admin routes |
