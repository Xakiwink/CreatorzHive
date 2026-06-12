# CreatorzHive — Code Quality Report

> Static analysis from repository structure and architecture review. Severity: **Critical** | **High** | **Medium** | **Low**.

## Summary

The project has completed a meaningful OOP migration (`src/` + DI) with a compat layer for legacy callers. Overall quality is **good for a custom PHP app**, with clear separation in new code. Main risks are dual paradigms (procedural + OOP), session security defaults, and operational secrets handling.

## Issues Found

| ID | Severity | Area | Issue | Suggested fix |
|----|----------|------|-------|----------------|
| Q1 | Medium | Architecture | Procedural `backend/compat` + globals coexist with DI | Continue migrating callers; avoid new `model_*` wrappers |
| Q2 | Medium | Auth UI | `bootstrap-web-view.php` does not boot container — `google_auth_is_configured()` only checks `.env` | Boot minimal app on auth pages or document env-only requirement |
| Q3 | Low | Duplication | Route registration split across `web.php` / `api.php` without OpenAPI | Keep `docs/api.md` in sync; consider route manifest |
| Q4 | Medium | Security | Session fingerprint uses IP — mobile users may be logged out | Document behavior; consider relaxing IP binding |
| Q5 | Low | Frontend | Terms/Privacy links on register are `onclick="return false;"` placeholders | Replace with real policy URLs |
| Q6 | Medium | Tests | Integration tests share dev DB unless `DB_DATABASE_TEST` set | Enforce test DB in CI |
| Q7 | Low | Media | Upload dir writability skipped in CI | Expected; document in setup |
| Q8 | Medium | Secrets | Platform credentials in DB + `.env` | Use encryption at rest (`encrypt-social-tokens.php`); restrict admin access |
| Q9 | Low | Dead code | Empty `backend/controllers/` may remain from migration | Remove if unused |
| Q10 | High | Ops | Google OAuth requires exact redirect URI match | Document `APP_URL` + `APP_BASE_PATH`; use `GOOGLE_AUTH_REDIRECT_URI` when needed |

## Duplicated logic

- Password validation appears in `AuthController` and client-side `auth.js` — acceptable dual validation; keep rules aligned.
- URL building: `route_url()`, `routeQueryUrl()` (JS) — subdirectory-safe; ensure `APP_BASE_PATH` consistent.

## Missing error handling

- External API failures in `SocialApiService` often fall back to mock — intentional for dev; log failures in production.
- Some repository methods assume rows exist — controllers generally check; add null guards when extending.

## Architectural inconsistencies

| Pattern | Location | Note |
|---------|----------|------|
| OOP controllers | `src/Controllers` | Preferred |
| Procedural router | `backend/core` | By design |
| Direct PHP views | `frontend/pages` | Some self-bootstrap vs `ViewRenderer` |

## Security vulnerabilities (review)

| Check | Status |
|-------|--------|
| SQL injection | Mitigated via parameterized queries in `Connection` |
| XSS | Views use `htmlspecialchars` in PHP templates; ensure JSON APIs escape on client render |
| CSRF | Enforced on POST API routes |
| Auth bypass | Middleware on protected routes — verify new routes register middleware |
| File upload | `MediaUploadHelper` — review MIME/size limits when hardening |

## Performance bottlenecks

- N+1 queries possible in list endpoints — profile with MySQL slow log if lists grow.
- `job_queue` polled by cron every minute — adequate for small scale.
- Large analytics aggregates — `analytics_snapshots` helps; index date columns.

## Tight coupling

- `AppServiceProvider` centralizes many bindings — good for small app; split providers if container grows.
- `SettingsController` has many dependencies — candidate for facade or sub-controllers.

## Refactoring recommendations

1. **Single bootstrap** for all PHP entry points (web views, CLI, tests).
2. **Extract API route table** to PHP array → generate `docs/api.md` section.
3. **Remove compat** once no `model_*` references remain (grep periodically).
4. **Policy pages** for legal links on registration.

## Maintainability

- Strong: `OOP.md`, PHPUnit suite, migrations folder.
- Improve: per-folder README (this documentation pass), `SYSTEM_OVERVIEW.md` as onboarding hub.

## Scalability ideas

- Object storage for `public/uploads/`
- Redis for sessions and rate limits
- Dedicated queue worker process
- Read replica for analytics reporting

---

See [SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md) for architecture context.
