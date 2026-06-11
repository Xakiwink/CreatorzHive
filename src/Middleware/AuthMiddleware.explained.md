# AuthMiddleware.php — Explained

**File:** `src/Middleware/AuthMiddleware.php`
**Namespace:** `CreatorzHive\Middleware`

---

## Purpose

Guards all authenticated routes. Runs before the controller on any route with `['auth']` middleware. Performs three checks: session fingerprint validation, user existence in DB, and account active status.

---

## Imports (procedural functions via `use function`)

| Function | Source | Purpose |
|----------|--------|---------|
| `response_json` | `backend/core/response.php` | Return 401 JSON for API requests |
| `response_redirect` | `backend/core/response.php` | Redirect to login for page requests |
| `route_url` | `backend/helpers/functions.php` | Build `?route=login` URL |
| `session_destroy_all` | `backend/core/session.php` | Clear session on invalid state |
| `session_fingerprint_is_valid` | `backend/core/session.php` | Verify session fingerprint |
| `session_get_user` | `backend/core/session.php` | Read session user array |
| `session_set_user` | `backend/core/session.php` | Update session user (re-sync from DB) |

---

## Class: AuthMiddleware

### Constructor

```php
public function __construct(UserRepository $users)
```

**Why UserRepository?** The middleware re-fetches the user from the database on every authenticated request. This ensures:
- Deactivated accounts are blocked immediately
- Updated user data (role, name) is reflected without logout

### Method: `handle(bool $isApi = false): void`

**Called by:** `router_run_middleware()` in `backend/core/router.php` for routes with `['auth']` middleware.

**Execution flow:**

**Step 1 — Fingerprint Check:**
```php
if (session_get_user() !== null && !session_fingerprint_is_valid()) {
    session_destroy_all();
    // return 401 JSON or redirect to login
}
```
If a session exists but the fingerprint (IP subnet + User-Agent hash) doesn't match, the session is forcibly destroyed. This prevents session hijacking when the session cookie is stolen.

**Step 2 — DB User Lookup:**
```php
$userId = (int) ($sessionUser['id'] ?? 0);
$user = $userId > 0 ? $this->users->findById($userId) : null;

if ($user === null) {
    // 401 or redirect
}
```
Re-fetches the user from `users` table. Returns 401/redirect if user was deleted since login.

The password is removed from the session data: `unset($user['password'])`.

**Step 3 — Active Status Check:**
```php
if ((int) $isActive !== 1 && $isActive !== true) {
    session_destroy_all();
    // 401 or redirect
}
```
Blocks deactivated accounts (admin set `is_active=0`). Destroys session and redirects.

**API vs Page behavior:** The `$isApi` parameter controls whether the response is JSON (401) or HTTP redirect. This is determined by `router_is_api_request()` checking the `HTTP_ACCEPT` header or `?route=` prefix.

---

## Security Implications

| Check | What It Prevents |
|-------|-----------------|
| Fingerprint validation | Session hijacking via stolen cookie on different IP/browser |
| DB re-fetch | Using stale session after account deletion |
| Active status check | Continued access after admin bans account |
| Password removal from session | Password hash exposure via `window.__USER__` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/router.php` | `router_run_middleware()` calls `auth_middleware_handle()` |
| `backend/middleware/auth.php` | Procedural wrapper that instantiates and calls this middleware |
| `src/Repositories/UserRepository.php` | Injected; used to re-validate user |
| `backend/core/session.php` | All session functions used here |
| `backend/routes/api.php` | Routes that have `['auth']` tag |
| `backend/routes/web.php` | Routes that have `['auth']` tag |
