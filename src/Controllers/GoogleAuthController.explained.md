# GoogleAuthController.php — Explained

**File:** `src/Controllers/GoogleAuthController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Handles Google Sign-In OAuth 2.0 flow. Supports both login (existing users) and registration (new users via Google). If a Google account's email matches an existing user, it links the Google ID to that account.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$google` | `GoogleAuthService` | Builds authorize URL, exchanges code, fetches profile |
| `$users` | `UserRepository` | Find/create/link/update user records |
| `$auth` | `AuthService` | Hash random password for new Google users |
| `$admin` | `AdminService` | registration_enabled and require_email_verification flags |
| `$notifications` | `NotificationService` | Welcome notification for new users |

---

## Methods

### `start()` — GET google-auth-start

Initiates the Google OAuth flow.

1. Redirect to dashboard if already logged in
2. Check `google_auth_is_configured()` — flash error if not
3. Read `?role=` (creator|brand, defaults creator)
4. Generate random state token, store in session with role
5. Redirect to `GoogleAuthService::authorizeUrl($state)`

### `callback()` — GET google-auth-callback

Handles Google's redirect back.

1. Check `?error=` → flash auth_error, redirect
2. Verify `state` via `hash_equals()`, clear from session
3. Exchange `?code=` for access token via `GoogleAuthService::exchangeCode()`
4. Fetch profile (google_id, email, name, picture, email_verified)
5. Find existing user by `google_id` OR by email
   - If found by email only: link google_id to existing account
6. If no user found: call `registerFromGoogle()` (creates new account)
7. Update profile if needed (mark email_verified, save avatar URL if blank)
8. Establish session via `establishSession()`
9. Redirect to dashboard

---

## Private Methods

### `registerFromGoogle(array $profile): ?array`

Creates a new user from Google profile data:
- Checks `registration_enabled` flag
- Uses role stored in session
- Auto-generates unique username via `UserRepository::suggestAvailableUsername()`
- Generates random password (user will never use it — Google auth only)
- Sets `email_verified` if Google reports it
- Stores Google avatar URL (truncated to 500 chars)
- Sends welcome notification

### `maybeUpdateProfileFromGoogle(int $userId, array $profile): void`

Called on returning Google users. Only updates if:
- Google's `email_verified = true` → set DB verified
- User has no avatar yet AND Google provides one → save it

### `establishSession(array $user): bool`

Validates account can log in:
- `is_active = 1` check
- `require_email_verification` admin flag check
- `session_regenerate_safe()` → set session user → update last_login
- Returns `false` (don't redirect) if either check fails

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/GoogleAuthService.php` | OAuth URL builder, code exchange, profile fetch |
| `src/Repositories/UserRepository.php` | `findByGoogleId()`, `createOAuthUser()`, `linkGoogleId()` |
| `backend/routes/web.php` | Route definitions |
