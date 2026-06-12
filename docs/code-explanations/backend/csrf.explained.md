# csrf.php — Explained

**File:** `backend/middleware/csrf.php`

---

## Purpose

Procedural bridge to the OOP `CsrfMiddleware` class. Provides global functions for CSRF token generation and validation used throughout the application.

---

## Functions

### `csrf_middleware(): CsrfMiddleware`
Resolves and returns the `CsrfMiddleware` instance from the DI container. Helper used by the other three functions.

### `csrf_generate_token(): string`
Generates the CSRF token for the current session if not already present. Returns the token. Called once per request from `backend/index.php`.

### `csrf_token(): string`
Returns the current session's CSRF token. Used in templates to embed the token in forms or pass it to JavaScript.

### `csrf_validate_post(): bool`
Validates the CSRF token on POST requests. Delegates to `CsrfMiddleware::validatePost()` which:
1. Skips validation if `Authorization: Bearer` header is present (API clients)
2. Reads `_token` from the POST body
3. Compares with session token using `hash_equals()` (timing-safe)
4. Throws/terminates with 403 if invalid

---

## Notes

- `csrf_generate_token()` is called on every request in `index.php` — safe to call multiple times (generates once, reuses after).
- `csrf_validate_post()` is called in route handlers for all state-mutating POST endpoints.
- The actual token is a 64-character hex string stored in `$_SESSION['_csrf_token']`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Middleware/CsrfMiddleware.php` | OOP class with CSRF logic |
| `backend/index.php` | Calls `csrf_generate_token()` on every request |
| `backend/routes/api.php` | Route handlers call `csrf_validate_post()` on state-mutating routes |
