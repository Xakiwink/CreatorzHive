# ApiMetaService.php — Explained

**File:** `src/Services/ApiMetaService.php`

---

## Purpose

Service for the API meta endpoints: returns the current user + CSRF bootstrap data (`/api/system/me`), and generates a self-documenting route catalog (`/api/system/catalog`) filtered by the current user's role.

---

## Methods

### `authenticatedClientMeta(array $sessionUser): array`
Returns the SPA bootstrap payload:
```php
[
    'user' => [...],               // sanitized user data (via UserPayloadFormatter::forApi())
    'base_url_path' => '/creatorzhive',
    'csrf_token' => '<64-char hex>',
    'csrf_field' => '_token',
    'post_content_type' => 'application/x-www-form-urlencoded',
    'note' => 'Include _token in POST bodies unless Authorization: Bearer … is used',
]
```

The `note` field is documentation embedded in the response — instructs API clients how to handle CSRF.

### `routeVisibleForRole(array $middleware, string $role): bool`
Determines if a route should appear in the catalog for a given role:
- Routes with `role:admin` middleware are hidden from non-admin users
- Routes with `non_admin` middleware are hidden from admin users
- All other routes are visible

### `catalogRoutesForRole(string $role): array`
Reads from `$GLOBALS['cz_router_routes']` (populated by the router when routes are registered) and returns all API routes visible for the given role.

Returns:
```php
[
    ['method' => 'GET', 'route' => 'api/posts', 'middleware' => ['auth']],
    ...
]
```

Results are sorted alphabetically by route name, then method. Only routes with `is_api = true` are included (excludes web/HTML routes).

---

## Notes

- The catalog reads from global state (`$GLOBALS['cz_router_routes']`), so it must be called after all routes are registered.
- This is used by `ApiMetaController::systemApiCatalog()` — useful for frontend developers to discover available endpoints.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/ApiMetaController.php` | Calls both methods |
| `src/Middleware/CsrfMiddleware.php` | Provides the CSRF token |
| `src/Support/UserPayloadFormatter.php` | Formats the user payload |
| `backend/core/router.php` | Populates `$GLOBALS['cz_router_routes']` |
