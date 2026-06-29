# `src/Services/` — Business Logic

## 1. Folder Purpose

Encapsulates rules that should not live in controllers or SQL: authentication, OAuth, notifications, social APIs, admin settings, analytics aggregation.

## 2. Files Overview

| File | Purpose |
|------|---------|
| `AuthService.php` | Password hash/verify, verification tokens |
| `AuthRateLimitService.php` | Login throttling |
| `GoogleAuthService.php` | Google OAuth2 URLs, token exchange, userinfo |
| `InstagramOAuthService.php` | Instagram Business Login (Meta Graph API v25) |
| `SocialApiService.php` | Publish posts, fetch analytics per platform |
| `PlatformApiSecretsService.php` | Admin-stored API credentials |
| `NotificationService.php` | Create in-app notifications |
| `AdminService.php` | Site settings key/value |
| `DashboardService.php` | Dashboard aggregates |
| `AnalyticsService.php` | Analytics business logic |
| `ApiMetaService.php` | API route catalog for frontend |

## 3. `GoogleAuthService` — key methods

| Method | Purpose |
|--------|---------|
| `isConfigured()` | Client ID + secret present |
| `redirectUri()` | Callback URL for Google Console |
| `authorizeUrl($state)` | Build Google consent URL |
| `exchangeCode($code)` | Authorization code → access token |
| `fetchUserProfile($token)` | OpenID userinfo → `google_id`, email |

**Env**: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, optional `GOOGLE_AUTH_REDIRECT_URI`.

## 4. Improvement suggestions

- Interface + mock implementation for `SocialApiService` in tests.
- Centralize HTTP client (cURL) to reduce duplication across OAuth services.
