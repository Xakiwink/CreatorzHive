# MetaOAuthService.php — Explained

**File:** `src/Services/MetaOAuthService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

Manages the complete Meta OAuth 2.0 flow for connecting Instagram Business and Facebook Page accounts. Handles token exchange, token extension to long-lived (~60 days), page/IG account discovery, and saving connected accounts to the database.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | Fetch social_account ID after upsert to dispatch analytics job |

---

## Methods

### `isConfigured(): bool`
Returns true if both `meta_app_id` and `meta_app_secret` are set (via `platform_api_secrets_resolve()`). Checked before any OAuth operation.

### `redirectUri(): string`
Returns the OAuth callback URL. Priority:
1. `META_OAUTH_REDIRECT_URI` env var (for override)
2. Auto-constructed: `APP_URL + base_url_path() + /?route=oauth-callback`

### `allowedPlatforms(): array`
Returns `['instagram', 'facebook']`.

### `scopes(string $platform): string`
Platform-specific OAuth scopes:
- **Instagram**: `instagram_basic,instagram_content_publish,business_management,pages_show_list,pages_read_engagement`
- **Facebook**: `pages_manage_posts,pages_read_engagement,pages_show_list,pages_read_engagement`

### `authorizeUrl(string $platform, string $state): string`
Builds Facebook OAuth dialog URL:
`https://www.facebook.com/v20.0/dialog/oauth?client_id=...&redirect_uri=...&state=...&scope=...&response_type=code`

### `exchangeCode(string $code): array`
Exchanges authorization code for short-lived user access token via Meta Graph API v20.0. Returns `{ success, access_token, expires_in }`.

### `longLivedToken(string $shortToken): array`
Exchanges short-lived token (1-2 hours) for a long-lived token (~60 days) using `grant_type=fb_exchange_token`. Returns `{ success, access_token }`.

### `fetchPages(string $userAccessToken): array`
Calls `/me/accounts` with fields `id,name,username,access_token,instagram_business_account{id,username,name}`. Returns array of Facebook Page objects. Each page may contain an `instagram_business_account` nested object.

### `saveFacebookPage(int $userId, array $page): bool`
Upserts a Facebook Page as a `social_accounts` record (platform=facebook). Token expires in 55 days (slightly before the 60-day long-lived expiry).

### `saveInstagramAccount(int $userId, array $page, array $ig): bool`
Upserts an Instagram Business Account. Uses the Page's `access_token` (not the user token) — this is correct per Meta's API requirements.

### `upsertSocialAccount(int $userId, array $data): void`
Generic account upsert. After saving, dispatches a `fetch_analytics` job for the newly connected account.

### `completeConnection(int $userId, string $platform, string $code): array`

The top-level orchestration method. Called by `OauthController::callbackHandler()`.

**Full flow:**
1. Validate platform
2. `exchangeCode($code)` → short-lived token
3. `longLivedToken(shortToken)` → ~60-day token
4. `fetchPages(userToken)` → get user's Facebook Pages
5. If no pages found → return error
6. For **facebook**: find first page → `saveFacebookPage()`
7. For **instagram**: iterate pages looking for `instagram_business_account` → `saveInstagramAccount()`
8. Return `{ success, message }`

---

## Important: Instagram Connection Requirement

Instagram must be connected as a Business Account linked to a Facebook Page. Personal Instagram accounts cannot be connected. Users need to:
1. Convert to Business/Creator account in Instagram settings
2. Link to a Facebook Page in Meta Business Suite

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/OauthController.php` | Calls `completeConnection()` |
| `src/Services/PlatformApiSecretsService.php` | `platform_api_secrets_resolve()` for credentials |
| `backend/compat/services.php` | Procedural wrappers: `meta_oauth_*()` |
| `src/Repositories/SocialAccountRepository.php` | Account persistence |
