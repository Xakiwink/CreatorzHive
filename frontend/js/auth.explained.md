# auth.js — Explained

**File:** `frontend/js/auth.js`

---

## Purpose

Handles all authentication form interactions: login, register, forgot password (OTP flow), password reset, email verification, and resend verification. Runs on auth pages (login, register, etc.) that don't have the main app shell.

---

## Core Utilities (local, not exported)

### `postForm(route, data, btn): Promise<object>`
Submits a form to the API via POST with CSRF token from `window.__CSRF__`. Sets button loading state. Parses JSON response. Attaches HTTP status to response object.

### `setBtnLoading(btn, loading): void`
Disables button and adds/removes `.loading` class during async operations.

### `passwordStrength(password): int (0-4)`
Scores password on 4 criteria: length ≥8, uppercase, digit, special char.

### `updateStrengthMeter(meterEl, barEl, labelEl, password): void`
Updates the visual password strength meter: bar width 0-100%, color (red/yellow/green), label text ("Weak"/"Fair"/"Good"/"Strong").

### `showFieldError(fieldId, message)` / `clearFieldError(fieldId)`
Shows/clears inline validation errors next to form fields.

---

## Form Handlers

### Login (`#loginForm`)
- Restores remembered email from `localStorage.creatorzhive_remember_email`
- On submit: posts to `'login'` route
- On success: saves/clears remember-me email, redirects to `res.data.redirect`
- Password show/hide toggle

### Register (`#registerForm`)
- Password strength meter on `#password` input
- Role selection buttons (`creator`/`brand`) update hidden `#role` input
- Google register button href syncs with selected role
- Live username availability check via `GET check_username` (debounced 400ms)
- Client-side terms validation before submit
- On success: swaps form panel for success panel
- Per-field server error display via `applyRegisterErrors(res.errors)`
- Password show/hide toggle for both fields

### Forgot Password (`#forgotForm`)
- 60-second cooldown timer after submission (persisted in `localStorage` across page reloads)
- Starts cooldown on 429 response too
- Button shows countdown: "Resend OTP in 42s"

### Reset Password (`#resetForm`)
- Password strength meter
- Password show/hide toggles for both fields
- On success: redirects after 600ms delay

### Email Verification (`#verifyPending`)
- Reads token from `window.__VERIFY_TOKEN__` (injected by PHP template)
- On load: immediately sends GET to `verify` route with token
- Shows `#verifyOk` panel on success, redirects after 1.6s
- Shows `#verifyFail` on error, `#verifyMissing` if no token

### Resend Verification (`#resendForm`)
- Posts to `resend-verification` route with email

---

## Notes

- Written in ES5-compatible syntax (no arrow functions, no `const`/`let`) — likely for compatibility with older browsers on the auth pages which don't load the full app bundle.
- Does not use `window.api()` from `app.js` — has its own `postForm()` to avoid dependency on the app shell.
- `routeQueryUrl()` is a local copy of `routeQuery()` from `app.js` (same logic, same subdirectory-safe base path handling).

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/routes/api.php` | `login`, `register`, `forgot-password`, `reset-password`, `verify`, `resend-verification`, `check_username` routes |
| `frontend/pages/auth/login.php` etc. | Templates that include this script |
