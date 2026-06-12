# RoleMiddleware.php — Explained

**File:** `src/Middleware/RoleMiddleware.php`
**Namespace:** `CreatorzHive\Middleware`

---

## Purpose

OOP role-enforcement middleware. Called by the procedural router via compat bridges (`role_middleware_require()`, `role_middleware_require_non_admin()`). Terminates the request with 403 if role requirements not met.

---

## Methods

### `requireRoles(string ...$roles): void`

Accepts any number of allowed roles. Checks session user's `role` against the list.

- Not logged in → 401 JSON
- Role not in list → 403 JSON

**Example:** `requireRoles('admin')` for admin-only routes.

### `requireNonAdmin(): void`

Blocks admin users from accessing creator/brand routes.

- Not logged in → 401 JSON
- Role is `admin` → 403
  - If API request: 403 JSON
  - If page request: renders `errors/403-admin` HTML view

This prevents admins from accidentally creating posts, deals, or invoices under their own account.

---

## How It's Called

The procedural router (`backend/core/router.php`) calls:
- `role_middleware_require_non_admin()` → `$container->get(RoleMiddleware::class)->requireNonAdmin()`
- `role_middleware_require('admin')` → `$container->get(RoleMiddleware::class)->requireRoles('admin')`

Both functions are defined in `backend/middleware/role.php`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/middleware/role.php` | Procedural wrapper functions |
| `backend/core/router.php` | Calls middleware during dispatch |
| `backend/routes/api.php` | Routes tagged with `'role:admin'` or `'non_admin'` |
