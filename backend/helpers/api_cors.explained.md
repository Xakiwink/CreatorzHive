# api_cors.php — Explained

**File:** `backend/helpers/api_cors.php`

---

## Purpose

Optional CORS support for cross-origin browser requests to the JSON API. Disabled by default — only active when `API_CORS_ORIGINS` is set in the environment.

---

## Configuration

Set `API_CORS_ORIGINS` in `.env` to a comma-separated list of allowed origins:
```
API_CORS_ORIGINS=https://app.example.com,http://localhost:5173
```

Empty or unset = CORS disabled entirely.

---

## Functions

### `api_cors_allowed_origins(): array`
Parses `API_CORS_ORIGINS` from environment. Result is cached in a static variable after first call.

### `api_cors_request_origin(): string`
Returns the `Origin` header from the current request, trimmed.

### `api_cors_origin_is_allowed(string $origin): bool`
Checks if `$origin` is in the allowed origins list (strict string comparison, no wildcards).

### `api_cors_emit_headers(): void`
Emits CORS response headers when the request origin is allowed:
```
Access-Control-Allow-Origin: <origin>
Vary: Origin
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: Content-Type
```
Called by `response_json()` in `backend/core/response.php` on every JSON response.

### `api_cors_handle_preflight(): void`
Handles HTTP `OPTIONS` preflight requests before route dispatch:
- Checks method is OPTIONS
- Checks allowed origins list is non-empty
- Checks request origin is allowed
- Emits full preflight response headers and exits with HTTP 204:
  ```
  Access-Control-Allow-Methods: GET, POST, OPTIONS
  Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With
  Access-Control-Max-Age: 86400
  ```

---

## Notes

- CORS only applies to explicitly allowed origins — no wildcard (`*`) support.
- `Vary: Origin` is always set when CORS headers are emitted to ensure correct CDN/proxy caching.
- `api_cors_handle_preflight()` is called from `backend/index.php` before `router_dispatch()`, so OPTIONS requests never reach route handlers.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/response.php` | Calls `api_cors_emit_headers()` on every JSON response |
| `backend/index.php` | Calls `api_cors_handle_preflight()` before dispatch |
