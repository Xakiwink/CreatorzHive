# Router.php — Explained

**File:** `src/Core/Routing/Router.php`

---

## Purpose

OOP HTTP router. Stores route definitions and dispatches requests to controller methods or callables. **This class is currently unused** — the active router is the procedural `backend/core/router.php`. This class exists from the OOP migration but is not wired into `AppServiceProvider` or `Application`.

---

## Route Registration

### `register(string $method, string $route, $handler, array $middleware): void`
Stores a route. Handler types supported:
- `[ControllerClass::class, 'methodName']` — OOP controller
- `'function_name'` — global function
- `callable` — closure

### `get()` / `post()`
Convenience wrappers for `register('GET', ...)` and `register('POST', ...)`.

---

## Dispatch

### `dispatch(Container $container): void`
1. Gets HTTP method and resolves the route name
2. Looks up route in `$this->routes[METHOD][route]`
3. If not found: calls `error_handler_not_found()` (404)
4. Runs middleware via `runMiddleware()`
5. Calls the handler:
   - `[class, method]` array → resolves controller from container, calls method
   - String function name → calls it
   - Callable → calls it
   - Otherwise: 404

### `resolveRoute(): string`
Resolves current route name:
1. Delegates to `router_resolve_route()` if it exists (procedural router)
2. Falls back to `$_GET['route']` trimmed
3. Defaults to `'login'`

---

## Middleware

### `runMiddleware(array $middlewares): void`
1. Delegates to `router_run_middleware()` if it exists
2. Otherwise handles only `'auth'` middleware inline

All other middleware strings (`role:admin`, `non_admin`, `csrf`) are not handled in the fallback — this is another indication this class is not the active router.

---

## State

- `reset()`: clears all routes and disables API mode
- `apiMode(bool $enabled)`: sets API mode flag (affects 404 response format)

---

## Notes

- This class delegates route resolution and middleware execution to procedural functions when they exist — which they always do in the running application. It's essentially a no-op wrapper in production.
- The active router is `backend/core/router.php` with its `router_dispatch()` function.
- If this class were wired in, it would need to be registered in `AppServiceProvider` and called from `backend/index.php` instead of `router_dispatch()`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/router.php` | The ACTIVE router (procedural) |
| `src/Core/Application.php` | Does NOT wire this class |
| `src/Providers/AppServiceProvider.php` | Does NOT register this class |
