# CreatorzHive — API Analysis

> **Version:** 1.0 | **Date:** 2026-06-10

---

## Part 1 — Internal HTTP API (CreatorzHive Backend)

All routes use a front-controller pattern: `GET /?route=<name>` for reads, `POST /?route=<name>` for writes. There are no REST path segments. Authentication is via PHP session cookie. CSRF token required on all POST routes.

### Authentication Endpoints

| Route | Method | Middleware | Controller::Method | Description |
|-------|--------|-----------|-------------------|-------------|
| `check_username` | GET | — | AuthController::checkUsername | Check username availability |
| `verify` | GET | — | AuthController::verify | Email verification page/action |
| `register` | POST | csrf | AuthController::register | Create new account |
| `login` | POST | csrf | AuthController::login | Login with email/password |
| `logout` | POST | auth, csrf | AuthController::logout | Destroy session |
| `forgot-password` | POST | csrf | AuthController::forgotPassword | Send reset email |
| `reset-password` | POST | csrf | AuthController::resetPassword | Apply new password |
| `resend-verification` | POST | csrf | AuthController::resendVerification | Re-send email verification |
| `google-auth` | GET | — | GoogleAuthController::start | Redirect to Google OAuth |
| `google-callback` | GET | — | GoogleAuthController::callback | Handle Google OAuth return |
| `oauth-connect` | GET | auth | OauthController::connectStart | Begin Meta platform OAuth |
| `oauth-callback` | GET | — | OauthController::callbackHandler | Handle Meta OAuth return |

### Post / Content Endpoints

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `posts` | GET | auth, non_admin | List posts (paginated, filterable) |
| `posts_calendar` | GET | auth, non_admin | Posts in calendar format |
| `post` | GET | auth, non_admin | Get single post by `?id=` |
| `create_post` | POST | auth, non_admin, csrf | Create new post |
| `update_post` | POST | auth, non_admin, csrf | Update existing post |
| `delete_post` | POST | auth, non_admin, csrf | Soft-delete post |
| `duplicate_post` | POST | auth, non_admin, csrf | Clone a post |
| `bulk_posts` | POST | auth, non_admin, csrf | Bulk status update |

### Media Endpoints

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `upload_media` | POST | auth, non_admin, csrf | Upload image/video file |
| `media_list` | GET | auth, non_admin | List user's media files |
| `delete_media` | POST | auth, non_admin, csrf | Delete media file |

### Analytics Endpoints

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `analytics_data` | GET | auth, non_admin | Get analytics charts/snapshots |
| `seed_analytics` | POST | auth, non_admin, csrf | Seed demo analytics data |

### Deals & Invoices

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `deals_data` | GET | auth, non_admin | List deals (Kanban data) |
| `deal` | GET | auth, non_admin | Get single deal |
| `create_deal` | POST | auth, non_admin, csrf | Create deal |
| `update_deal` | POST | auth, non_admin, csrf | Update deal details |
| `update_deal_status` | POST | auth, non_admin, csrf | Move deal to new Kanban stage |
| `delete_deal` | POST | auth, non_admin, csrf | Soft-delete deal |
| `invoices_data` | GET | auth, non_admin | List invoices |
| `invoice` | GET | auth, non_admin | Get single invoice |
| `create_invoice` | POST | auth, non_admin, csrf | Create invoice |
| `update_invoice` | POST | auth, non_admin, csrf | Update invoice |
| `mark_invoice_paid` | POST | auth, non_admin, csrf | Mark invoice as paid |

### Notifications

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `notifications_data` | GET | auth | All notifications (paginated) |
| `notifications_count` | GET | auth | Unread count (badge) |
| `mark_read` | POST | auth, csrf | Mark notification as read |
| `mark_all_read` | POST | auth, csrf | Mark all as read |
| `delete_notification` | POST | auth, csrf | Delete notification |
| `delete_read_notifications` | POST | auth, csrf | Bulk delete read |

### Settings

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `profile_data` | GET | auth | Get current user profile |
| `update_profile` | POST | auth, csrf | Update name, bio, username, avatar |
| `update_password` | POST | auth, csrf | Change password |
| `user_sessions` | GET | auth | List active sessions |
| `revoke_session` | POST | auth, csrf | Revoke specific session |
| `revoke_all_sessions` | POST | auth, csrf | Revoke all other sessions |
| `integrations_data` | GET | auth | List connected platforms |
| `connect_platform` | POST | auth, csrf | Initiate platform connection |
| `disconnect_platform` | POST | auth, csrf | Revoke platform access |
| `notification_prefs` | GET | auth | Get notification preferences |
| `update_notification_prefs` | POST | auth, csrf | Update notification preferences |
| `update_preferences` | POST | auth, csrf | Update UI preferences |

### Admin Endpoints

| Route | Method | Middleware | Description |
|-------|--------|-----------|-------------|
| `admin_users` | GET | auth, role:admin | List all users |
| `admin_create_user` | POST | auth, role:admin, csrf | Create user (admin) |
| `admin_update_user` | POST | auth, role:admin, csrf | Update user |
| `admin_delete_user` | POST | auth, role:admin, csrf | Delete user |
| `admin_verify_user` | POST | auth, role:admin, csrf | Force-verify user email |
| `admin_overview` | GET | auth, role:admin | Platform-wide stats |
| `admin_update_settings` | POST | auth, role:admin, csrf | Update admin settings |
| `admin_audit_logs` | GET | auth, role:admin | View audit log |
| `admin_test_integration` | GET | auth, role:admin | Test API connectivity |
| `admin_platform_credentials` | GET | auth, role:admin | View stored API credentials |
| `admin_update_platform_credentials` | POST | auth, role:admin, csrf | Update API credentials |

### System / Meta

| Route | Method | Description |
|-------|--------|-------------|
| `ping` | GET | Health check |
| `db-test` | GET | Database connectivity (admin) |
| `api_me` | GET | Current user info + CSRF token |
| `api_catalog` | GET | Available API routes catalog |

---

## Part 2 — External Social Media APIs

### 2.1 Meta Graph API (Facebook & Instagram)

**Base URL:** `https://graph.facebook.com/v20.0/`

**Authentication:** Bearer token (OAuth 2.0 access token, stored encrypted in `social_accounts.access_token`)

**Purpose:** Publish posts to Facebook Pages and Instagram Business accounts; fetch follower analytics.

**OAuth Flow:**
1. `GET https://www.facebook.com/v20.0/dialog/oauth?client_id={APP_ID}&redirect_uri={URI}&scope={SCOPES}&response_type=code`
2. User grants permission → Facebook redirects to `?route=oauth-callback?code=…`
3. `GET https://graph.facebook.com/v20.0/oauth/access_token?client_id={}&client_secret={}&redirect_uri={}&code={}` → short-lived token
4. `GET https://graph.facebook.com/v20.0/oauth/access_token?grant_type=fb_exchange_token&client_id={}&client_secret={}&fb_exchange_token={}` → 60-day long-lived token
5. `GET https://graph.facebook.com/v20.0/me/accounts?fields=id,name,username,access_token,instagram_business_account{id,username,name}&access_token={}` → list of Pages + linked IG accounts

**Instagram Publishing Endpoints:**

```
POST https://graph.facebook.com/v20.0/{businessId}/media
Body: { "image_url": "...", "caption": "..." }
Response: { "id": "container_id" }

POST https://graph.facebook.com/v20.0/{businessId}/media_publish
Body: { "creation_id": "container_id" }
Response: { "id": "post_id" }
```

**Required Scopes (Instagram):** `instagram_basic`, `instagram_content_publish`, `business_management`, `pages_show_list`, `pages_read_engagement`

**Facebook Publishing Endpoint:**

```
POST https://graph.facebook.com/v20.0/{pageId}/feed
Body: { "message": "..." }
Response: { "id": "page_postId" }
```

**Required Scopes (Facebook):** `pages_manage_posts`, `pages_read_engagement`, `pages_show_list`, `pages_read_engagement`

**Analytics Endpoint:**

```
GET https://graph.facebook.com/v20.0/{id}?fields=followers_count&access_token={token}
Response: { "followers_count": 12500, "id": "..." }
```

**Rate Limits:** Meta enforces per-app and per-user rate limits. Standard rate: 200 calls/hour per token. Publishing rate: varies by account level.

**Error Handling:** `SocialApiService::httpRequest()` returns `['ok' => false, 'error' => 'HTTP {code}']` on non-2xx. Upstream caller falls back to mock if `SOCIAL_API_MOCK_FALLBACK=true`.

**Configuration:**
- `META_APP_ID` — from Meta Developer Console
- `META_APP_SECRET` — from Meta Developer Console
- `META_OAUTH_REDIRECT_URI` — must match Facebook app settings
- Also configurable via Admin → Platform Credentials (encrypted storage)

---

### 2.2 TikTok Open Platform API

**Base URL:** `https://open.tiktokapis.com/v2/`

**Authentication:** Bearer token (OAuth access token)

**Purpose:** Publish video posts to TikTok creator accounts.

**Publishing Endpoint:**

```
POST https://open.tiktokapis.com/v2/post/publish/inbox/video/init/
Headers: Authorization: Bearer {token}
Body:
{
  "post_info": {
    "title": "...",
    "description": "...",
    "privacy_level": "SELF_ONLY",
    "disable_comment": false,
    "disable_duet": false,
    "disable_stitch": false
  }
}
Response: { "data": { "publish_id": "..." }, "error": { "code": "ok" } }
```

**Note:** The current implementation uses the "inbox video" endpoint (draft to inbox), not direct publish. Full publish requires additional video upload steps.

**Configuration:**
- `TIKTOK_ACCESS_TOKEN` — from TikTok Developer app
- `TIKTOK_PRIVACY_LEVEL` — `SELF_ONLY`, `MUTUAL_FOLLOW_FRIENDS`, `FOLLOWER_OF_CREATOR`, `PUBLIC_TO_EVERYONE`

**Rate Limits:** TikTok: 100 requests/day for content publishing on standard tier.

---

### 2.3 YouTube Data API v3

**Base URL:** `https://www.googleapis.com/youtube/v3/`

**Authentication:** OAuth 2.0 Bearer token (with refresh via Google OAuth)

**Purpose:** Upload/publish videos to YouTube channels; fetch subscriber analytics.

**Publishing Endpoint:**

```
POST https://www.googleapis.com/youtube/v3/videos?part=snippet,status
Headers: Authorization: Bearer {token}
Body:
{
  "snippet": {
    "title": "...",
    "description": "..."
  },
  "status": {
    "privacyStatus": "private"
  }
}
Response: { "id": "videoId", "snippet": {...}, "status": {...} }
```

**Analytics Endpoint:**

```
GET https://www.googleapis.com/youtube/v3/channels?part=statistics&id={channelId}
Headers: Authorization: Bearer {token}
Response: {
  "items": [{
    "statistics": {
      "subscriberCount": "50000",
      "viewCount": "1000000"
    }
  }]
}
```

**Token Refresh:**

```
POST https://oauth2.googleapis.com/token
Body: {
  "client_id": "...",
  "client_secret": "...",
  "refresh_token": "...",
  "grant_type": "refresh_token"
}
Response: { "access_token": "...", "expires_in": 3600 }
```

**Token Revoke:**

```
POST https://oauth2.googleapis.com/revoke?token={token}
```

**Configuration:**
- `YOUTUBE_ACCESS_TOKEN` — long-lived access token
- `YOUTUBE_CHANNEL_ID` — creator's channel ID
- `YOUTUBE_PRIVACY_STATUS` — `private`, `public`, `unlisted`
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — for token refresh

---

### 2.4 Google OAuth 2.0 (Sign-in with Google)

**Authorization URL:** `https://accounts.google.com/o/oauth2/v2/auth`

**Purpose:** Enable "Sign in with Google" authentication. Separate from YouTube OAuth.

**Flow (handled by `GoogleAuthService.php`):**
1. Build authorization URL with scopes: `openid email profile`
2. User authenticates with Google
3. Google redirects to `?route=google-callback?code=…`
4. Exchange code for ID token + access token via `GoogleAuthService::exchangeCode()`
5. Decode ID token → extract `sub` (google_id), `email`, `name`, `picture`
6. Create or find user in `users` table by `google_id` or `email`
7. Set PHP session

**Configuration:**
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_AUTH_REDIRECT_URI` (optional, defaults to `{APP_URL}/?route=google-callback`)

---

### 2.5 X (Twitter) API v2

**Base URL:** `https://api.twitter.com/2/`

**Authentication:** Bearer token

**Purpose:** Publish tweets.

**Publishing Endpoint:**

```
POST https://api.twitter.com/2/tweets
Headers: Authorization: Bearer {token}
Body: { "text": "..." }
Response: { "data": { "id": "...", "text": "..." } }
```

**Constraints:** Max 280 characters. Text is truncated with `…` if longer.

**Configuration:**
- `TWITTER_BEARER_TOKEN`

---

## Part 3 — API Security Model

### CSRF Protection
All POST routes require a valid CSRF token in `_csrf_token` POST parameter. Token is generated per session by `csrf_generate_token()` and stored in `$_SESSION['_csrf_token']`. `csrf_validate_post()` compares using `hash_equals()` (timing-safe).

### Rate Limiting (Login)
`AuthRateLimitService` uses a token-bucket algorithm backed by the `rate_limits` MySQL table. Key format: `ip:{client_ip}:login`. No Redis dependency required.

### Token Encryption
Platform access tokens stored in `social_accounts.access_token` are AES-256-CBC encrypted using `TokenCrypto::encryptDb()`. Encrypted value has prefix `czenc1:`. Decrypted only at usage time via `SocialAccountRepository`.

### Mock Fallback
When `SOCIAL_API_MOCK_FALLBACK=true` (default in dev), any platform API call that fails due to missing tokens returns a mock success response with `platform_post_id: mock_XXXXXX`. This prevents hard failures during development.
