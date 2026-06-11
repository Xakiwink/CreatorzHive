# CreatorzHive — Code Quality Audit

> **Version:** 1.0 | **Date:** 2026-06-10

---

## Executive Summary

The codebase is in a **transitional state** between a procedural architecture and a proper OOP one. The OOP migration is largely complete for the core layer. Several issues exist around code duplication between the OOP classes and their compat wrappers, inconsistent error handling, and technical debt from the migration period. Overall quality is **above average for a PHP project of this size**.

---

## 1. Architecture Quality

### 1.1 Hybrid Architecture (Technical Debt)
**Severity:** MEDIUM

The `backend/compat/` directory contains three files that wrap OOP services with procedural global functions:
- `auth.php` — wraps `AuthService` with `auth_service_*` functions
- `models.php` — wraps repositories with `model_user_*`, `model_post_*` functions
- `services.php` — wraps `SocialApiService`, `MetaOAuthService`, `AnalyticsService`

**Problem:** The OOP classes also exist and are used via DI. The compat layer creates a dual codebase where logic can be called two ways. The compat files also re-duplicate method implementations (copy-pasted code in services.php that mirrors what's in the OOP service classes).

**Recommendation:**
1. Gradually remove compat functions as all callers are updated to use DI.
2. Mark all compat files with `@deprecated` comments.
3. Target removal in 3–6 months as part of codebase cleanup.

### 1.2 DI Container (Service Locator Anti-Pattern)
**Severity:** LOW

`AppServiceProvider` registers all dependencies. The container is a service locator stored in `$GLOBALS['cz_container']`. This works but is harder to test than constructor injection.

All controllers correctly receive dependencies via constructor injection through the DI container — this is good. The global fallback exists only for compat code.

### 1.3 Procedural Router Functions
**Severity:** LOW

The OOP `Router` class (`src/Core/Routing/Router.php`) exists alongside the procedural `router_register()`, `router_dispatch()` functions (`backend/core/router.php`). The OOP Router is currently unused — `router_dispatch()` (procedural) is what actually runs. The OOP Router may be leftover from a planned refactor.

**Recommendation:** Either wire the OOP Router into the bootstrap or remove it to avoid confusion.

---

## 2. Code Duplication

### 2.1 SocialApiService — OOP vs Compat
**Severity:** HIGH

`src/Services/SocialApiService.php` defines `publishToInstagram()`, `publishToTiktok()`, etc. as OOP methods. The corresponding compat functions in `backend/compat/services.php` contain nearly identical implementations.

The OOP service methods delegate to `social_api_service_publish_to_instagram()` (the compat function) via `use function` imports, and the compat functions delegate to... the OOP object methods? This creates a circular dependency chain.

**Recommendation:** The OOP service methods should contain the actual implementation. The compat functions should simply delegate to the OOP service. Eliminate duplicate logic.

### 2.2 MetaOAuthService — Same Issue
**Severity:** MEDIUM

Same pattern as SocialApiService — the OOP class delegates to compat functions that contain the actual implementation. This is backwards.

### 2.3 Database Access
**Severity:** LOW

Some places use `$this->db->query()/fetchOne()/fetchAll()` (OOP Connection), others use `db_query()/db_fetch_one()` (procedural compat). Both ultimately hit the same PDO connection but the dual API is confusing for new developers.

---

## 3. Code Smells

### 3.1 Large AppServiceProvider
**Severity:** LOW

`AppServiceProvider.php` is ~450 lines and registers all bindings for the entire application. Consider splitting into domain-specific providers (e.g., `PostServiceProvider`, `AuthServiceProvider`).

### 3.2 Global State via $GLOBALS
**Severity:** MEDIUM

Several components store/read from `$GLOBALS`:
- `$GLOBALS['cz_container']` — DI container
- `$GLOBALS['cz_app']` — Application instance
- `$GLOBALS['_cz_pdo']` — PDO connection
- `$GLOBALS['cz_router_routes']` — Route registry

This makes testing and dependency tracking harder. Acceptable for legacy compatibility but should be phased out.

### 3.3 Static Methods
**Severity:** LOW

`Application::boot()`, `AppServiceProvider::register()` are static methods that write to `$GLOBALS`. Acceptable for bootstrap code, but makes unit testing harder.

### 3.4 Undefined Function Usage
**Severity:** MEDIUM

Several `src/Services/*.php` files use `use function some_function_name;` to import procedural functions. If those functions don't exist (e.g., compat files not loaded), these will throw fatal errors at runtime. The compat loading in `bootstrap-procedural.php` must always happen before these services are instantiated.

---

## 4. Dead Code

### 4.1 OOP Router Class
**File:** `src/Core/Routing/Router.php`
**Status:** Appears unused — `router_dispatch()` (procedural) is the active dispatcher.

### 4.2 OOP Migration Scripts
**Files:** `scripts/oop-*.php` (8 files)
These were one-time code transformation scripts used during the OOP migration. They should be archived or deleted now that migration is complete.

### 4.3 `backend/jobs/.gitkeep` and `backend/controllers/.gitkeep`
These placeholder directories appear to be leftover from the procedural architecture. The OOP controllers are in `src/Controllers/`. The `backend/jobs/` and `backend/controllers/` directories are empty.

---

## 5. Error Handling

### 5.1 Inconsistent Error Returns
**Severity:** MEDIUM

Some methods return `['success' => false, 'error' => '…']` arrays on failure. Others throw exceptions. Others return `false` or `null`. Callers must handle all three patterns, leading to defensive coding.

**Recommendation:** Establish a convention — either always return result arrays (current dominant pattern) or always throw typed exceptions. Document the convention.

### 5.2 Silent Failures in Crypto
**Severity:** MEDIUM

`TokenCrypto::pack()` returns empty string `''` on OpenSSL failure. This means a failed encryption silently stores no token — downstream callers won't detect the problem until the token fails to decrypt.

**Recommendation:** Throw a `RuntimeException` on OpenSSL failure rather than returning empty string.

### 5.3 Job Queue Error Handling
**Severity:** LOW

Failed jobs increment `attempts` but the error is only stored in `error_message`. There is no alerting or notification when jobs fail repeatedly. Admin panel could show failed job counts.

---

## 6. Performance Issues

### 6.1 v_creator_summary View
**Severity:** MEDIUM

The `v_creator_summary` view contains three correlated subqueries per user row (active_deals, scheduled_posts, unread_notifications). For large user counts this will be slow.

**Recommendation:** Pre-compute these counts and cache them in the `analytics` table, updated by triggers or periodic jobs.

### 6.2 No Query Result Caching
**Severity:** LOW

Dashboard data, analytics snapshots, and notification counts are re-fetched on every page load. Consider adding short-lived (60-second) cache for dashboard aggregates.

### 6.3 Eager Loading Not Available
**Severity:** LOW

The repository pattern doesn't support eager loading relationships. Each related data fetch is a separate query (N+1 potential). Current usage appears to avoid N+1 but this is fragile as features grow.

---

## 7. Naming Issues

### 7.1 Mixed Convention in Functions
The procedural functions use `snake_case` (`auth_middleware_handle`, `session_get_user`) while OOP methods use `camelCase` (`handle()`, `getUser()`). This is expected for the hybrid architecture but can confuse new developers.

### 7.2 Route Names
Routes use `underscore_format` (`create_post`, `update_deal_status`) but some use `kebab-case` (`forgot-password`, `reset-password`, `oauth-callback`). Standardize on one format.

### 7.3 `READMimi.md`
The root contains `READMimi.md` — a typo/leftover file alongside `README.md` equivalent. Should be renamed or deleted.

---

## 8. Testing Coverage

### 8.1 Unit Tests
**Location:** `tests/unit/`
Tests cover: `AuthService`, `Validator`, `SocialAccountToken`, and potentially others.

**Gap:** No tests for `SocialApiService`, `MetaOAuthService`, `DealWorkflowHelper`, `PostInputNormalizer`.

### 8.2 Integration Tests
**Location:** `tests/integration/`
Integration tests use a real database (`IntegrationTestCase`). 

**Gap:** Test coverage for controllers is incomplete. Admin flows untested.

### 8.3 CI
GitHub Actions CI (`.github/workflows/ci.yml`) runs PHPUnit. 

**Gap:** No static analysis (PHPStan/Psalm), no code style enforcement (PHP-CS-Fixer), no security scanning.

---

## 9. Refactoring Recommendations (Prioritized)

| Priority | Issue | Action |
|----------|-------|--------|
| 1 (HIGH) | SocialApiService/MetaOAuthService circular delegation | Move implementation to OOP, compat delegates to OOP |
| 2 (HIGH) | TokenCrypto silent failure | Throw exception on OpenSSL error |
| 3 (MEDIUM) | IDOR risk in repositories | Add `findByIdAndUser()` variants |
| 4 (MEDIUM) | Inconsistent error handling | Establish and document error convention |
| 5 (MEDIUM) | v_creator_summary correlated subqueries | Pre-compute counts; add to analytics table |
| 6 (LOW) | OOP Router class unused | Remove or wire it up |
| 7 (LOW) | oop-*.php migration scripts | Archive or delete |
| 8 (LOW) | Mixed route naming convention | Standardize (suggest snake_case) |
| 9 (LOW) | AppServiceProvider too large | Split into domain providers |
| 10 (LOW) | Add PHPStan to CI | Catch type errors statically |
