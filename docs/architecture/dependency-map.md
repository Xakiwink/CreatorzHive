# CreatorzHive — Dependency Map

> **Version:** 1.0 | **Date:** 2026-06-10

---

## 1. External Dependencies (Composer)

### Production Dependencies

| Package | Version Constraint | Purpose | Used By |
|---------|-------------------|---------|--------|
| `phpmailer/phpmailer` | ^6.8 | SMTP email sending | `backend/core/mailer.php`, `NotificationService`, email verification, password resets |

### Development Dependencies

| Package | Version Constraint | Purpose |
|---------|-------------------|---------|
| `phpunit/phpunit` | ^9.6 | Unit and integration testing |

### Runtime Requirements

| Requirement | Minimum | Notes |
|-------------|---------|-------|
| PHP | 7.4 | Typed properties, arrow functions |
| ext-pdo | bundled | Database access |
| ext-pdo_mysql | bundled | MySQL driver |
| ext-openssl | bundled | AES encryption (TokenCrypto) |
| ext-json | bundled | JSON encoding/decoding |
| ext-curl | optional | Social API HTTP calls (falls back gracefully if missing) |
| MySQL | 8.0 | JSON column support, FULLTEXT on InnoDB |

---

## 2. Internal Dependency Graph

### Core Layer (no internal dependencies)

```
Connection          ← depends on: AppConfig, ext-pdo, ext-pdo_mysql
TokenCrypto         ← depends on: ext-openssl, env()
JsonResponder       ← no dependencies
ViewRenderer        ← no dependencies
Container           ← no dependencies
AppConfig           ← depends on: env()
```

### Repository Layer (depends on Core only)

```
UserRepository              ← Connection
PostRepository              ← Connection
MediaFileRepository         ← Connection
TagRepository               ← Connection
DealRepository              ← Connection
InvoiceRepository           ← Connection
NotificationRepository      ← Connection
NotificationPrefRepository  ← Connection
UserPreferencesRepository   ← Connection
UserSessionRepository       ← Connection
JobQueueRepository          ← Connection
AuditLogRepository          ← Connection
AnalyticsRepository         ← Connection + PostRepository
SocialAccountRepository     ← Connection + TokenCrypto
DashboardRepository         ← Connection
```

### Service Layer (depends on Repositories + Core)

```
AuthService                 ← Connection
AuthRateLimitService        ← Connection
GoogleAuthService           ← env() (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET)
MetaOAuthService            ← Connection + compat functions (meta_oauth_*)
SocialApiService            ← Connection + compat functions (social_api_service_*)
AnalyticsService            ← Connection
DashboardService            ← DashboardRepository
NotificationService         ← Connection + UserRepository + NotifPrefRepo + NotifRepo
AdminService                ← Connection
ApiMetaService              ← CsrfMiddleware + UserPayloadFormatter
PlatformApiSecretsService   ← Connection + TokenCrypto
```

### Support Layer (depends on Repositories + Core)

```
MediaUploadHelper       ← no constructor deps (uses env, file system)
PostInputNormalizer     ← Connection + PostRepository
AnalyticsReportHelper   ← Connection
DealWorkflowHelper      ← AuditLogRepository + NotificationService + DealRepository
SettingsPageHelper      ← no constructor deps
UserPayloadFormatter    ← no deps
```

### Job Layer (depends on Repositories + Services)

```
PublishPostJob          ← PostRepository + SocialAccountRepository + SocialApiService
                           + NotificationService + AnalyticsRepository + Connection
FetchAnalyticsJob       ← SocialAccountRepository + SocialApiService
                           + AnalyticsService + Connection
CleanupMediaJob         ← Connection (file system + DB)
SendNotificationJob     ← UserRepository + NotifPrefRepository
```

### Controller Layer (depends on Services + Support)

```
AuthController          ← ViewRenderer + JsonResponder + Connection + AuthService
                           + AuthRateLimitService + AdminService + UserRepository
                           + NotificationService
DashboardController     ← ViewRenderer + JsonResponder + Connection + DashboardService
PostController          ← ViewRenderer + JsonResponder + Connection + PostRepository
                           + JobQueueRepository + MediaFileRepository + AnalyticsRepository
                           + NotificationService + PostInputNormalizer
AnalyticsController     ← ViewRenderer + JsonResponder + Connection + AnalyticsRepository
                           + AnalyticsReportHelper + AnalyticsService
DealController          ← ViewRenderer + JsonResponder + Connection + DealRepository
                           + InvoiceRepository + DealWorkflowHelper + NotificationService
InvoiceController       ← ViewRenderer + JsonResponder + Connection + InvoiceRepository
                           + DealRepository + NotificationService
MediaController         ← ViewRenderer + JsonResponder + Connection + MediaFileRepository
                           + MediaUploadHelper
NotificationController  ← ViewRenderer + JsonResponder + Connection + NotificationRepository
SettingsController      ← ViewRenderer + JsonResponder + Connection + SettingsPageHelper
                           + UserRepository + UserPreferencesRepository + UserSessionRepository
                           + NotifPrefRepository + SocialAccountRepository + AuthService
                           + AdminService + MetaOAuthService + JobQueueRepository
AdminUserController     ← ViewRenderer + JsonResponder + Connection + UserRepository
                           + AdminService + AuditLogRepository + PlatformApiSecretsService
                           + SocialApiService + AuthService
OauthController         ← ViewRenderer + JsonResponder + Connection + MetaOAuthService
                           + AdminService
GoogleAuthController    ← ViewRenderer + JsonResponder + Connection + GoogleAuthService
                           + UserRepository + AuthService + AdminService + NotificationService
TagController           ← ViewRenderer + JsonResponder + Connection + TagRepository
ApiMetaController       ← ViewRenderer + JsonResponder + Connection + ApiMetaService
SystemController        ← ViewRenderer + JsonResponder + Connection
```

---

## 3. Circular Dependency Analysis

### DETECTED: SocialApiService ↔ Compat Functions

```
SocialApiService::publishToInstagram()
    → calls: social_api_service_http_request() [compat function]
    
backend/compat/services.php: social_api_service_publish_to_instagram()
    → may call: SocialApiService::publishToInstagram() [OOP method]
    OR contain duplicate implementation
```

**Status:** Potential circular dependency in compat layer. The compat wrappers were auto-generated and may delegate back to OOP or contain duplicate code.

**Recommendation:** Audit `backend/compat/services.php` to confirm whether it delegates TO the OOP class or contains standalone implementations. If standalone, the OOP class should call the standalone implementation (not vice versa).

### No Other Circular Dependencies Detected

The overall dependency direction is clean:
```
Controllers → Services → Repositories → Connection
```

---

## 4. Unused Packages

None detected. Both `phpmailer/phpmailer` and `phpunit/phpunit` are actively used.

---

## 5. Missing Packages (Recommended Additions)

| Package | Purpose | Priority |
|---------|---------|---------|
| `phpstan/phpstan` or `vimeo/psalm` | Static analysis | HIGH — catches type errors before runtime |
| `friendsofphp/php-cs-fixer` | Code style enforcement | MEDIUM |
| `symfony/validator` | Advanced input validation | LOW |
| `dompdf/dompdf` or `mpdf/mpdf` | PDF invoice generation | MEDIUM (feature needed) |
| `league/flysystem` | Abstract file storage (local/S3/CDN) | MEDIUM (for CDN support) |
| `monolog/monolog` | Structured logging | LOW (current file logs are basic) |
| `guzzlehttp/guzzle` | HTTP client (replaces curl direct calls) | LOW |

---

## 6. Frontend Dependencies

### Self-Hosted (Zero CDN)

| Dependency | Location | Version | Purpose |
|-----------|----------|---------|---------|
| Chart.js | `frontend/assets/chart.js/chart.umd.min.js` | Unknown | Analytics charts |
| Inter font | `frontend/fonts/inter/` | — | Primary UI font |
| JetBrains Mono | `frontend/fonts/jetbrains-mono/` | — | Code/monospace |
| Playfair Display | `frontend/fonts/playfair-display/` | — | Display/headings |

All frontend dependencies are self-hosted — no external CDN calls. This is correct for offline-capable / low-bandwidth environments.

---

## 7. Upgrade Recommendations

| Package | Current | Action |
|---------|---------|--------|
| PHPMailer | ^6.8 | Acceptable; check for ^6.9+ security patches |
| PHP | 7.4 | **CRITICAL**: PHP 7.4 is end-of-life since Nov 2022. Upgrade to PHP 8.1 or 8.2. |
| PHPUnit | ^9.6 | Consider upgrading to PHPUnit 10 or 11 when PHP version is updated |
| Chart.js | Unknown | Identify current version; upgrade to latest stable |

### PHP 8.x Migration Notes

PHP 8.x provides:
- Named arguments (cleaner factory calls)
- Match expressions (replace switch in SocialApiService)
- Fibers (async job processing option)
- Enum types (replace ENUM-like constants)
- Union types, nullsafe operator `?->`

The codebase uses `declare(strict_types=1)` throughout — good foundation for PHP 8 upgrade. Main breaking changes to watch: deprecated functions, changed error handling, typed properties behavior.
