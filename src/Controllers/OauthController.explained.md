# OauthController.php — Explained

**File:** `src/Controllers/OauthController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Handles the Meta OAuth 2.0 flow for connecting Instagram and Facebook accounts. Two endpoints: the initiation redirect (`connectStart`) and the callback handler (`callbackHandler`). Both are GET page routes that redirect on completion.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$metaOAuth` | `MetaOAuthService` | Builds authorization URL, exchanges code, saves account |
| `$admin` | `AdminService` | Checks integration-enabled flags |

---

## Methods

### `connectStart()` — GET oauth-connect-start

Called when user clicks "Connect Instagram" in Settings → Integrations.

**Flow:**
1. Require authenticated session
2. Block admin users from connecting creator accounts
3. Validate `?platform=` param is a supported OAuth platform
4. Check admin has enabled this integration
5. Check Meta App ID/Secret are configured
6. Generate 16-byte random CSRF `state` token
7. Store state, platform, and user_id in session
8. Redirect to `MetaOAuthService::authorizeUrl($platform, $state)`

### `callbackHandler()` — GET oauth-callback

Called when Meta redirects back with `?code=` and `?state=`.

**Flow:**
1. Check for `?error=` in query string → flash error, redirect
2. Verify `state` matches session value via `hash_equals()` (CSRF protection)
3. Read and clear `oauth_state`, `oauth_platform`, `oauth_user_id` from session
4. Validate user_id and platform still valid
5. Check `?code=` present
6. Call `MetaOAuthService::completeConnection($userId, $platform, $code)`
7. Flash success or error message
8. Redirect to `settings-integrations`

---

## CSRF Protection

The OAuth state parameter is a 32-hex-char random token stored in session before the redirect. On callback, it is verified with `hash_equals()` to prevent CSRF attacks and state fixation.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/MetaOAuthService.php` | Builds URLs, exchanges tokens, saves accounts |
| `src/Services/AdminService.php` | Integration enable checks |
| `backend/routes/web.php` | Route definitions for both endpoints |
| `frontend/js/settings.js` | Initiates the OAuth flow via window.location |
