# CreatorzHive — OOP architecture

The backend is object-oriented under PSR-4 (`CreatorzHive\` → `src/`). HTTP routes resolve to controllers; domain access goes through repositories and services. A thin **compat** layer keeps legacy global function names for jobs and remaining callers.

## Directory structure

```
src/
├── Config/AppConfig.php
├── Core/Application.php, Container.php, Database/Connection.php, Http/*
├── Controllers/                 # HTTP (Auth, Post, Deal, …)
├── Repositories/                # SQL / persistence
├── Services/                    # Business rules
├── Jobs/                        # Queue handlers (PublishPost, FetchAnalytics, …)
├── Support/
│   ├── PostInputNormalizer.php
│   ├── SettingsPageHelper.php
│   ├── MediaUploadHelper.php
│   ├── AnalyticsReportHelper.php
│   └── DealWorkflowHelper.php
├── Core/Security/TokenCrypto.php
├── Middleware/
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   └── RoleMiddleware.php
└── Providers/AppServiceProvider.php

backend/
├── bootstrap-oop.php            # Application::boot()
├── bootstrap-procedural.php     # Compat, middleware, router, job runner
├── compat/
│   ├── models.php               # Global model_* → repositories
│   ├── services.php             # Global *_service_* → services
│   └── auth.php                 # auth_service_* → AuthService
├── routes/web.php, api.php
└── core/, middleware/           # Router; middleware/*.php delegates to src/Middleware/*
```

Removed at runtime (no longer loaded): `backend/handlers/`, `backend/models/`, `backend/services/`, `backend/jobs/handlers.php`.

## Boot order

1. `backend/index.php` or `scripts/cron.php` — `.env`, Composer autoload
2. `backend/bootstrap-oop.php` — `Application::boot()`, `$GLOBALS['cz_container']`
3. `backend/bootstrap-procedural.php` — compat, router, jobs
4. `router_dispatch()` or `job_runner_run()` — resolves from container

## Jobs

Queue rows use class aliases (`publish_post`, `fetch_analytics`, …). `job_runner_resolve_callable()` loads `CreatorzHive\Jobs\*` from the container. Cron must boot OOP first (`scripts/cron.php` loads `bootstrap-oop.php`).

| Job key | Class |
|---------|--------|
| `publish_post` | `PublishPostJob` |
| `fetch_analytics` | `FetchAnalyticsJob` |
| `cleanup_media` | `CleanupMediaJob` |
| `send_notification` | `SendNotificationJob` |

## Controllers with full DI (not compat for domain)

- **DashboardController** — `DashboardService`
- **PostController** — repos + `PostInputNormalizer` + `NotificationService`
- **AuthController** — `AuthService`, `AuthRateLimitService`, `AdminService`, `UserRepository`, `NotificationService`
- **SettingsController** — `SettingsPageHelper`
- **MediaController** — `MediaFileRepository`, `MediaUploadHelper`
- **DealController** — `DealRepository`, `InvoiceRepository`, `DealWorkflowHelper`, `NotificationService`
- **AnalyticsController** — `AnalyticsRepository`, `AnalyticsReportHelper`, `AnalyticsService`
- **TagController** — `TagRepository`
- **InvoiceController** — `InvoiceRepository`, `DealRepository`, `NotificationService`
- **NotificationController** — `NotificationRepository`
- **AdminUserController** — `UserRepository`, `AdminService`, `AuditLogRepository`, `PlatformApiSecretsService`, `SocialApiService`, `AuthService`
- **OauthController** — `MetaOAuthService`, `AdminService`
- **SettingsController** — `SettingsPageHelper`, `UserRepository`, prefs/sessions/social repos, `AuthService`, `AdminService`, `MetaOAuthService`, `JobQueueRepository`
- **ApiMetaController** — `ApiMetaService` (`CsrfMiddleware`, `UserPayloadFormatter`)

## Verify

```bash
composer dump-autoload
./vendor/bin/phpunit
```

## Database access

- Prefer `app_connection()` (returns `Connection` when the container is booted) in new code.
- Legacy `db_*` helpers in `backend/core/database.php` delegate to `Connection` when the OOP container is booted; otherwise they use a standalone PDO.

## Middleware

`backend/middleware/*.php` are thin wrappers. Logic lives in `CreatorzHive\Middleware\*` and is resolved from the container (`AuthMiddleware` uses `UserRepository` for session refresh).

## Security & CLI

- **TokenCrypto** (`src/Core/Security/TokenCrypto.php`) — DB token encryption; procedural `token_crypto_*` delegates to the container or a fallback instance.
- **CLI scripts** use `backend/helpers/cli_bootstrap.php` (`cli_load_env()`, `cli_boot_oop()`, `cli_pdo()`) so migrate/verify/cron share the OOP `Connection` when Composer is present.

## Optional follow-ups

- Retire unused `scripts/oop-*.php` migration helpers once no longer needed
- **SystemController** — optional thin DI for consistency (no domain compat today)
