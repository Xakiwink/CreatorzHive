# AuthController.php — Explained

**File:** `src/Controllers/AuthController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

All authentication flows: registration, login, logout, email verification, password reset via OTP or link, and username availability. Renders HTML pages and handles JSON API endpoints.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$auth` | `AuthService` | Password hashing, token/OTP generation |
| `$rateLimit` | `AuthRateLimitService` | Token-bucket rate limiting |
| `$admin` | `AdminService` | Feature flags (registration_enabled, etc.) |
| `$users` | `UserRepository` | Find/create/update user records |
| `$notifications` | `NotificationService` | Welcome notification on register |

---

## Page Render Methods

| Method | Route | Template |
|--------|-------|----------|
| `loginPage()` | `GET login` | `auth/login` |
| `registerPage()` | `GET register` | `auth/register` |
| `forgotPage()` | `GET forgot-password` | `auth/forgot-password` |
| `resetPage()` | `GET reset-password` | `auth/reset-password` |
| `verifyPage()` | `GET verify-email` | `auth/verify-email` |
| `logoutPage()` | `GET logout` | — redirects to login after `session_destroy_all()` |

---

## API Methods

### `register()` — POST register

1. Checks `registration_enabled` admin flag — returns 403 if disabled
2. Rate limit: 3 attempts per IP per 60 minutes
3. Validates: name, username (unique, regex `[A-Za-z0-9._-]{3,100}`), email (unique), password (min 8, confirmed), role (creator|brand), terms checkbox
4. Creates user, generates email verification token, sends verification email
5. Creates welcome notification

### `login()` — POST login

Accepts `email` or `login` field (either email or username).

1. Dual rate limiting: 5 per IP per 15 min **and** 5 per identifier-hash per 15 min
2. Lookup: by email if valid email format, else by username
3. Missing user → `dummyPasswordCheck()` (prevents timing-based user enumeration), returns 401
4. Wrong password → increments both rate limiters, may trigger lockout alert email
5. Checks `is_active` and `require_email_verification` admin flag
6. On success: `session_regenerate_safe()` → set session user (password stripped) → `updateLastLogin()` → reset rate counters
7. Returns `{ data: { redirect: '/?route=dashboard' } }`

### `logout()` — POST logout

`session_destroy_all()` and returns redirect JSON.

### `forgotPassword()` — POST forgot-password

1. Checks `forgot_password_enabled` flag
2. Rate limit: 5 per IP per 60 min
3. Validates email, looks up user (silently skips if not found)
4. Enforces 60-second minimum between requests per user
5. Generates 6-digit OTP → `mailer_send_password_reset_otp_email()`
6. **Always returns 200** — no user enumeration ("If the email exists...")

### `resetPassword()` — POST reset-password

Supports two flows:
- **Token flow:** `token=<64-char-hex>` (from email link)
- **OTP flow:** `email=<addr>&otp=<6-digits>`

Rate limit: 10 attempts per IP per 15 min, error message includes countdown.

### `verify()` — GET verify

Validates `?token=` query param, marks email verified, marks token used. Returns redirect to login.

### `checkUsername()` — GET check_username

Returns `{ available: bool }`. Supports `exclude_user_id` to allow checking against current user's existing username during profile edit.

### `resendVerification()` — POST resend-verification

Re-sends verification email only if account exists and is unverified. **Always returns 200** — no user enumeration.

---

## Security Notes

- No user enumeration in `forgotPassword` or `resendVerification`
- `dummyPasswordCheck()` prevents timing attacks in `login`
- Dual rate limiting defeats distributed login attacks
- Session regeneration before setting authenticated state

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/AuthService.php` | Core auth logic |
| `src/Services/AuthRateLimitService.php` | Rate limiting |
| `src/Repositories/UserRepository.php` | User DB queries |
| `backend/core/mailer.php` | Email sending functions |
