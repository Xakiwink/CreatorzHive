# request.php — Explained

**File:** `backend/core/request.php`

---

## Purpose

Procedural HTTP request input functions. All input is sanitized by default — strings are `trim()`'d and `strip_tags()`'d automatically. Used throughout controllers and middleware instead of accessing `$_GET`/`$_POST` directly.

---

## Functions

### `request_sanitize($value): mixed`
Recursively sanitizes input: strings get `trim(strip_tags())`, arrays get mapped recursively, other types returned as-is.

### `request_get(string $key, $default): mixed`
Reads from `$_GET` with sanitization.

### `request_post(string $key, $default): mixed`
Reads from `$_POST` with sanitization.

### `request_all(): array`
Merges `$_GET` and `$_POST`, applies sanitization to all values. POST values override GET if same key.

### `request_only(array $keys): array`
Returns subset of `request_all()` — only the specified keys.

### `request_has(string $key): bool`
Returns `true` if key exists in GET or POST and value is non-empty.

### `request_file(string $key): ?array`
Returns `$_FILES[$key]` or null.

### `request_ip(): string`
Resolves client IP:
1. Checks `X-Forwarded-For` header (for proxies/load balancers) — takes first IP and validates
2. Falls back to `REMOTE_ADDR`
3. Falls back to `'0.0.0.0'`

**Note:** `X-Forwarded-For` is only reliable if a trusted reverse proxy is in front. In direct-access deployments, this could be spoofed.

### `request_user_agent(): string`
Returns `HTTP_USER_AGENT` or `'unknown'`.

### `request_is_json(): bool`
Checks if `Content-Type: application/json` header is present.

### `request_json_body(): array`
Reads and decodes the raw request body (`php://input`) as JSON. Returns empty array if body is empty or invalid JSON.

---

## Sanitization Caveat

`strip_tags()` is appropriate for most text inputs but **intentionally strips HTML**. If any field should allow HTML (e.g., rich text), callers must bypass these functions and read `$_POST` or `php://input` directly.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/*.php` | All controllers use these functions |
| `backend/middleware/auth.php` | Uses `request_ip()` for fingerprinting |
