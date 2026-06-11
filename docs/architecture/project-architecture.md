# CreatorzHive — Project Architecture Report

> **Version:** 1.0 | **Date:** 2026-06-10 | **Author:** Architecture Audit

---

## 1. System Overview

CreatorzHive is a **server-rendered PHP web application** with a **vanilla JavaScript SPA-like frontend**. It is a single-tenant creator management platform targeting content creators and brands primarily in East Africa (default locale: Tanzania, currency TZS, timezone Africa/Dar_es_Salaam).

The system provides:
- Multi-platform social media post scheduling and publishing
- Unified analytics aggregated across platforms
- Brand deal CRM and Kanban pipeline
- Invoice generation for brand deals
- Media library for uploaded assets
- User authentication (email/password + Google OAuth)
- Social platform OAuth (Meta/Instagram/Facebook)
- Admin panel for user and platform management
- Asynchronous job queue for background tasks

### High-Level System Diagram

```
┌───────────────────────────────────────────────────────┐
│                     Browser (Client)                  │
│  HTML Pages + Vanilla JS (fetch API calls to backend) │
└───────────────────┬───────────────────────────────────┘
                    │ HTTP (GET / POST via ?route=…)
                    ▼
┌───────────────────────────────────────────────────────┐
│                  Apache / php -S                       │
│           Document Root: public/index.php             │
└───────────────────┬───────────────────────────────────┘
                    │
                    ▼
┌───────────────────────────────────────────────────────┐
│              backend/index.php (Bootstrap)            │
│  .env load → config → OOP boot → session → CSRF       │
│  → route registration → router_dispatch()             │
└───────────────────┬───────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        ▼                       ▼
  Web Routes                API Routes
  (HTML pages)            (JSON responses)
        │                       │
        └─────────┬─────────────┘
                  ▼
      Middleware Stack (auth/csrf/role)
                  │
                  ▼
        Controller (src/Controllers/)
                  │
         ┌────────┴────────┐
         ▼                 ▼
      Service           Repository
  (src/Services/)    (src/Repositories/)
         │                 │
         └────────┬────────┘
                  ▼
        MySQL 8 (PDO via Connection)
```

---

## 2. Technology Stack

| Layer | Technology | Version | Notes |
|-------|-----------|---------|-------|
| **Language** | PHP | ≥ 7.4 | OOP with PSR-4 autoloading |
| **Database** | MySQL | 8.0 | utf8mb4, PDO prepared statements |
| **Mail** | PHPMailer | ^6.8 | SMTP; email verification, resets |
| **Testing** | PHPUnit | ^9.6 | Unit + integration tests |
| **Frontend** | Vanilla JS | ES6+ | No framework, `fetch()` for API calls |
| **CSS** | Hand-written CSS | — | Per-feature stylesheets, dark mode |
| **Charts** | Chart.js | (self-hosted) | `frontend/assets/chart.js/` |
| **Fonts** | Inter, JetBrains Mono, Playfair Display | (self-hosted) | WOFF2 under `frontend/fonts/` |
| **HTTP Server** | Apache (mod_rewrite) or `php -S` | — | Entry via `public/` |
| **Dependency Manager** | Composer | — | `composer.json` |
| **CI** | GitHub Actions | — | `.github/workflows/ci.yml` |

### External API Dependencies

| Platform | API | Purpose |
|----------|-----|---------|
| Meta (Facebook/Instagram) | Graph API v20.0 | Publishing, analytics, OAuth |
| TikTok | Open Platform API v2 | Publishing (inbox video) |
| YouTube | Data API v3 | Publishing, analytics |
| Google | OAuth 2.0 | Sign-in with Google + YouTube refresh |
| X (Twitter) | API v2 | Publishing tweets |

---

## 3. Folder Structure

```
creatorzhive/
├── public/                    ← Web root (Apache/Nginx document root)
│   ├── index.php              ← Primary front controller
│   ├── .htaccess              ← Rewrite rules → index.php
│   ├── uploads/               ← User-uploaded media (publicly accessible)
│   └── assets/                ← (Empty; frontend assets served from /frontend/)
│
├── backend/                   ← PHP backend (NOT publicly accessible if deployed correctly)
│   ├── index.php              ← Main bootstrap/HTTP entry
│   ├── bootstrap-oop.php      ← Boots OOP Application + DI container
│   ├── bootstrap-procedural.php ← Loads all procedural helper includes
│   ├── bootstrap-web-view.php ← View-specific bootstrap
│   ├── routes/
│   │   ├── web.php            ← HTML page routes (GET)
│   │   └── api.php            ← JSON API routes (GET/POST)
│   ├── core/                  ← Procedural infrastructure functions
│   │   ├── router.php         ← router_register(), router_dispatch()
│   │   ├── session.php        ← Session helpers + fingerprinting
│   │   ├── database.php       ← db_query(), db_fetch_one(), etc.
│   │   ├── mailer.php         ← mailer_send() via PHPMailer
│   │   ├── request.php        ← request_ip(), request_input(), etc.
│   │   ├── response.php       ← response_json(), response_redirect()
│   │   ├── validator.php      ← validate_required(), validate_email(), etc.
│   │   ├── token_crypto.php   ← token_encrypt(), token_decrypt()
│   │   ├── error_handler.php  ← error_handler_register(), 404/500
│   │   └── job_runner.php     ← job_runner_run(), job_runner_dispatch()
│   ├── compat/                ← Legacy → OOP bridges
│   │   ├── auth.php           ← auth_service_* global functions
│   │   ├── models.php         ← model_user_*, model_post_* etc.
│   │   └── services.php       ← Social API, Meta OAuth, Analytics global wrappers
│   ├── config/
│   │   ├── app.php            ← APP_NAME, APP_URL, APP_ENV constants
│   │   └── database.php       ← DB_HOST, DB_DATABASE constants
│   ├── helpers/
│   │   ├── functions.php      ← load_env(), env(), base_path(), route_url(), etc.
│   │   ├── platforms.php      ← Platform list + icon helpers
│   │   ├── api_cors.php       ← api_cors_handle_preflight()
│   │   └── cli_bootstrap.php  ← CLI-specific bootstrap for scripts/
│   ├── middleware/            ← Procedural middleware (called by router)
│   │   ├── auth.php           ← auth_middleware_handle()
│   │   ├── csrf.php           ← csrf_generate_token(), csrf_validate_post()
│   │   └── role.php           ← role_middleware_require(), role_middleware_require_non_admin()
│   ├── http.php               ← HTTP-specific bootstrap additions
│   ├── storage/
│   │   ├── email-templates/   ← HTML email templates
│   │   ├── logs/              ← Error and mail logs (date-stamped)
│   │   ├── uploads/           ← Internal uploads (not publicly served)
│   │   └── cache/             ← Cron state files
│   └── README.md
│
├── src/                       ← OOP source (PSR-4: CreatorzHive\)
│   ├── Config/
│   │   └── AppConfig.php      ← Typed config accessors (wraps env/constants)
│   ├── Controllers/           ← HTTP request handlers (extend AbstractController)
│   │   ├── Support/AbstractController.php
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── PostController.php
│   │   ├── AnalyticsController.php
│   │   ├── DealController.php
│   │   ├── InvoiceController.php
│   │   ├── MediaController.php
│   │   ├── NotificationController.php
│   │   ├── SettingsController.php
│   │   ├── AdminUserController.php
│   │   ├── TagController.php
│   │   ├── OauthController.php
│   │   ├── GoogleAuthController.php
│   │   ├── ApiMetaController.php
│   │   └── SystemController.php
│   ├── Services/              ← Domain/business logic
│   │   ├── AuthService.php
│   │   ├── AuthRateLimitService.php
│   │   ├── GoogleAuthService.php
│   │   ├── MetaOAuthService.php
│   │   ├── SocialApiService.php
│   │   ├── AnalyticsService.php
│   │   ├── DashboardService.php
│   │   ├── NotificationService.php
│   │   ├── AdminService.php
│   │   ├── ApiMetaService.php
│   │   └── PlatformApiSecretsService.php
│   ├── Repositories/          ← SQL data access layer (PDO)
│   │   ├── UserRepository.php
│   │   ├── PostRepository.php
│   │   ├── MediaFileRepository.php
│   │   ├── TagRepository.php
│   │   ├── DealRepository.php
│   │   ├── InvoiceRepository.php
│   │   ├── AnalyticsRepository.php
│   │   ├── DashboardRepository.php
│   │   ├── NotificationRepository.php
│   │   ├── NotificationPreferenceRepository.php
│   │   ├── SocialAccountRepository.php
│   │   ├── UserPreferencesRepository.php
│   │   ├── UserSessionRepository.php
│   │   ├── AuditLogRepository.php
│   │   └── JobQueueRepository.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── Jobs/
│   │   ├── JobHandlerInterface.php
│   │   ├── PublishPostJob.php
│   │   ├── FetchAnalyticsJob.php
│   │   ├── CleanupMediaJob.php
│   │   └── SendNotificationJob.php
│   ├── Core/
│   │   ├── Application.php        ← Composition root; boots container
│   │   ├── Container.php          ← Simple DI container (service locator)
│   │   ├── Database/Connection.php ← PDO wrapper
│   │   ├── Http/JsonResponder.php
│   │   ├── Http/ViewRenderer.php
│   │   ├── Routing/Router.php
│   │   └── Security/TokenCrypto.php ← AES-256-CBC encryption
│   ├── Providers/
│   │   └── AppServiceProvider.php ← Wires all DI bindings
│   ├── Support/
│   │   ├── AnalyticsReportHelper.php
│   │   ├── DealWorkflowHelper.php
│   │   ├── MediaUploadHelper.php
│   │   ├── PostInputNormalizer.php
│   │   ├── SettingsPageHelper.php
│   │   └── UserPayloadFormatter.php
│   └── Helpers/
│       └── PlatformHelper.php
│
├── frontend/                  ← Static frontend assets
│   ├── index.html             ← App shell / landing redirect
│   ├── js/                    ← Per-feature vanilla JavaScript modules
│   ├── css/                   ← Per-feature stylesheets
│   ├── pages/                 ← PHP-rendered HTML templates + static HTML shells
│   ├── components/            ← Reusable HTML component snippets
│   ├── assets/                ← Self-hosted vendor JS (Chart.js)
│   └── fonts/                 ← Self-hosted web fonts
│
├── database/
│   ├── schema.sql             ← Full DDL (tables, views, triggers)
│   ├── migrations/            ← Incremental ALTER migrations
│   └── seeds/                 ← SQL seed data per feature area
│
├── scripts/                   ← CLI utilities
│   ├── migrate.php            ← Runs schema.sql against DB
│   ├── seed.php               ← Runs seed files
│   ├── cron.php               ← Job queue runner (runs every minute)
│   ├── encrypt-social-tokens.php ← Migrate legacy plaintext tokens
│   └── oop-*.php              ← One-time OOP migration automation scripts
│
├── tests/
│   ├── unit/                  ← PHPUnit unit tests
│   └── integration/           ← PHPUnit integration tests (hit real DB)
│
├── vendor/                    ← Composer dependencies (gitignored)
├── .env                       ← Environment config (gitignored)
├── .env.example               ← Environment template
├── composer.json
├── phpunit.xml.dist
└── dev-server-router.php      ← PHP built-in server router
```

---

## 4. Module Relationships

```
Application (Core/Application.php)
    └── Container (Core/Container.php)
            └── AppServiceProvider (registers all bindings)
                    ├── Controllers ──uses──► Services ──uses──► Repositories ──uses──► Connection
                    ├── Middleware ──runs before── Controllers
                    ├── Jobs ──dispatched via── JobQueueRepository ──run by── cron.php
                    └── Support Helpers ──used by── Controllers / Services
```

### Dependency Direction

```
Controller → Service → Repository → Connection (PDO → MySQL)
Controller → Support Helper
Controller → JobQueueRepository (for dispatching async jobs)
Service → Repository (for complex reads)
Job → Repository + Service (direct access during execution)
Middleware → UserRepository (auth check)
```

---

## 5. Data Flow

### Example: Create a Scheduled Post

```
1. Browser (planner.js)
   POST ?route=create_post  { title, content, caption, platforms, scheduled_at, media_ids }
        ↓
2. backend/index.php
   → Loads .env, config, Application::boot(), session, CSRF token
        ↓
3. router_dispatch()
   → Resolves route 'create_post'
   → Runs middleware: [auth] → [non_admin] → [csrf]
        ↓
4. PostController::store()
   → Reads $_POST via request_input()
   → Calls PostInputNormalizer::normalize() to sanitize & validate
   → Calls PostRepository::create() → INSERT INTO posts
   → Associates media via PostRepository::attachMedia()
   → If scheduled_at set: dispatches PublishPostJob via JobQueueRepository
   → Updates analytics row via AnalyticsRepository
   → Returns JsonResponder::success(['post_id' => …])
        ↓
5. cron.php (runs every minute)
   → job_runner_run('default', 10)
   → Finds pending PublishPostJob
   → PublishPostJob::handle()
        → PostRepository::findById()
        → SocialAccountRepository::findByUserAndPlatforms()
        → foreach platform: SocialApiService::publish()
              → Instagram: POST graph.facebook.com/v20.0/{id}/media
              → TikTok:    POST open.tiktokapis.com/v2/post/publish/inbox/video/init/
              → YouTube:   POST googleapis.com/youtube/v3/videos
              → Facebook:  POST graph.facebook.com/v20.0/{id}/feed
              → Twitter:   POST api.twitter.com/2/tweets
        → Writes to platform_post_results
        → Updates post.status = 'published'
        → NotificationService::createNotification('post_published')
```

---

## 6. Authentication Flow

### 6a. Email / Password Login

```
Browser → POST ?route=login {email, password, _csrf_token}
    ↓ backend/index.php → csrf_validate_post() → AuthController::login()
    ↓ UserRepository::findByEmail()
    ↓ AuthRateLimitService::check() (rate_limits table, token bucket)
    ↓ AuthService::checkPassword() → password_verify()
    ↓ If OK: session_regenerate_safe()
              session_set_user($user)   (stores user array + fingerprint hash)
    ↓ redirect → ?route=dashboard
```

### 6b. Google OAuth Login

```
Browser → GET ?route=google-auth
    ↓ GoogleAuthController::start()
       → GoogleAuthService::buildAuthUrl() → Google OAuth URL
    ↓ redirect → Google
    ↓ Google → GET ?route=google-callback?code=…
    ↓ GoogleAuthController::callback()
       → GoogleAuthService::exchangeCode() → Google access token
       → GoogleAuthService::getUserInfo() → email, name, google_id
       → UserRepository::findByGoogleId() or UserRepository::findByEmail()
       → If new user: UserRepository::create() (auto-trigger creates analytics row)
    ↓ session_set_user()
    ↓ redirect → ?route=dashboard
```

### 6c. Meta (Instagram/Facebook) Platform OAuth

```
Browser → GET ?route=oauth-connect?platform=instagram  (NOT app login)
    ↓ OauthController::connectStart()
       → MetaOAuthService::authorizeUrl() → Facebook OAuth dialog URL
    ↓ redirect → facebook.com/v20.0/dialog/oauth
    ↓ Facebook → GET ?route=oauth-callback?code=…&state=…
    ↓ OauthController::callbackHandler()
       → MetaOAuthService::completeConnection()
              → exchangeCode() → short-lived token
              → longLivedToken() → 60-day token
              → fetchPages() → list of FB Pages
              → saveFacebookPage() or saveInstagramAccount()
              → Upserts into social_accounts (token AES-256-CBC encrypted)
              → Dispatches FetchAnalyticsJob
```

### Session Fingerprinting

Every session stores `$_SESSION['_fingerprint']` = SHA-256 hash of (truncated IP subnet + lowercased User-Agent). On every authenticated request, `session_fingerprint_is_valid()` is called. Mismatch → session destroyed + redirect to login.

---

## 7. Request Lifecycle

```
HTTP Request arrives at public/index.php
    │
    ├── require backend/index.php
    │       ├── load_env('.env')
    │       ├── require config/app.php (defines constants)
    │       ├── require vendor/autoload.php (Composer PSR-4)
    │       ├── require bootstrap-oop.php
    │       │       └── Application::boot()
    │       │               └── AppServiceProvider::register() → Container
    │       ├── require bootstrap-procedural.php
    │       │       └── includes all backend/core/*.php, compat/*.php, middleware/*.php
    │       ├── error_handler_register()
    │       ├── session_start_safe()
    │       ├── csrf_generate_token()
    │       ├── router_reset()
    │       ├── api_cors_handle_preflight() (CORS preflight exit)
    │       ├── require routes/web.php
    │       ├── router_api_mode(true)
    │       ├── require routes/api.php
    │       └── router_dispatch()
    │               ├── router_resolve_route() → reads ?route=… or URI path
    │               ├── router_run_middleware([auth, csrf, non_admin, role:…])
    │               └── $controller->{$method}()
    │
    └── Controller executes → outputs JSON or renders PHP view template
```

---

## 8. API Flow (JSON)

All JSON APIs use the query parameter `?route=<key>`. There are no REST path segments.

**Request format:**
```
POST /?route=create_post
Content-Type: application/x-www-form-urlencoded
Cookie: PHPSESSID=…
Body: title=My+Post&content=…&_csrf_token=…
```

**Response format:**
```json
{
  "success": true,
  "message": "Post created.",
  "data": { "post_id": 42 }
}
```

**Error format:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "title": "Title is required." }
}
```

All responses use `JsonResponder::success()` or `JsonResponder::error()` which set `Content-Type: application/json` and the appropriate HTTP status code.

---

## 9. Integration Flow

### Publishing to Platforms

```
PostController::store()
    → creates post record (status: scheduled)
    → dispatches PublishPostJob to job_queue

cron.php (every minute)
    → job_runner_run('default', 10)
    → PublishPostJob::handle(payload)
        ↓
        SocialApiService::publish(account, post)
            ├── instagram → Graph API v20.0 two-step: /media → /media_publish
            ├── facebook  → Graph API v20.0 /{pageId}/feed POST
            ├── tiktok    → Open Platform v2 /post/publish/inbox/video/init/
            ├── youtube   → Data API v3 /videos?part=snippet,status
            └── twitter   → API v2 /tweets POST
        ↓
        platform_post_results INSERT
        posts.status = 'published'
        analytics UPDATE
        notifications CREATE
```

### Analytics Sync

```
cron.php (every 60 minutes)
    → FetchAnalyticsJob::handle(user_id, social_account_id)
        ↓
        SocialApiService::getAnalytics(account, date)
            ├── instagram/facebook → GET /{id}?fields=followers_count
            ├── youtube → GET /channels?part=statistics
            └── others → computed from follower_count heuristics
        ↓
        AnalyticsService::saveSnapshot() → analytics_snapshots INSERT/UPDATE
        AnalyticsService::updateRollingTotals() → analytics UPDATE
```

---

## 10. Deployment Architecture

```
Production (recommended)
─────────────────────────
Apache/Nginx
  DocumentRoot → /var/www/html/creatorzhive/public/
  public/.htaccess rewrites all requests → public/index.php
  public/index.php → require ../backend/index.php

Environment
  /var/www/html/creatorzhive/.env (never inside public/)
  APP_ENV=production
  APP_DEBUG=false
  SESSION_SECURE=true
  APP_SECRET=<32+ char random>

Cron (every minute)
  * * * * * php /var/www/html/creatorzhive/scripts/cron.php >> /tmp/ch-cron.log 2>&1

Development
─────────────
php -S 127.0.0.1:8080 -t . dev-server-router.php
# OR: composer serve
```
