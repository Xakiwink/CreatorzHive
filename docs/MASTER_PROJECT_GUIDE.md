# CreatorzHive — Master Project Guide

> **Version:** 1.0 | **Date:** 2026-06-10 | **Status:** Complete System Documentation
>
> This is the **definitive handbook** for the CreatorzHive platform. A new developer can read this document and understand the entire system without reading source code.

---

## Table of Contents

1. [What Is CreatorzHive?](#1-what-is-creatorzhive)
2. [Technology Stack](#2-technology-stack)
3. [How to Run Locally](#3-how-to-run-locally)
4. [System Architecture](#4-system-architecture)
5. [Database Structure](#5-database-structure)
6. [Request Lifecycle](#6-request-lifecycle)
7. [Authentication System](#7-authentication-system)
8. [Feature Reference](#8-feature-reference)
9. [API Reference](#9-api-reference)
10. [Social Media Integrations](#10-social-media-integrations)
11. [Background Jobs](#11-background-jobs)
12. [Security Model](#12-security-model)
13. [Folder Structure Reference](#13-folder-structure-reference)
14. [Key Classes Reference](#14-key-classes-reference)
15. [Development Guidelines](#15-development-guidelines)
16. [Known Issues & Technical Debt](#16-known-issues--technical-debt)
17. [Where to Find Things](#17-where-to-find-things)
18. [Documentation Index](#18-documentation-index)

---

## 1. What Is CreatorzHive?

CreatorzHive is a **centralized content creator management platform** built for influencers, content creators, and brands — primarily targeting the East African market (Tanzania, Kenya, Uganda).

### Core Problem Solved
Creators manage 5+ social media platforms with separate apps, no unified analytics, informal brand deal tracking, and manual invoicing. CreatorzHive centralizes all of this.

### What Creators Can Do
- **Schedule posts** across Instagram, TikTok, YouTube, Facebook, and Twitter from one place
- **Track analytics** — follower counts, engagement rates, impressions — in unified charts
- **Manage brand deals** through a Kanban pipeline (lead → completed)
- **Issue invoices** for brand sponsorships with TZS currency support
- **Store media** in a centralized library
- **Receive notifications** for published posts, deal updates, invoice payments

### User Roles
| Role | Description | Access |
|------|-------------|--------|
| `creator` | Content creator | All creator features |
| `brand` | Advertiser/brand | Deal creation (limited) |
| `admin` | Platform administrator | Full access + admin panel |

---

## 2. Technology Stack

| Component | Technology |
|-----------|-----------|
| **Backend Language** | PHP 7.4+ (strict types, PSR-4 OOP) |
| **Database** | MySQL 8.0 (utf8mb4, InnoDB, PDO prepared statements) |
| **Email** | PHPMailer 6.8 (SMTP) |
| **Testing** | PHPUnit 9.6 |
| **Frontend** | Vanilla HTML + CSS + JavaScript (ES6+, no framework) |
| **Charts** | Chart.js (self-hosted) |
| **Fonts** | Inter, JetBrains Mono, Playfair Display (self-hosted) |
| **Web Server** | Apache (mod_rewrite) or `php -S` dev server |

**No npm, no Webpack, no React. Zero external CDN calls.**

---

## 3. How to Run Locally

```bash
# 1. Clone
git clone <repo-url> /var/www/html/creatorzhive
cd /var/www/html/creatorzhive

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env: DB credentials, MAIL settings, APP_URL

# 4. Create database
mysql -u root -p -e "CREATE DATABASE creatorz_hive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Run migrations
php scripts/migrate.php

# 6. (Optional) Seed demo data
php scripts/seed.php

# 7. Start server
composer serve  # or: php -S 127.0.0.1:8080 -t . dev-server-router.php

# 8. Open browser
# http://127.0.0.1:8080/?route=login
```

**Default dev `.env` settings that matter:**
- `SOCIAL_API_MOCK_FALLBACK=true` — no real platform API calls needed
- `APP_DEBUG=true` — shows errors
- `SESSION_SECURE=false` — HTTP ok for dev
- `APP_SECRET` — set to anything non-empty to enable token encryption

---

## 4. System Architecture

### Architecture Type
**Hybrid PHP Application**: PSR-4 OOP layer (`src/`) + procedural compatibility bridges (`backend/compat/`). The OOP layer is primary; the procedural layer is a compatibility shim from a completed migration.

### High-Level Flow
```
Browser → Apache → public/index.php → backend/index.php
  → Load .env → Boot OOP Application (DI Container)
  → Bootstrap procedural helpers
  → Start session → Generate CSRF token
  → Register routes (web.php + api.php)
  → Dispatch: Middleware → Controller → Service → Repository → MySQL
  → Response: JSON or HTML template
```

### Key Architectural Patterns

| Pattern | Implementation |
|---------|---------------|
| **Front Controller** | All requests → `public/index.php` → `backend/index.php` |
| **Dependency Injection** | `Container` + `AppServiceProvider` |
| **Repository Pattern** | `src/Repositories/*.php` — SQL isolated from business logic |
| **Service Layer** | `src/Services/*.php` — business rules, not HTTP concerns |
| **Middleware Chain** | `auth`, `csrf`, `non_admin`, `role:admin` tags on routes |
| **Job Queue** | `job_queue` table + `cron.php` + `src/Jobs/*.php` |
| **Soft Deletes** | `is_deleted=1` on `posts` and `deals` |
| **Token Encryption** | AES-256-CBC on all OAuth tokens in `social_accounts` |

---

## 5. Database Structure

**22 tables, 4 views, 1 trigger** in MySQL database `creatorz_hive`.

### Core Tables
| Table | Purpose |
|-------|---------|
| `users` | All accounts (creator/brand/admin) |
| `user_preferences` | Per-user UI settings |
| `social_accounts` | Connected platform accounts (encrypted tokens) |
| `posts` | Content pieces (draft/scheduled/published) |
| `media_files` | Uploaded images and videos |
| `tags` | Post labels |
| `deals` | Brand sponsorship CRM |
| `invoices` | Financial documents |
| `analytics` | Rolling aggregate totals per user |
| `analytics_snapshots` | Time-series metrics for charts |
| `notifications` | In-app alerts |
| `job_queue` | Async task persistence |
| `audit_logs` | Immutable action trail |

### Auto-Created on Registration
MySQL trigger `trg_after_user_insert` creates:
- `analytics` row (all zeros)
- `notification_preferences` row (all enabled)
- `user_preferences` row (defaults)

### Important Relationships
```
users → posts → post_media → media_files
users → deals → deal_posts → posts
users → deals → invoices
users → social_accounts → platform_post_results
users → analytics / analytics_snapshots
```

Full ER diagram: `docs/database/database-er-diagram.md`
Full analysis: `docs/database/database-analysis.md`

---

## 6. Request Lifecycle

### GET Request (HTML Page)
```
GET /?route=dashboard
  1. public/index.php → require backend/index.php
  2. load_env() → config constants
  3. Application::boot() → Container → AppServiceProvider
  4. bootstrap-procedural.php → router, session, compat helpers
  5. session_start_safe()
  6. csrf_generate_token()
  7. Register routes (web.php + api.php)
  8. router_dispatch()
     → route = 'dashboard'
     → middleware: auth_middleware_handle() [validates session]
     → DashboardController::index()
        → ViewRenderer::render('frontend/pages/dashboard/index.php')
  9. PHP template renders HTML with window.__USER__, window.__CSRF__
```

### POST Request (JSON API)
```
POST /?route=create_post {title, content, platforms, _csrf_token}
  1-7. Same bootstrap as above
  8. router_dispatch()
     → route = 'create_post'
     → middleware: auth + non_admin + csrf (3 checks)
     → PostController::store()
        → PostInputNormalizer::normalize()
        → PostRepository::create() → INSERT INTO posts
        → JobQueueRepository::dispatch('publish_post', {post_id})
        → JsonResponder::success(['post_id' => 42])
  9. Response: {"success":true,"message":"...","data":{"post_id":42}}
```

---

## 7. Authentication System

### Login Methods
1. **Email/Password** — `POST ?route=login` → bcrypt verify → PHP session
2. **Google Sign-In** — `GET ?route=google-auth` → Google OAuth → `?route=google-callback`

### Session Architecture
- PHP native sessions with file storage
- Session cookie: `httponly=true`, `samesite=Strict`, `secure=true` (production)
- **Fingerprint** protection: SHA-256(IP_subnet + UA) stored in session
- On every authenticated request: fingerprint re-validated → session destroyed on mismatch

### Platform OAuth (NOT Login)
Meta/Instagram/Facebook OAuth connects social accounts for publishing:
`?route=oauth-connect?platform=instagram` → Facebook OAuth → `?route=oauth-callback`
Results stored in `social_accounts` table with encrypted tokens.

### CSRF Protection
Every POST request requires `_csrf_token` matching `$_SESSION['_csrf_token']` (64-char hex, `hash_equals` comparison).

---

## 8. Feature Reference

| Feature | Route (page) | API Routes | JS File |
|---------|-------------|-----------|---------|
| Login/Register | `?route=login` | `login`, `register` | `auth.js` |
| Dashboard | `?route=dashboard` | `dashboard_data` | `dashboard.js` |
| Content Planner | `?route=planner` | `posts`, `create_post`, etc. | `planner.js` |
| Analytics | `?route=analytics` | `analytics_data` | `analytics.js` |
| Deals CRM | `?route=deals` | `deals_data`, `create_deal`, etc. | `deals.js` |
| Invoices | `?route=invoices` | `invoices_data`, `create_invoice`, etc. | `invoices.js` |
| Media Library | `?route=media` | `media_list`, `upload_media` | `media.js` |
| Notifications | `?route=notifications` | `notifications_data`, `mark_read` | `notifications.js` |
| Settings | `?route=settings` | `profile_data`, `update_profile`, etc. | `settings.js` |
| Admin Panel | `?route=admin-users` | `admin_users`, `admin_overview` | `admin-users.js` |

Detailed feature-to-code mapping: `docs/knowledge-base/feature-map.md`

---

## 9. API Reference

All APIs use `?route=<name>` — no REST paths. Session cookie authentication.

### Response Format
```json
{ "success": true, "message": "OK", "data": {} }
{ "success": false, "message": "Error", "errors": {} }
```

### Key API Routes
- `GET ?route=api_me` — Current user + fresh CSRF token
- `GET ?route=dashboard_data` — Dashboard summary
- `GET ?route=posts?status=scheduled` — Post list
- `POST ?route=create_post` — Create/schedule post
- `GET ?route=analytics_data?period=weekly` — Analytics data
- `GET ?route=deals_data` — Kanban board data
- `POST ?route=update_deal_status` — Move deal to new stage
- `POST ?route=upload_media` — Upload image/video
- `GET ?route=notifications_count` — Unread count

Full route catalog: `docs/apis/api-analysis.md`

---

## 10. Social Media Integrations

| Platform | Publishing | Analytics | OAuth | Config |
|----------|-----------|-----------|-------|--------|
| **Instagram** | ✅ 2-step Graph API | ✅ followers_count | ✅ Meta OAuth | `META_APP_ID`, `META_APP_SECRET` |
| **Facebook** | ✅ Page feed | ✅ followers_count | ✅ Meta OAuth | Same as Instagram |
| **TikTok** | ✅ Inbox draft | ❌ Heuristic | ❌ Manual token | `TIKTOK_ACCESS_TOKEN` |
| **YouTube** | ✅ Video metadata | ✅ Channel stats | Partial (refresh) | `YOUTUBE_ACCESS_TOKEN`, `GOOGLE_CLIENT_ID` |
| **Twitter/X** | ✅ Tweet | ❌ None | ❌ Manual token | `TWITTER_BEARER_TOKEN` |

**Dev mode:** `SOCIAL_API_MOCK_FALLBACK=true` returns mock success for all platforms.

Full integration docs: `docs/knowledge-base/integration-map.md`

---

## 11. Background Jobs

`scripts/cron.php` runs every minute. Manages 3 queues:

| Queue | Job | Frequency | Purpose |
|-------|-----|-----------|---------|
| `default` | `PublishPostJob` | Every minute | Publish scheduled posts |
| `analytics` | `FetchAnalyticsJob` | Hourly | Sync platform analytics |
| `cleanup` | `CleanupMediaJob` | Daily 2AM | Remove orphaned media |
| `default` | `SendNotificationJob` | As queued | Send notification emails |

**Job lifecycle:**
1. Controller/cron dispatches job → `INSERT INTO job_queue`
2. `cron.php` → `job_runner_run()` → marks `running`
3. Job `handle()` executes
4. Marks `completed` or `failed` (up to 3 attempts)

**Monitor jobs:**
```sql
SELECT job_class, status, attempts, error_message 
FROM job_queue ORDER BY created_at DESC LIMIT 20;
```

---

## 12. Security Model

### Implemented Protections
| Protection | Implementation |
|-----------|---------------|
| Password hashing | bcrypt cost 12 |
| CSRF protection | 64-char token, `hash_equals` |
| Session fingerprinting | SHA-256(IP+UA) |
| SQL injection prevention | PDO prepared statements, `ATTR_EMULATE_PREPARES=false` |
| Token encryption | AES-256-CBC (`TokenCrypto`) |
| Rate limiting | Token bucket in `rate_limits` table |
| Security headers | X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy |
| Role enforcement | Per-route middleware |

### Critical Production Requirements
1. **Set `APP_SECRET`** — If empty, tokens are encrypted with a known dev key
2. **Set `APP_DEBUG=false`** — Hides stack traces from users
3. **Set `SESSION_SECURE=true`** — Requires HTTPS for session cookie
4. **Set document root to `public/`** — Prevents `.env` web exposure
5. **Set `SOCIAL_API_MOCK_FALLBACK=false`** — Required for real publishing

Full security audit: `docs/security/security-audit.md`

---

## 13. Folder Structure Reference

```
/
├── public/            ← WEB ROOT. Apache points here.
│   └── index.php      ← Entry point. Requires backend/index.php.
│
├── backend/           ← PHP backend. NOT web-accessible.
│   ├── index.php      ← Main bootstrap. Loaded by public/index.php.
│   ├── routes/        ← web.php (HTML) + api.php (JSON) route definitions
│   ├── core/          ← Procedural helpers (router, session, db, mailer)
│   ├── compat/        ← OOP bridge functions (being phased out)
│   ├── middleware/    ← Procedural middleware wrappers
│   ├── config/        ← Constants from .env
│   ├── helpers/       ← Global utility functions
│   └── storage/       ← Logs, email templates, cron state
│
├── src/               ← OOP source (CreatorzHive\ namespace)
│   ├── Controllers/   ← HTTP handlers
│   ├── Services/      ← Business logic
│   ├── Repositories/  ← SQL data access
│   ├── Jobs/          ← Async background tasks
│   ├── Middleware/     ← Auth, CSRF, Role
│   ├── Core/          ← Container, Connection, Router, TokenCrypto
│   ├── Providers/     ← DI wiring (AppServiceProvider)
│   ├── Support/       ← Helper classes
│   └── Config/        ← AppConfig
│
├── frontend/          ← Frontend assets (HTML, CSS, JS)
│   ├── pages/         ← PHP/HTML templates per feature
│   ├── js/            ← Per-feature JavaScript modules
│   ├── css/           ← Per-feature stylesheets
│   ├── components/    ← Reusable HTML fragments
│   ├── fonts/         ← Self-hosted web fonts
│   └── assets/        ← Self-hosted Chart.js
│
├── database/          ← Schema, migrations, seeds
├── scripts/           ← CLI tools (migrate, seed, cron, etc.)
├── tests/             ← PHPUnit unit + integration tests
├── vendor/            ← Composer dependencies
├── .env               ← Environment config (NOT committed to git)
├── .env.example       ← Environment template
└── composer.json      ← Dependencies and autoload config
```

---

## 14. Key Classes Reference

| Class | File | What It Does |
|-------|------|-------------|
| `Application` | `src/Core/Application.php` | Bootstrap; creates DI container |
| `Container` | `src/Core/Container.php` | Service locator / DI container |
| `Connection` | `src/Core/Database/Connection.php` | PDO wrapper; all DB queries |
| `TokenCrypto` | `src/Core/Security/TokenCrypto.php` | AES-256-CBC encryption for tokens |
| `AppServiceProvider` | `src/Providers/AppServiceProvider.php` | Wires all DI bindings (~450 lines) |
| `AuthMiddleware` | `src/Middleware/AuthMiddleware.php` | Session validation on every auth request |
| `AuthService` | `src/Services/AuthService.php` | Password hashing, token generation |
| `SocialApiService` | `src/Services/SocialApiService.php` | All platform API calls |
| `MetaOAuthService` | `src/Services/MetaOAuthService.php` | Meta/Instagram/Facebook OAuth flow |
| `PublishPostJob` | `src/Jobs/PublishPostJob.php` | Publishes scheduled posts |
| `FetchAnalyticsJob` | `src/Jobs/FetchAnalyticsJob.php` | Syncs platform analytics |
| `UserRepository` | `src/Repositories/UserRepository.php` | User CRUD |
| `PostRepository` | `src/Repositories/PostRepository.php` | Post CRUD + scheduling |
| `SocialAccountRepository` | `src/Repositories/SocialAccountRepository.php` | Platform accounts (with crypto) |

---

## 15. Development Guidelines

### Adding a New Feature

1. **Database:** Add table to `database/schema.sql` + create migration in `database/migrations/`
2. **Repository:** Create `src/Repositories/NewFeatureRepository.php` (receives `Connection`)
3. **Service:** Create `src/Services/NewFeatureService.php` if business logic needed
4. **Controller:** Create `src/Controllers/NewFeatureController.php` (extends `AbstractController`)
5. **DI Wiring:** Add to `src/Providers/AppServiceProvider.php`
6. **Routes:** Add to `backend/routes/api.php` (JSON) and/or `backend/routes/web.php` (pages)
7. **Frontend:** Add page template in `frontend/pages/` and JS in `frontend/js/`
8. **Test:** Add unit test in `tests/unit/` and integration test in `tests/integration/`

### Code Conventions
- `declare(strict_types=1)` at top of every PHP file
- Constructor injection (no `new` inside services/controllers)
- Repository methods must filter by `user_id` to prevent IDOR
- All POST routes must include `['csrf']` in middleware array
- Use `$this->db->query()` with named parameters — never string concatenation
- Return `JsonResponder::success()` / `JsonResponder::error()` from API controllers
- Return `ViewRenderer::render()` from page controllers

### Testing
```bash
./vendor/bin/phpunit                    # All tests
./vendor/bin/phpunit --testsuite=Unit   # Unit tests only
./vendor/bin/phpunit tests/unit/AuthServiceTest.php  # Single file
```

For integration tests, create a test database: `DB_DATABASE_TEST=creatorz_hive_test` in `.env`.

---

## 16. Known Issues & Technical Debt

| Issue | Severity | File | Recommended Fix |
|-------|----------|------|----------------|
| PHP 7.4 (EOL since Nov 2022) | CRITICAL | `composer.json` | Upgrade to PHP 8.1+ |
| APP_SECRET empty = insecure encryption | CRITICAL | `TokenCrypto.php` | Enforce in production |
| SocialApiService circular with compat layer | HIGH | `compat/services.php` | Move impl to OOP, compat delegates |
| Missing CSP header | HIGH | `backend/index.php` | Add Content-Security-Policy |
| Meta OAuth state not validated | MEDIUM | `OauthController.php` | Store state in session, validate on callback |
| OOP Router class unused | LOW | `src/Core/Routing/Router.php` | Remove or wire up |
| TokenCrypto::pack() silent failure | MEDIUM | `TokenCrypto.php` | Throw RuntimeException |
| IDOR risk in some queries | MEDIUM | Repositories | Add `findByIdAndUser()` |

Full analysis: `docs/code-quality/code-review.md`
Full security audit: `docs/security/security-audit.md`

---

## 17. Where to Find Things

| Question | Answer / Location |
|----------|-----------------|
| Where does a request enter? | `public/index.php` → `backend/index.php` |
| Where are routes defined? | `backend/routes/web.php` (pages) + `backend/routes/api.php` (API) |
| How does DI work? | `src/Core/Container.php` + `src/Providers/AppServiceProvider.php` |
| Where is auth logic? | `src/Services/AuthService.php` + `src/Middleware/AuthMiddleware.php` |
| Where are SQL queries? | `src/Repositories/*.php` |
| How are tokens encrypted? | `src/Core/Security/TokenCrypto.php` |
| Where is the DB schema? | `database/schema.sql` |
| How do posts get published? | `src/Jobs/PublishPostJob.php` → `SocialApiService.php` |
| How are jobs run? | `scripts/cron.php` → `backend/core/job_runner.php` |
| Where are email templates? | `backend/storage/email-templates/` |
| Where are error logs? | `backend/storage/logs/error-YYYY-MM-DD.log` |
| Where is platform API config? | `.env` + Admin → Platform Credentials |
| Where are frontend assets? | `frontend/js/`, `frontend/css/`, `frontend/pages/` |
| How is session managed? | `backend/core/session.php` |
| How does CSRF work? | `backend/middleware/csrf.php` + `backend/core/csrf_token` in session |

---

## 18. Documentation Index

| Document | Location | Covers |
|----------|----------|--------|
| **This Guide** | `docs/MASTER_PROJECT_GUIDE.md` | Everything |
| Architecture Report | `docs/architecture/project-architecture.md` | System design, flows, stack |
| Dependency Map | `docs/architecture/dependency-map.md` | Package deps, circular deps |
| Database Analysis | `docs/database/database-analysis.md` | All 22 tables, indexes, optimization |
| ER Diagram | `docs/database/database-er-diagram.md` | Entity relationships |
| API Analysis | `docs/apis/api-analysis.md` | All routes + external APIs |
| Security Audit | `docs/security/security-audit.md` | Risks, fixes, security model |
| Code Review | `docs/code-quality/code-review.md` | Technical debt, quality issues |
| Business Analysis | `docs/business/business-analysis.md` | Market, features, monetization |
| Glossary | `docs/knowledge-base/glossary.md` | Term definitions |
| Feature Map | `docs/knowledge-base/feature-map.md` | Features → code mapping |
| Workflow Map | `docs/knowledge-base/workflow-map.md` | End-to-end user workflows |
| Integration Map | `docs/knowledge-base/integration-map.md` | Platform integrations setup |
| Deployment Guide | `docs/knowledge-base/deployment-guide.md` | Dev + production setup |
| Troubleshooting | `docs/knowledge-base/troubleshooting-guide.md` | Common problems + fixes |
| Roadmap | `docs/roadmap/project-roadmap.md` | Feature roadmap (Now → 24 months) |

### Per-File Documentation (`.explained.md`)
| File | Explained |
|------|-----------|
| `src/Core/Application.php` | `src/Core/Application.explained.md` |
| `src/Core/Container.php` | `src/Core/Container.explained.md` |
| `src/Core/Database/Connection.php` | `src/Core/Database/Connection.explained.md` |
| `src/Core/Security/TokenCrypto.php` | `src/Core/Security/TokenCrypto.explained.md` |
| `src/Middleware/AuthMiddleware.php` | `src/Middleware/AuthMiddleware.explained.md` |
| `src/Services/AuthService.php` | `src/Services/AuthService.explained.md` |
| `src/Services/SocialApiService.php` | `src/Services/SocialApiService.explained.md` |
| `src/Jobs/PublishPostJob.php` | `src/Jobs/PublishPostJob.explained.md` |
| `backend/core/router.php` | `backend/core/router.explained.md` |
| `backend/routes/api.php` | `backend/routes/api.explained.md` |
| `backend/helpers/functions.php` | `backend/helpers/functions.explained.md` |

---

*Documentation generated by comprehensive codebase audit on 2026-06-10.*
*Total: 18 documentation files covering architecture, database, APIs, security, business analysis, and code quality.*
