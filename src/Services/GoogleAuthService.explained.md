# GoogleAuthService.php — Explained

**File:** `src/Services/GoogleAuthService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

Google Sign-In OAuth 2.0 implementation. Handles the authorization URL, code exchange, and profile fetching. No external dependencies — uses cURL directly. No database interaction (profile data returned to controller which decides what to do with it).

---

## Methods

### `isConfigured(): bool`
Returns true if both `clientId()` and `clientSecret()` are non-empty.

### `redirectUri(): string`
Callback URL for Google to redirect to. Priority:
1. `GOOGLE_AUTH_REDIRECT_URI` env var
2. Auto-constructed: `APP_URL + base_url_path() + /?route=google-callback`

### `authorizeUrl(string $state): string`
Builds the Google OAuth consent page URL:
```
https://accounts.google.com/o/oauth2/v2/auth?
  client_id=...&redirect_uri=...&response_type=code
  &scope=openid+email+profile&state=...
  &access_type=online&prompt=select_account
```

`prompt=select_account` forces Google to show the account picker even if one account is already signed in — avoids silently using wrong account.

### `exchangeCode(string $code): array`
POSTs to `https://oauth2.googleapis.com/token` with `grant_type=authorization_code`. Returns:
```php
['ok' => true, 'access_token' => '...', 'id_token' => '...']
// or
['ok' => false, 'error' => '...']
```

### `fetchUserProfile(string $accessToken): array`
GETs `https://openidconnect.googleapis.com/v1/userinfo` with `Authorization: Bearer` header.

Normalizes profile into:
```php
[
  'google_id'      => string,  // the `sub` (subject) field — unique Google user ID
  'email'          => string,
  'name'           => string,
  'given_name'     => string,
  'family_name'    => string,
  'picture'        => string,  // URL to Google avatar
  'email_verified' => bool,
]
```

Requires both `sub` and `email` to be present — returns error if missing.

---

## Credential Resolution

Both `clientId()` and `clientSecret()` have dual fallback:
1. `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` env vars (`.env` file)
2. `platform_api_secrets_resolve('google_client_id')` — DB/file stored via admin panel

This allows setting credentials either in `.env` (for development) or through the admin UI (for production).

---

## HTTP Implementation

All requests use cURL directly (not via `SocialApiService`). Timeouts: 10s connect, 30s read. JSON response decoded and returned.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/GoogleAuthController.php` | Consumes this service |
| `src/Services/PlatformApiSecretsService.php` | Fallback credential lookup |
| `backend/helpers/functions.php` | `google_auth_is_configured()`, `google_auth_start_url()` |
