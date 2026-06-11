# session.php — Explained

**File:** `backend/core/session.php`

---

## Purpose

All PHP session management as procedural functions. Handles starting sessions securely, storing/retrieving values, session fingerprinting for hijack detection, flash messages, and user authentication state.

---

## Functions

### `session_start_safe(): void`
Starts PHP session with secure settings:
- `httponly=true` — cookie not accessible via JavaScript
- `samesite=Strict` — prevents CSRF via cross-site requests
- `secure` — configurable via `SESSION_SECURE` env var
- Lifetime: `SESSION_LIFETIME` env (in minutes) × 60

In PHPUnit mode: disables cookies (uses memory-based sessions).

### `session_set(string $key, $value): void` / `session_get(string $key, $default): mixed`
Generic session read/write.

### `session_has(string $key): bool`
Returns `isset($_SESSION[$key])`.

### `session_remove(string $key): void`
Removes a key from session.

### `session_destroy_all(): void`
Clears all session data and destroys the session. Called on logout.

### Flash Messages

**`session_flash(string $key, $value): void`**
Stores a value in `$_SESSION['_flash'][$key]`.

**`session_get_flash(string $key): mixed`**
Reads and immediately deletes a flash value. Returns null if not set. Used for OAuth redirect messages.

### `session_regenerate_safe(): void`
Calls `session_regenerate_id(true)` if session is active. Called before setting user after login to prevent session fixation.

### `session_set_user(array $user): void`
Stores user array in `$_SESSION['user']` AND generates a session fingerprint: `$_SESSION['_fingerprint']`.

### `session_get_user(): ?array`
Returns `$_SESSION['user']` if set and is array, else null.

### `session_is_logged_in(): bool` / `session_user_is_admin(): bool`
Convenience checks.

---

## Session Fingerprinting

### `session_fingerprint_generate(): string`
Creates a SHA-256 hash of:
- Lowercase User-Agent
- IP address subnet scope (first 3 octets for IPv4, first 4 groups for IPv6)

### `session_fingerprint_is_valid(): bool`
Compares stored fingerprint against freshly computed one using `hash_equals()` (constant-time). Returns `false` if fingerprint missing.

### `session_ip_scope(string $ip): string`
Extracts subnet from IP:
- IPv4 `1.2.3.4` → `1.2.3` (class C)
- IPv6 `2001:db8::1` → `2001:db8:0:0`

**Purpose:** Allows small IP changes within same subnet (DHCP renewals) without breaking session, while still catching cross-region hijacking.

---

## Security Design

- HTTP-only cookies prevent XSS token theft
- SameSite=Strict prevents CSRF via cross-origin navigation
- Session regeneration after login prevents session fixation
- Fingerprinting adds hijack detection layer

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Middleware/AuthMiddleware.php` | Calls `session_fingerprint_is_valid()` |
| `backend/index.php` | Calls `session_start_safe()` |
| `src/Controllers/AuthController.php` | Calls `session_regenerate_safe()`, `session_set_user()` |
