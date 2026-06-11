# CreatorzHive — Integration Map

> Documents every external system integration: setup, flow, and dependencies.

---

## Overview

| Integration | Purpose | Type | Status |
|-------------|---------|------|--------|
| Meta (Facebook) | Platform connection + publishing | OAuth 2.0 + Graph API | Implemented |
| Meta (Instagram) | Platform connection + publishing | OAuth 2.0 + Graph API | Implemented |
| TikTok | Publishing | Access token | Implemented (partial) |
| YouTube | Publishing + analytics | Access token + OAuth refresh | Implemented |
| X (Twitter) | Publishing | Bearer token | Implemented |
| Google Sign-In | User authentication | OAuth 2.0 | Implemented |
| PHPMailer / SMTP | Email notifications | SMTP | Implemented |

---

## 1. Meta (Facebook + Instagram) Integration

### 1.1 Prerequisites
1. Create app at developers.facebook.com
2. Add products: **Facebook Login** and **Instagram Basic Display** (or **Instagram Graph API**)
3. Set Valid OAuth Redirect URI: `{APP_URL}/?route=oauth-callback`
4. Configure app in Admin → Platform Credentials

### 1.2 Environment Variables
```
META_APP_ID=123456789
META_APP_SECRET=abcdef1234567890abcdef1234567890
META_OAUTH_REDIRECT_URI=https://yourapp.com/?route=oauth-callback
```

### 1.3 Code Locations
| Component | File |
|-----------|------|
| OAuth flow start | `src/Controllers/OauthController.php::connectStart()` |
| OAuth callback | `src/Controllers/OauthController.php::callbackHandler()` |
| Token exchange | `src/Services/MetaOAuthService.php::exchangeCode()` |
| Long-lived token | `src/Services/MetaOAuthService.php::longLivedToken()` |
| Fetch pages | `src/Services/MetaOAuthService.php::fetchPages()` |
| Save FB account | `src/Services/MetaOAuthService.php::saveFacebookPage()` |
| Save IG account | `src/Services/MetaOAuthService.php::saveInstagramAccount()` |
| Publish to Facebook | `src/Services/SocialApiService.php::publishToFacebook()` |
| Publish to Instagram | `src/Services/SocialApiService.php::publishToInstagram()` |
| Get analytics | `src/Services/SocialApiService.php::getAnalytics()` |
| Token storage | `src/Repositories/SocialAccountRepository.php` (AES encrypted) |
| DB table | `social_accounts` (platform: 'facebook' or 'instagram') |

### 1.4 API Endpoints Used
| Method | URL | Purpose |
|--------|-----|---------|
| GET | `facebook.com/v20.0/dialog/oauth` | Authorization dialog |
| GET | `graph.facebook.com/v20.0/oauth/access_token` | Code exchange + token refresh |
| GET | `graph.facebook.com/v20.0/me/accounts` | Fetch Pages + linked IG |
| POST | `graph.facebook.com/v20.0/{id}/media` | Create Instagram media container |
| POST | `graph.facebook.com/v20.0/{id}/media_publish` | Publish IG container |
| POST | `graph.facebook.com/v20.0/{id}/feed` | Publish Facebook post |
| GET | `graph.facebook.com/v20.0/{id}?fields=followers_count` | Analytics |

### 1.5 Required Scopes
**Instagram:** `instagram_basic`, `instagram_content_publish`, `business_management`, `pages_show_list`, `pages_read_engagement`

**Facebook:** `pages_manage_posts`, `pages_read_engagement`, `pages_show_list`, `pages_read_engagement`

### 1.6 Token Lifecycle
- Short-lived token: ~1 hour (from code exchange)
- Long-lived token: ~60 days (from `fb_exchange_token` exchange)
- Stored encrypted in `social_accounts.access_token` with `czenc1:` prefix
- `token_expires_at` set to `+55 days` from connection time

### 1.7 Testing Without Real Meta App
Set `SOCIAL_API_MOCK_FALLBACK=true` (already default). All API calls return mock success responses.

---

## 2. TikTok Integration

### 2.1 Prerequisites
1. Create app at developers.tiktok.com
2. Enable **Content Posting API** product
3. Get access token for creator account

### 2.2 Environment Variables
```
TIKTOK_ACCESS_TOKEN=your_access_token_here
TIKTOK_PRIVACY_LEVEL=SELF_ONLY
```

### 2.3 Code Locations
| Component | File |
|-----------|------|
| Publish | `src/Services/SocialApiService.php::publishToTiktok()` |
| Analytics | `src/Services/SocialApiService.php::getAnalytics()` (heuristic) |

### 2.4 Current Implementation Notes
- Uses TikTok Open Platform v2 `post/publish/inbox/video/init/` endpoint
- This creates a draft in the creator's TikTok inbox (not a direct publish)
- Full video upload support requires additional implementation
- No TikTok OAuth flow (token must be manually set in environment or admin panel)

### 2.5 Privacy Levels
| Level | Description |
|-------|-------------|
| `SELF_ONLY` | Only creator can see (default for testing) |
| `MUTUAL_FOLLOW_FRIENDS` | Friends only |
| `FOLLOWER_OF_CREATOR` | Followers only |
| `PUBLIC_TO_EVERYONE` | Public |

---

## 3. YouTube Integration

### 3.1 Prerequisites
1. Create project at console.cloud.google.com
2. Enable **YouTube Data API v3**
3. Create **OAuth 2.0 Web client** credentials
4. Set redirect URI: `{APP_URL}/?route=google-callback` (shared with Google Sign-In)

### 3.2 Environment Variables
```
YOUTUBE_ACCESS_TOKEN=your_access_token
YOUTUBE_CHANNEL_ID=UCxxxxxxxxxxxxxxxxxxxxxxxx
YOUTUBE_PRIVACY_STATUS=private
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret
```

### 3.3 Code Locations
| Component | File |
|-----------|------|
| Publish | `src/Services/SocialApiService.php::publishToYoutube()` |
| Analytics | `src/Services/SocialApiService.php::getAnalytics()` |
| Token refresh | `src/Services/SocialApiService.php::refreshToken()` |
| Token revoke | `src/Services/SocialApiService.php::revokeAccess()` |

### 3.4 API Endpoints Used
| Method | URL | Purpose |
|--------|-----|---------|
| POST | `googleapis.com/youtube/v3/videos?part=snippet,status` | Upload video |
| GET | `googleapis.com/youtube/v3/channels?part=statistics&id={id}` | Channel stats |
| POST | `oauth2.googleapis.com/token` | Refresh access token |
| POST | `oauth2.googleapis.com/revoke?token={token}` | Revoke token |

### 3.5 Token Refresh Flow
YouTube tokens expire in 1 hour. `SocialApiService::refreshToken()` uses the stored `refresh_token` with `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` to get a new access token. Called automatically when publish fails with 401.

---

## 4. X (Twitter) Integration

### 4.1 Prerequisites
1. Create app at developer.twitter.com
2. Enable **OAuth 2.0** with read/write scopes
3. Get Bearer Token

### 4.2 Environment Variables
```
TWITTER_BEARER_TOKEN=AAAAAAAAAAAAAAAAAAAAAxxxxx...
```

### 4.3 Code Locations
| Component | File |
|-----------|------|
| Publish tweet | `src/Services/SocialApiService.php::publishToTwitter()` |

### 4.4 Limitations
- Text limited to 280 characters (auto-truncated with `…`)
- No image/video upload implemented
- No analytics API integrated (no Twitter analytics endpoint in current code)
- No OAuth flow (manual token entry)

---

## 5. Google Sign-In Integration

### 5.1 Purpose
Allow users to register and login with their Google account. Separate from YouTube API.

### 5.2 Environment Variables
```
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret
# GOOGLE_AUTH_REDIRECT_URI=https://yourapp.com/?route=google-callback  # optional
```

### 5.3 Code Locations
| Component | File |
|-----------|------|
| Start OAuth | `src/Controllers/GoogleAuthController.php::start()` |
| Handle callback | `src/Controllers/GoogleAuthController.php::callback()` |
| Service logic | `src/Services/GoogleAuthService.php` |

### 5.4 Flow
1. `?route=google-auth` → build Google auth URL (scope: `openid email profile`)
2. Google redirects to `?route=google-callback?code=…`
3. Exchange code for ID token + access token
4. Decode ID token → extract `sub`, `email`, `name`, `picture`
5. Find or create user in `users` table
6. Set PHP session

### 5.5 Show Google Sign-In Button
The button is displayed only when `google_auth_is_configured()` returns true (checks `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`).

---

## 6. Email / PHPMailer Integration

### 6.1 Purpose
Send transactional emails: email verification, password resets, notification emails.

### 6.2 Environment Variables
```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io         # or smtp.gmail.com, ses.amazonaws.com, etc.
MAIL_PORT=2525                     # 587 for Gmail, 465 for SSL
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS=noreply@creatorzhive.com
MAIL_FROM_NAME=CreatorzHive
```

### 6.3 Code Locations
| Component | File |
|-----------|------|
| Send function | `backend/core/mailer.php::mailer_send()` |
| Email templates | `backend/storage/email-templates/` |

### 6.4 Email Templates
| Template | Used For |
|----------|---------|
| `verify-email.html` | New user email verification |
| `reset-password.html` | Password reset link/OTP |
| `notification-generic.html` | Generic notification email |

### 6.5 Testing
Use [Mailtrap](https://mailtrap.io) (free tier) to capture emails during development without sending real emails. Set `MAIL_HOST=smtp.mailtrap.io` and use Mailtrap credentials.

---

## 7. Admin-Managed API Credentials

### 7.1 Purpose
Admins can configure API credentials through the UI (Admin → Platform Credentials) instead of requiring server `.env` changes. Credentials are stored encrypted in the database.

### 7.2 Service
`src/Services/PlatformApiSecretsService.php`

### 7.3 Storage
Database-backed key-value store. Keys include:
- `meta_app_id`, `meta_app_secret`
- `instagram_business_id`
- `facebook_page_id`
- `tiktok_access_token`, `tiktok_privacy_level`
- `youtube_access_token`, `youtube_channel_id`, `youtube_privacy_status`
- `twitter_bearer_token`

### 7.4 Priority Resolution
`platform_api_secrets_resolve($key)` checks:
1. Admin-configured DB credential (encrypted)
2. `.env` variable

This allows `.env` to be the fallback and DB to override per-user or per-installation.

---

## 8. Integration Testing

### 8.1 Admin Test Endpoint
`GET ?route=admin_test_integration` — `AdminUserController::integrationTest()`

Tests connectivity to configured integrations and returns health status.

### 8.2 Mock Mode
Set `SOCIAL_API_MOCK_FALLBACK=true` to run the full publish workflow without real API credentials. All platform calls return: `{ success: true, platform_post_id: "mock_xxx", platform_url: null }`.

### 8.3 Development Setup
The recommended development setup:
```env
SOCIAL_API_MOCK_FALLBACK=true
MAIL_HOST=smtp.mailtrap.io
APP_ENV=development
APP_DEBUG=true
```
