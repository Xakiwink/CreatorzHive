# SocialApiService.php — Explained

**File:** `src/Services/SocialApiService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

The **central integration service** for all social media platform API calls. Handles publishing posts to Instagram, Facebook, TikTok, YouTube, and Twitter, as well as fetching analytics data and token management (refresh, revoke).

---

## Class: SocialApiService

### Constructor
Receives `Connection $db`. Used for lookups when needed (e.g., fetching platform config).

### Key Methods

#### `mockEnabled(): bool`
Returns `true` when `SOCIAL_API_MOCK_FALLBACK=true` env variable is set. Default `true` in dev. When mock is enabled, failed/missing API calls return simulated success responses.

#### `httpRequest(string $method, string $url, array $headers, ?array $body): array`
Generic cURL HTTP client. Called by all platform-specific publish methods.

**Returns:** `['ok' => bool, 'status' => int, 'data' => array|null, 'error' => string|null]`

**Timeout:** Connect 10s, Total 30s.

**Note:** If cURL is not installed, returns `['ok' => false, 'error' => 'cURL extension is not enabled']`. Does NOT throw an exception.

#### `publish(array $account, array $post): array`
Main entry point for publishing. Checks if the platform integration is enabled via `admin_service_integration_enabled($platform)`, then routes to the appropriate platform method.

**Input:**
- `$account` — row from `social_accounts` table (with decrypted token)
- `$post` — row from `posts` table

**Returns:** `['success' => bool, 'platform_post_id' => string, 'platform_url' => string|null]`

#### `publishToInstagram(array $account, array $post): array`
Two-step Instagram Graph API publish:
1. `POST /{businessId}/media` with `image_url` and `caption` → returns `container_id`
2. `POST /{businessId}/media_publish` with `creation_id` → returns `post_id`

**Requires:** `access_token` + `platform_user_id` (Instagram Business Account ID)

**Note:** Requires a publicly accessible image URL. Local development uploads won't work without `ngrok` or similar tunnel.

#### `publishToTiktok(array $account, array $post): array`
Calls TikTok Open Platform v2 `post/publish/inbox/video/init/`. Creates a draft in the creator's TikTok inbox. Does NOT directly publish — creator must accept the draft in TikTok app.

**Requires:** `access_token`

#### `publishToYoutube(array $account, array $post): array`
Calls YouTube Data API v3 `videos?part=snippet,status`. Creates a video metadata record.

**Requires:** `access_token`, optional `channel_id`

**Note:** Full video upload requires multipart upload — current implementation only sends metadata, not actual video binary.

#### `publishToFacebook(array $account, array $post): array`
Posts to Facebook Page feed via `/{pageId}/feed`.

**Requires:** `access_token` + `platform_user_id` (Page ID)

#### `publishToTwitter(array $account, array $post): array`
Posts a tweet via Twitter API v2 `/tweets`.

**Requires:** `access_token` (Bearer token)

**Character limit:** Text truncated to 277 chars + `…` if longer.

#### `getAnalytics(array $account, string $date): array`
Fetches engagement metrics for a social account.

**Returns:** `['followers' => int, 'impressions' => int, 'reach' => int, 'likes' => int, 'comments' => int, 'shares' => int, 'saves' => int, 'engagement_rate' => float]`

**Behavior:**
- If no token: returns seeded mock data based on `crc32(account_id + date)` for consistent dev output
- Instagram/Facebook: fetches `followers_count` from Graph API; other metrics computed from follower count heuristics
- YouTube: fetches `subscriberCount` and `viewCount` from Data API
- TikTok/Twitter: heuristic-only (no analytics API implemented)

#### `refreshToken(array $account): array`
Refreshes an expired OAuth token. Currently only implemented for YouTube (Google OAuth refresh flow). Other platforms return mock success.

#### `revokeAccess(array $account): bool`
Revokes OAuth tokens on platform side. Only YouTube has actual API revocation. Returns `true` for others.

---

## Architecture Note: Compat Delegation

The OOP methods (`publishToInstagram`, etc.) call procedural compat functions via `use function` imports (e.g., `social_api_service_publish_to_instagram`). This is a known architectural issue from the OOP migration — the actual implementation may live in the compat function. See `docs/code-quality/code-review.md` for details.

---

## Security Implications

| Risk | Notes |
|------|-------|
| Bearer tokens in HTTP headers | Transmitted over HTTPS only; ensure `SESSION_SECURE=true` in production |
| Mock mode in production | `SOCIAL_API_MOCK_FALLBACK=false` must be set in production |
| No token expiry check before publish | Should check `token_expires_at` before calling; implement auto-refresh |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Jobs/PublishPostJob.php` | Calls `publish()` for each platform |
| `src/Jobs/FetchAnalyticsJob.php` | Calls `getAnalytics()` |
| `src/Repositories/SocialAccountRepository.php` | Provides decrypted account rows |
| `backend/compat/services.php` | Compat implementations of individual platform methods |
| `.env` | `SOCIAL_API_MOCK_FALLBACK`, platform tokens |
