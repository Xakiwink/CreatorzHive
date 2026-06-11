# router.php — Explained

**File:** `backend/core/router.php`

---

## Purpose

The **procedural HTTP router** — the actual dispatcher used in production. Stores route registrations in `$GLOBALS['cz_router_routes']` and dispatches to controller methods or callables when `router_dispatch()` is called at the end of `backend/index.php`.

Note: An OOP `Router` class also exists in `src/Core/Routing/Router.php` but is currently unused. This procedural version is what runs.

---

## Functions

### `router_reset(): void`
Initializes the route registry in `$GLOBALS`. Called at the start of each request before route files are loaded.

Sets `$GLOBALS['cz_router_routes'] = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []]` and `$GLOBALS['cz_router_api_mode'] = false`.

### `router_api_mode(bool $enabled): void`
Sets a flag indicating the router is now registering API routes. Used between loading `web.php` and `api.php` to tag routes.

### `router_register(string $method, string $route, $handler, array $middleware): void`
Stores a route definition. Route key is trimmed of `/` slashes.

**Stored structure:**
```php
$GLOBALS['cz_router_routes']['GET']['dashboard'] = [
    'handler' => [DashboardController::class, 'index'],
    'middleware' => ['auth'],
    'is_api' => false,
];
```

### `router_get_action(string $route, string $controller, string $action, array $middleware): void`
### `router_post_action(string $route, string $controller, string $action, array $middleware): void`
Convenience wrappers that register `[$controller, $action]` array as the handler.

**Used in:** `backend/routes/web.php` and `backend/routes/api.php`

### `router_resolve_route(): string`
Determines which route to dispatch.

**Priority:**
1. `?route=` query parameter (e.g., `?route=dashboard`)
2. URI path parsing (for clean URL installs)
3. Default: `'login'`

### `router_is_api_request(): bool`
Returns `true` if:
- Route starts with `api/`
- HTTP `Accept: application/json` header present

Used to determine JSON vs HTML response format in middleware.

### `router_run_middleware(array $middlewares): void`
Executes middleware tags in order:
- `'auth'` → `auth_middleware_handle(router_is_api_request())`
- `'csrf'` → `csrf_validate_post()`
- `'non_admin'` → `role_middleware_require_non_admin()`
- `'role:{roles}'` → `role_middleware_require(...$roles)`

Each middleware function may terminate the request (die/exit) if validation fails.

### `router_dispatch(): void`
Main dispatch function. Called at end of `backend/index.php`.

**Steps:**
1. Resolve route string
2. Look up in `$GLOBALS['cz_router_routes'][$method][$route]`
3. If not found: `error_handler_not_found()` → 404 response
4. Run middleware via `router_run_middleware()`
5. If handler is `[$class, $method]`: resolve from `$GLOBALS['cz_container']`, call method
6. If handler is callable/function: call directly

---

## Route Resolution Example

```
GET /?route=dashboard
    → $method = 'GET'
    → $route = 'dashboard'
    → $definition = ['handler' => [DashboardController::class, 'index'], 'middleware' => ['auth']]
    → router_run_middleware(['auth'])
       → auth_middleware_handle(false)
          → AuthMiddleware::handle()
    → $controller = $container->get(DashboardController::class)
    → $controller->index()
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Calls `router_reset()`, loads routes, calls `router_dispatch()` |
| `backend/routes/web.php` | Calls `router_get_action()` for page routes |
| `backend/routes/api.php` | Calls `router_get_action()` and `router_post_action()` for API routes |
| `backend/middleware/auth.php` | `auth_middleware_handle()` called by this router |
| `backend/middleware/csrf.php` | `csrf_validate_post()` called by this router |
| `backend/middleware/role.php` | Role middleware functions called by this router |
| `src/Core/Routing/Router.php` | OOP equivalent (currently unused) |
