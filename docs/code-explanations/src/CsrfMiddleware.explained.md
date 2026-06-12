# CsrfMiddleware.php — Explained

**File:** `src/Middleware/CsrfMiddleware.php`
**Namespace:** `CreatorzHive\Middleware`

---

## Purpose

OOP CSRF protection middleware. Generates per-session tokens and validates them on POST requests. Called by the router for all POST API routes tagged with `'csrf'`.

---

## Methods

### `validatePost(): void`

Validates the `_token` form field on POST requests.

**Skips validation if:**
- `Authorization: Bearer ...` header present (API token auth)
- Not a POST request

**Validates:**
- Reads `_token` from POST body
- Reads `_csrf_token` from session
- Compares with `hash_equals()` (constant-time, prevents timing attacks)
- Returns 403 JSON if missing or mismatched

### `generateToken(): string`

Generates a 64-char hex token (`bin2hex(random_bytes(32))`) and stores in `$_SESSION['_csrf_token']`. Only generates once per session — reuses existing token on subsequent calls.

### `token(): string`

Alias for `generateToken()`. Returns current session CSRF token.

---

## Integration

Token is generated during bootstrap by `csrf_generate_token()` in `backend/index.php`.

Frontend receives the token via:
1. PHP global `window.__CSRF__` set in `frontend/pages/partials/app_script_globals.php`
2. JSON API at `GET api/api_me` which includes `csrf_token`

Frontend submits it as the `_token` field in all POST requests.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/middleware/csrf.php` | Procedural wrapper calling `validatePost()` |
| `backend/index.php` | Calls `csrf_generate_token()` on every request |
| `frontend/pages/partials/app_script_globals.php` | Exposes token to JS |
| `frontend/js/utils.js` | Reads `window.__CSRF__` and injects into fetch requests |
