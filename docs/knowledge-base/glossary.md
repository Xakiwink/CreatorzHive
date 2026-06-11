# CreatorzHive — Glossary

> A reference guide for all terms, acronyms, and concepts used in the CreatorzHive codebase and documentation.

---

## A

**AbstractController**
Base class at `src/Controllers/Support/AbstractController.php`. All controllers extend this. Provides shared constructor injection of `ViewRenderer`, `JsonResponder`, and `Connection`.

**AES-256-CBC**
Encryption algorithm used by `TokenCrypto` to encrypt OAuth access tokens before storing them in the database. Initialization vector (IV) is prepended to the ciphertext and stored base64-encoded with a `czenc1:` prefix.

**AnalyticsRepository**
Repository class responsible for reading/writing to the `analytics` and `analytics_snapshots` tables.

**AnalyticsService**
Service class that processes raw platform API data and saves analytics snapshots. Called by `FetchAnalyticsJob`.

**AppConfig**
Typed configuration class (`src/Config/AppConfig.php`) that wraps environment variables and constants into type-safe getter methods.

**AppServiceProvider**
The dependency injection wiring file (`src/Providers/AppServiceProvider.php`). Registers all services, repositories, controllers, and jobs into the DI container.

**Application**
The composition root class (`src/Core/Application.php`). Entry point for OOP bootstrap: creates Container, runs AppServiceProvider, stores globals.

**audit_logs**
Immutable database table recording all significant user and system actions with before/after JSON snapshots. Used for compliance and debugging.

---

## B

**bcrypt**
Password hashing algorithm used by PHP's `password_hash()`. CreatorzHive uses cost factor 12 for good security/performance balance.

**Bootstrap**
The initialization sequence that runs on every HTTP request. Includes: `.env` load → config → Composer autoload → OOP boot → procedural helpers → session → CSRF → route registration → dispatch.

**brand**
User role for brand/advertiser accounts. Brands can create deals and collaborate with creators.

---

## C

**caption**
Platform-specific text for a post (hashtags, mentions, etc.). Separate from `content` which is the main body text.

**CSRF (Cross-Site Request Forgery)**
Attack type where a malicious site makes authenticated requests on behalf of a user. Mitigated by CSRF tokens (`_csrf_token` field in all POST forms).

**Connection**
The PDO wrapper class at `src/Core/Database/Connection.php`. Provides typed methods: `query()`, `fetchOne()`, `fetchAll()`, `insert()`, `update()`, `delete()`, plus transaction support.

**Container**
The dependency injection container at `src/Core/Container.php`. A simple service locator supporting singleton (`set`) and factory (`factory`) registrations.

**cron.php**
CLI script at `scripts/cron.php` that runs every minute via system cron. Processes the job queue: publishes scheduled posts, syncs analytics, runs cleanup tasks.

**czenc1:**
Prefix added to encrypted tokens stored in the database. Presence of this prefix indicates the value is AES-256-CBC encrypted and must be decrypted before use.

---

## D

**deal**
A brand sponsorship or collaboration agreement. Tracked through a 6-stage pipeline: lead → negotiation → contract → active → completed → cancelled.

**deal_type**
Classification of a deal: `sponsored_post`, `affiliate`, `ambassador`, `gifted`, or `other`.

**deliverables**
The content or actions a creator promises to deliver as part of a deal (e.g., "3 Instagram posts, 2 Stories").

**DI Container**
Dependency Injection container. CreatorzHive uses a simple hand-rolled container. All objects are registered once and resolved on demand.

---

## E

**email_verifications**
Database table storing verification tokens sent to new users. Token expires in 24 hours.

**engagement_rate**
Metric: `(likes + comments + shares + saves) / impressions * 100`. Stored as a percentage in `analytics.avg_engagement_rate` and `analytics_snapshots.engagement_rate`.

---

## F

**FetchAnalyticsJob**
Async job that calls platform APIs to retrieve follower counts and engagement metrics, then saves them as `analytics_snapshots` rows.

**fingerprint**
Session security mechanism. A SHA-256 hash of the user's IP subnet and lowercase User-Agent string. Stored in `$_SESSION['_fingerprint']` and verified on every authenticated request.

**frontend_user_payload**
Normalized user data exposed to JavaScript as `window.__USER__`. Contains: id, name, username, email, role, avatar_url.

---

## G

**Google OAuth**
Authentication flow that allows users to sign in with their Google account. Uses `GoogleAuthController` and `GoogleAuthService`. Separate from YouTube API access.

**google_id**
The unique identifier from Google's OAuth system stored in `users.google_id`. Used to link Google accounts to CreatorzHive accounts.

---

## H

**hybrid architecture**
The current state of the codebase — a mix of PSR-4 OOP code (`src/`) and procedural code (`backend/core/`, `backend/compat/`). The OOP layer is primary; procedural code is compatibility bridges from the migration.

---

## I

**invoice_number**
Unique sequential invoice identifier (e.g., `INV-2026-001`). Stored in `invoices.invoice_number`.

**is_deleted**
Soft delete flag on `posts` and `deals` tables. Deleted records remain in the database but are filtered from all queries. Enables audit trail and recovery.

---

## J

**job_queue**
Database table storing asynchronous jobs (task records) for background processing by `cron.php`. Has queues: `default`, `analytics`, `cleanup`.

**JobHandlerInterface**
PHP interface at `src/Jobs/JobHandlerInterface.php`. All job classes implement `handle(array $payload): void`.

---

## K

**Kanban**
The deal management UI style — cards in columns representing pipeline stages. Stages: lead, negotiation, contract, active, completed, cancelled.

---

## L

**line_items**
JSON field in `invoices` table. Format: `[{"description": "Instagram posts x3", "qty": 3, "unit_price": 150000}]`. Used to calculate `subtotal`.

**long-lived token**
A Meta OAuth access token with ~60-day expiry (vs 1-hour short-lived tokens). Obtained by exchanging a short-lived token via the `fb_exchange_token` grant.

---

## M

**media_files**
Database table for all uploaded images and videos. Files stored in `public/uploads/YYYY/MM/{hash}.ext`.

**Meta**
Facebook's parent company. Provides the Graph API used for Instagram and Facebook integration.

**MetaOAuthService**
Service handling the Meta (Facebook/Instagram) OAuth 2.0 flow: authorization URL, code exchange, long-lived token, page fetching, and account saving.

**middleware**
PHP code that runs before a controller action. Current middleware: `auth` (session check), `csrf` (token validation), `non_admin` (blocks admin role), `role:admin` (requires admin role).

**MIME type**
Media file type identifier (e.g., `image/jpeg`, `video/mp4`). Used for upload validation.

**mock fallback**
When `SOCIAL_API_MOCK_FALLBACK=true`, failed or missing-token platform API calls return a simulated success response instead of failing. Used in development.

---

## N

**non_admin**
Middleware tag that blocks users with `role=admin` from accessing creator-specific routes. Ensures admins use the admin panel, not creator tools.

**notification_preferences**
Per-user table controlling which events trigger email notifications and push alerts.

---

## O

**OauthController**
Handles Meta platform OAuth (connect social accounts). NOT the login OAuth — that's GoogleAuthController.

**OOP migration**
The project was previously procedural PHP. The migration to PSR-4 OOP architecture is largely complete. `backend/compat/` contains bridges for backward compatibility.

---

## P

**password_resets**
Database table for password reset tokens. Supports both link-based (64-char hex token) and OTP-based (6-digit + random string) flows.

**PDO**
PHP Data Objects — the database extension used by `Connection`. All queries use prepared statements to prevent SQL injection.

**platform**
A social media network: `instagram`, `tiktok`, `youtube`, `twitter`, `facebook`. Used as ENUM in `social_accounts.platform`.

**platform_post_results**
Database table storing the outcome of publishing a post to each platform: success/failure, platform-assigned post ID, and URL.

**platforms** (JSON field)
The `posts.platforms` JSON column stores an array of target platform slugs: `["instagram", "tiktok"]`.

**PublishPostJob**
Async job that takes a scheduled post and publishes it to each target platform via `SocialApiService`.

---

## R

**rate_limits**
Database table implementing token-bucket rate limiting for login attempts. Key format: `ip:{ip_address}:login`.

**role**
User access level: `creator` (content creators), `brand` (advertisers), `admin` (system administrators).

**route**
A named action identifier passed as `?route=<name>` in the URL. e.g., `?route=dashboard`, `?route=create_post`.

---

## S

**session fingerprint**
See **fingerprint**.

**snapshot**
A point-in-time analytics record stored in `analytics_snapshots`. Captured daily, weekly, or monthly.

**social_accounts**
Database table for connected platform accounts. One row per platform per user. Tokens encrypted with AES-256-CBC.

**SocialApiService**
Service responsible for all social media API calls: publishing to platforms and fetching analytics data.

**soft delete**
Pattern where records are marked with `is_deleted=1` rather than physically removed from the database. Used on `posts` and `deals`.

---

## T

**tag**
A user-defined label for organizing posts. Has a name and hex color. Managed via `TagController` and `TagRepository`.

**token bucket**
Rate limiting algorithm where tokens refill at a fixed rate. If bucket is empty, the request is rejected. Used by `AuthRateLimitService`.

**TokenCrypto**
Encryption utility at `src/Core/Security/TokenCrypto.php`. Provides AES-256-CBC encryption for OAuth tokens stored in the database.

**trigger (DB)**
`trg_after_user_insert` — MySQL trigger that auto-creates companion rows in `analytics`, `notification_preferences`, and `user_preferences` when a new user registers.

**TZS**
Tanzanian Shilling — the default currency in `deals.currency`, `invoices.currency`, and `user_preferences.default_currency`.

---

## U

**user_preferences**
Per-user settings table: theme, language, currency, date format, sidebar state.

**UserPayloadFormatter**
Support class that normalizes a user array for frontend consumption: ensures id is int, avatar_url is a full URL, role is set.

---

## V

**v_creator_summary**
MySQL view providing a summary row per active user for the dashboard: post counts, followers, revenue, active deals, unread notifications.

**v_deal_revenue**
MySQL view aggregating deal revenue per user per currency.

**v_post_performance**
MySQL view showing published post success rates across platforms.

**v_upcoming_posts**
MySQL view showing scheduled posts in the next 14 days.

**ViewRenderer**
Class at `src/Core/Http/ViewRenderer.php`. Responsible for rendering PHP template files from `frontend/pages/`.

---

## W

**web.php / api.php**
The two route definition files. `web.php` defines HTML page routes (GET only). `api.php` defines JSON data routes (GET and POST).

**window.__USER__**
JavaScript global set by PHP templates containing the current user's normalized profile data.

**window.__CSRF__**
JavaScript global set by PHP templates containing the current CSRF token. Included in all fetch() POST requests.
