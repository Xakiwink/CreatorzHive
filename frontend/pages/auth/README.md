# `frontend/pages/auth/` — Authentication UI

## 1. Folder Purpose

Login, registration, forgot/reset password, and email verification screens.

## 2. Files

| File | Purpose |
|------|---------|
| `login.php` | Sign in form + **Continue with Google** |
| `register.php` | Sign up + role toggle + Google button |
| `forgot-password.php` | Request reset email |
| `reset-password.php` | Set new password from token |
| `verify-email.php` | Email verification landing |

## 3. Google button behavior

- Always visible on login/register.
- Links to `google_auth_start_url('creator')` → `?route=google-auth&role=...`
- Register page: `auth.js` updates href when Creator/Brand role changes (`#googleRegisterBtn`).
- Server handles OAuth in `GoogleAuthController` (see [src/Controllers/README.md](../../../src/Controllers/README.md)).

## 4. Styling

- `frontend/css/auth.css` — `.btn-google`, `.social-row`, form layout.

## 5. Bootstrap note

These files include `backend/bootstrap-web-view.php` (session + CSRF only). Full app uses `AuthController` + `ViewRenderer` for the same templates when routed via `?route=login`.
