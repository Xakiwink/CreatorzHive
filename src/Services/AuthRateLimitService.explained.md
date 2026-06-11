# AuthRateLimitService.php — Explained

**File:** `src/Services/AuthRateLimitService.php`

---

## Purpose

Token bucket rate limiting using the `rate_limits` MySQL table. Tracks attempts per key (IP or identifier), supports time-window resets, and sends login lockout alert emails.

---

## Rate Limit Table

The `rate_limits` table has:
- `key` (unique string): e.g. `'login_ip:1.2.3.4'`, `'register_ip:1.2.3.4'`
- `tokens` (int): current attempt count
- `last_refill` (datetime): when the counter was last incremented

---

## Methods

### `row(string $key): ?array`
Fetches the rate limit row for a key. Returns `null` if no row exists yet.

### `resetIfExpired(?array &$row, int $minutes): void`
Resets tokens to 0 if more than `$minutes` have elapsed since `last_refill`. Modifies the `$row` array by reference (updates `tokens` to 0 locally). Used to implement time windows (e.g., reset login count after 15 minutes).

### `reset(?array $row): void`
Unconditionally resets tokens to 0. Used after successful login to clear failed attempt count.

### `incrementLoginAttempts(string $key, ?array $rateRow): void`
### `incrementRegisterAttempts(string $key, ?array $rateRow): void`
### `incrementRateAttempts(string $key, ?array $rateRow): void`
All three delegate to the private `increment()` method. Named separately for semantic clarity in callers.

### `increment(string $key, ?array $rateRow): void` (private)
- If `$rateRow === null`: inserts a new row with `tokens = 1`
- Otherwise: increments `tokens + 1` and updates `last_refill = NOW()`

---

## Lockout Alert

### `maybeSendLoginLockoutAlert(array $user, int $failedAttempts, string $ip): void`
Sends a security alert email after 5+ failed login attempts:
1. Checks `failedAttempts >= 5`
2. Looks up a deduplicated alert key: `'login_alert:user:{id}'`
3. Resets if the alert key is older than 30 minutes (allows re-alerting after cooldown)
4. Skips if already sent (tokens > 0)
5. Sends email via `mailer_send_login_lockout_alert_email()`
6. Records the alert in `rate_limits` to prevent duplicate emails

---

## Usage in AuthController

`AuthController::login()` uses dual rate limiting:
- Per-IP: key = `'login_ip:{ip}'`, max 5 attempts / 15 minutes
- Per-identifier (email): key = `'login_id:{email}'`, max 5 attempts / 15 minutes

Both counters are incremented on failed login. Both are reset on successful login.

`AuthController::register()` uses per-IP limiting:
- Key = `'register_ip:{ip}'`, max 3 attempts / 60 minutes

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AuthController.php` | Primary caller — login and register rate limiting |
| `backend/core/mailer.php` | `mailer_send_login_lockout_alert_email()` called here |
