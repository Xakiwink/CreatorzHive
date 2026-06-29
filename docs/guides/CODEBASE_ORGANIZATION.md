# CreatorzHive — Codebase Organization

How the project is structured and where to find things.

---

## Directory Structure

```
creatorzhive/
├── src/                    OOP business logic (production code)
├── backend/                Procedural compatibility layer
├── frontend/               CSS, JS, HTML templates, fonts, assets
├── public/                 Web entry point (index.php, setup.php, webhook, uploads)
├── database/               schema.sql (live DB dump)
├── scripts/                CLI utilities
├── tests/                  PHPUnit test suite
├── vendor/                 Composer dependencies
├── docs/                   Documentation
├── .env                    Runtime config (not committed)
├── .env.example            Config template
├── composer.json
└── .htaccess               URL routing
```

---

## Source Code (`src/`)

```
src/
├── Config/         AppConfig — loads .env, exposes config values
├── Contracts/      Interfaces (SocialProviderInterface)
├── Controllers/    HTTP request handlers — one class per route group
├── Core/           Application bootstrap, DI container, Router, DB connection, TokenCrypto
├── Helpers/        PlatformHelper — canonical platform slug list
├── Jobs/           Background jobs: PublishPostJob, FetchAnalyticsJob, etc.
├── Middleware/     AuthMiddleware, CsrfMiddleware, RoleMiddleware
├── Providers/      AppServiceProvider — DI wiring
├── Repositories/   Database queries — one class per table
├── Services/       Business logic — OAuth, social APIs, admin settings, etc.
└── Support/        Helper classes: MediaUploadHelper, PostInputNormalizer, etc.
```

Each folder has a `README.md` with a file-by-file summary.

---

## Backend Layer (`backend/`)

Procedural compatibility bridge used by route handlers and legacy code.

```
backend/
├── index.php               Entry point — APP_SECRET check, bootstrap
├── bootstrap-oop.php       Boots DI container (AppServiceProvider)
├── bootstrap-procedural.php  Legacy compat setup
├── routes/
│   ├── web.php             Page and form routes
│   └── api.php             JSON API routes
├── compat/
│   ├── models.php          ~60 global functions wrapping Repositories
│   ├── services.php        ~20 global functions wrapping Services
│   └── auth.php            9 global functions wrapping AuthService
├── core/                   Procedural utilities (mailer, validator, session, etc.)
├── helpers/                Helper functions (platforms, functions, api_cors)
├── middleware/             Procedural middleware wrappers
└── storage/
    ├── email-templates/    HTML email templates
    ├── logs/               Error and mail logs (gitignored)
    └── uploads/            Gitkeep placeholder
```

---

## Frontend (`frontend/`)

```
frontend/
├── js/             JavaScript modules (one per page)
├── css/            Stylesheets
├── pages/          PHP + HTML page templates
│   ├── auth/       Login, register, password reset, verify
│   ├── dashboard/  Dashboard (index.php includes dashboard.html)
│   ├── planner/    Content planner (index.php includes planner.html)
│   ├── analytics/  Analytics (index.php includes analytics.html)
│   ├── monetization/ Deals and invoices
│   ├── media/      Media library
│   ├── settings/   Profile, integrations, admin
│   ├── notifications/
│   ├── errors/     404.html, 500.html (included by error_handler.php)
│   └── partials/   Shared PHP partials (app_script_globals.php)
├── components/     Shared HTML fragments (navbar, sidebar, modal, toast)
├── fonts/          Self-hosted Inter, JetBrains Mono, Playfair Display
└── assets/         icon.svg, Chart.js bundle
```

Note: `.html` files inside `pages/` are loaded by their sibling `index.php` or `.php` file via `file_get_contents()` — they are not standalone pages.

---

## Database (`database/`)

```
database/
├── schema.sql          Full live database dump — import this into phpMyAdmin
├── migrations/         (empty — future schema changes go here)
└── seeds/              (empty — demo data is included in schema.sql)
```

---

## Scripts (`scripts/`)

CLI utilities for local development and server diagnostics:

| Script | Purpose |
|--------|---------|
| `migrate.php` | Runs schema.sql against a local database |
| `hash-password.php` | Generates a bcrypt hash for manual user creation |
| `encrypt-social-tokens.php` | Re-encrypts stored OAuth tokens after key rotation |
| `verify-server.php` | Checks PHP extensions and server config |
| `download-frontend-vendor.sh` | Downloads Chart.js bundle |

---

## Public Entry Point (`public/`)

```
public/
├── index.php           Front controller — routes all requests
├── setup.php           One-time setup wizard (delete after use)
├── verify-deployment.php  Deployment health checker
├── webhook/
│   └── process-jobs.php  Background job trigger (called by UptimeRobot)
├── uploads/            User-uploaded media files
└── .htaccess           Blocks PHP execution in uploads/
```

---

## Documentation (`docs/`)

```
docs/
├── guides/
│   ├── INFINITYFREE_SETUP.md       Deployment guide
│   ├── INSTAGRAM_BUSINESS_LOGIN.md Instagram OAuth setup
│   ├── MANUAL_SQL_IMPORT.md        phpMyAdmin import guide
│   └── CODEBASE_ORGANIZATION.md   This file
├── knowledge-base/
│   ├── feature-map.md              Features → code mapping
│   └── troubleshooting-guide.md   Common problems and fixes
├── reference/
│   ├── FINAL_DEPLOYMENT_AUDIT.md  Phase 1 checklist
│   └── infinityfree-compatibility-report.md  Hosting constraints
├── business/
│   └── business-analysis.md
└── roadmap/
    └── project-roadmap.md
```

---

## Supported Platforms

Instagram, TikTok, YouTube, X/Twitter. Facebook has been removed.

Instagram uses **Meta Graph API v25 Business Login** — see [INSTAGRAM_BUSINESS_LOGIN.md](INSTAGRAM_BUSINESS_LOGIN.md).
