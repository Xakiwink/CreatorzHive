# auth.php — Explained

**File:** `backend/compat/auth.php`

---

## Purpose

Procedural compatibility bridge for `AuthService`. Exposes password hashing, token generation, and OTP validation as global functions for jobs and legacy callers.

---

## Functions Bridged

| Global Function | AuthService Method | Description |
|----------------|-------------------|-------------|
| `auth_service_hash_password(string $password)` | `hashPassword()` | bcrypt hash a password |
| `auth_service_check_password(string $password, string $hash)` | `checkPassword()` | Verify password against bcrypt hash |
| `auth_service_dummy_password_check(string $password)` | `dummyPasswordCheck()` | Perform a dummy bcrypt verify (timing attack prevention) |
| `auth_service_generate_verification_token(int $userId)` | `generateVerificationToken()` | Create and store an email verification token |
| `auth_service_generate_password_reset_token(int $userId)` | `generatePasswordResetToken()` | Create and store a password reset token |
| `auth_service_generate_password_reset_otp(int $userId)` | `generatePasswordResetOtp()` | Create and store a 6-digit OTP |
| `auth_service_validate_reset_token(string $token)` | `validateResetToken()` | Validate token, return user row or null |
| `auth_service_validate_reset_otp(int $userId, string $otp)` | `validateResetOtp()` | Validate OTP, return user row or null |
| `auth_service_validate_verification_token(string $token)` | `validateVerificationToken()` | Validate verification token, return user row or null |

---

## Notes

- The comment "Auth service compat for jobs and legacy callers" indicates this is used by job handlers (e.g., when jobs need to hash passwords or validate tokens outside an HTTP context).
- All implementation is in `src/Services/AuthService.php`.
- This bridge follows the same `Application::instance()->get(...)` pattern as `models.php` and `services.php`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/AuthService.php` | OOP class with all implementation |
| `backend/compat/models.php` | Same pattern for repositories |
| `backend/compat/services.php` | Same pattern for other services |
| `backend/bootstrap-procedural.php` | Loads this file |
