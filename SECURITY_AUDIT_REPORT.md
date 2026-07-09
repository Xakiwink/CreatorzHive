# CreatorzHive — Security Audit Report

**Scope:** Full codebase (183 PHP files across `src/`, `backend/`, `public/`, `frontend/`, root-level entry scripts).
**Method:** Manual review — live request-path tracing, SQL query audit, OAuth flow tracing, output-escaping sampling, `.htaccess`/header review, dependency audit (`composer audit`), malware/backdoor sweep.
**Constraint:** No architecture, routing, UI, or business-logic changes. Only confirmed vulnerabilities were fixed; risky changes are documented as recommendations only (see SECURITY_FIX_PLAN.md).

## Executive Summary

The codebase is in noticeably good shape for a hand-rolled procedural/OOP hybrid PHP app: **all SQL access goes through a single PDO wrapper using prepared statements** (no string-concatenated queries were found anywhere), **CSRF protection is applied consistently to every state-changing POST route**, **output escaping (`htmlspecialchars` server-side, a shared `escapeHtml()` helper client-side) is applied consistently** everywhere sampled, session cookies are `HttpOnly`/`SameSite=Lax` with a configurable `Secure` flag, and passwords are hashed with bcrypt (cost 12). **No malware, web shells, obfuscated payloads, or backdoors were found** — the oddly-named `tiktokzewzmpsjzQhD8YOLrokyLMUavbPvshxz.txt` at the project root is a legitimate TikTok domain-verification file, not malicious.

Four confirmed issues were found and fixed (see below); none required changing routing, UI, or business logic. One additional issue (Instagram OAuth's stateless-vs-session-bound design) is only partially mitigated — a full fix risks reintroducing a session-loss bug this design was built to work around, so per the audit brief it is documented as a recommendation rather than auto-applied.

None of these findings point to an obvious cause for the prior Google Safe Browsing "Dangerous Site" flag (no malicious script/redirect/payload exists in the code). The most plausible code-level contributor is Finding 1 below (a public, unauthenticated diagnostic page); it's also common for InfinityFree's shared IP ranges to inherit reputation flags from other free-tier tenants unrelated to your code. See SECURITY_FIX_PLAN.md for the Search Console recommendation.

| # | Finding | Severity | Status |
|---|---------|----------|--------|
| 1 | `public/verify-deployment.php` publicly discloses DB host/name/user, `APP_SECRET` presence, and table row counts with no auth | **High** | ✅ Fixed |
| 2 | Instagram OAuth `state` is a stateless, session-independent bearer token (unlike Google/TikTok/YouTube) | **High** | ⚠️ Partially mitigated (expiry added); full fix documented, not auto-applied |
| 3 | Instagram OAuth token exchange logged plaintext long-lived access token to disk unconditionally | **Medium** | ✅ Fixed |
| 4 | Legacy, unrouted `app/` (App\* namespace) and root `routes.php` directly reachable over HTTP | **Low** | ✅ Fixed |

---

## Finding 1 — Unauthenticated deployment-diagnostics page discloses infrastructure & business data

- **Severity:** High
- **Affected file:** `public/verify-deployment.php`
- **Risk:** Sensitive information disclosure (OWASP A05:2021 – Security Misconfiguration)

### Technical explanation

This file has no authentication or IP restriction (unlike its sibling `public/setup.php`, which gates on `SETUP_ALLOWED_IPS`/localhost). Any anonymous visitor hitting `/verify-deployment.php` on the live site would see:
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` values
- Whether `APP_SECRET` is set and its exact character length
- Row counts for `users`, `posts`, `social_accounts`, `deals`, `invoices`, `notifications`, `job_queue` (business/growth data)
- Full PHP error output (`error_reporting(E_ALL); ini_set('display_errors', 1)` at the top of the file), which could leak file paths or stack traces on any failure

This is exactly the kind of endpoint that gets found by automated scanners and can itself contribute to a hosting reputation/Safe-Browsing flag.

### Fix applied

Gated the page behind the same IP-allowlist mechanism `public/setup.php` already uses (`SETUP_ALLOWED_IPS` env var / localhost default), returning HTTP 403 to disallowed visitors before any diagnostic output or error-display setting is executed. No functionality was removed — the page still works exactly as before for whoever configures their IP in `.env`, matching the existing, already-trusted pattern used by setup.php.

### Status: Fixed

---

## Finding 2 — Instagram OAuth `state` is a stateless bearer token, not bound to the initiating session

- **Severity:** High
- **Affected files:** `src/Controllers/InstagramOAuthController.php` (`buildState`/`verifyState`), `ig-cb.php` (duplicate callback handler)
- **Risk:** Cross-session OAuth state replay / forced account-linking (related to OWASP A01:2021 – Broken Access Control, and CSRF-class login/linking attacks)

### Technical explanation

Google, TikTok, and YouTube's connect flows all store the expected `state` value **in the initiating browser's session** (`session_set('google_auth_state', $state)`, etc.) and compare it against the session on callback — meaning only the same browser that started the flow can complete it.

Instagram's flow (both entry points — the routed `InstagramOAuthController::callbackHandler()` and the duplicate root-level `ig-cb.php`, which is the one actually configured via `INSTAGRAM_OAUTH_REDIRECT_URI` in `.env`) instead uses a **self-contained, HMAC-signed token** (`userId.nonce.hmac`) that is valid regardless of which browser/session presents it, and — critically — the callback calls `session_set_user($user)` using the `userId` embedded in the state, effectively **logging the requesting browser in as that user** purely on possession of a valid state string.

This means: a user who legitimately starts "Connect Instagram" and obtains a valid `state` for their own account could send the resulting Instagram authorize URL to a victim. If the victim completes Instagram's own consent screen (for their own real Instagram Business account), the callback fires with the attacker's `state` value + the victim's authorization `code`. The app would then (a) log the victim's browser in as the attacker's CreatorzHive account, and (b) link the victim's Instagram Business account (and its access token) to the attacker's account — because nothing ties the `state` to the session that initiated it.

### Why this wasn't blindly "fixed" to match the other three flows

The self-contained design (and the very existence of the duplicate `ig-cb.php` handler with extensive session-debug logging) strongly suggests this was a deliberate workaround for a session-loss problem specific to the Instagram redirect chain on InfinityFree — session-binding it like Google/TikTok/YouTube risks reintroducing that original bug. Per the audit brief's instruction to not auto-apply changes that risk behavior regressions, this was **not** converted to session-bound state.

### Fix applied (safe, non-breaking mitigation)

Added a signed issued-at timestamp to the state payload (`userId.nonce.timestamp.hmac`) with a 15-minute validity window, checked in **both** `InstagramOAuthController::verifyState()` and `ig-cb.php`'s inline verifier (kept in lock-step since they parse the same signed format). This doesn't change the happy path at all (a real OAuth round-trip completes in seconds) but sharply reduces the window in which a leaked/shared `state` value could be replayed.

### Recommended follow-up (not auto-applied — see SECURITY_FIX_PLAN.md)

Session-bind the state as a defense-in-depth layer while keeping the signed token as a fallback, and/or make the state single-use (delete/mark-consumed on first successful callback) via a small `job_queue`-style table, so a captured state can't be reused a second time even within the validity window.

### Status: Partially mitigated (expiry window added); deeper fix documented as a recommendation

---

## Finding 3 — Instagram token exchange logged plaintext long-lived access token to disk

- **Severity:** Medium
- **Affected file:** `src/Services/InstagramOAuthService.php` (`doCompleteConnection()`)
- **Risk:** Sensitive data exposure at rest (OWASP A02:2021 – Cryptographic Failures)

### Technical explanation

Every Instagram connection wrote the full token-exchange response — including the **long-lived access token in plaintext** (`exchange_data`) and a 30-character prefix of the short-lived token — to `backend/storage/logs/oauth-instagram-debug.json`, **unconditionally**, regardless of `APP_DEBUG`. `backend/` is blocked from direct web access by `.htaccess`, so this wasn't remotely readable in the current configuration, but it directly undermines the app's own `TokenCrypto`/encrypted-token-at-rest design by leaving a plaintext copy on disk indefinitely (the file is overwritten, not rotated, but persists between connections), and would become exposed if the `.htaccess` rule were ever misapplied or the host ignored it.

### Fix applied

Removed both unconditional `file_put_contents(...oauth-instagram-debug.json...)` calls. This is dead debugging code with no functional purpose — connection behavior, error handling, and return values are unchanged.

### Status: Fixed

---

## Finding 4 — Legacy, unrouted code reachable directly over HTTP

- **Severity:** Low
- **Affected paths:** `app/` (namespace `App\*` — an earlier, superseded procedural-router prototype), root `routes.php`
- **Risk:** Unnecessary attack surface (OWASP A05:2021 – Security Misconfiguration)

### Technical explanation

The live request path is `public/index.php` → `backend/index.php` → `backend/routes/{web,api}.php`, using the `CreatorzHive\*` (src/) namespace exclusively. `app/` and root `routes.php` are a dead, earlier iteration — confirmed via `composer.json` autoload entries and a repo-wide grep showing nothing in the live path references `App\*` or requires `routes.php`. However, because they're real files/directories, Apache serves them directly (bypassing the front-controller rewrite) if requested by URL — e.g. `GET /routes.php` would execute it.

Nothing catastrophic happens if these are hit directly (no destructive side effects were found), but there's no reason to leave a second, unmaintained code path reachable from the internet.

### Fix applied

Added `app` to the existing sensitive-path block list and a dedicated rule for `routes.php` in the root `.htaccess`, consistent with the existing `backend|database|vendor|scripts|tests` blocks. No files were deleted or renamed.

### Status: Fixed

---

## Areas reviewed with no confirmed findings

- **SQL Injection** — `src/Core/Database/Connection.php` uses PDO with `ATTR_EMULATE_PREPARES => false` and prepared statements throughout; the only `sprintf()`-built SQL is for table/column identifiers (backtick-quoted, never user-controlled) in the generic `insert()/update()/delete()` helpers. `ORDER BY`/sort-metric interpolation in `AnalyticsRepository`/`PostRepository` is constrained to fixed whitelists (`sortDirection()` only returns `ASC`/`DESC`; metric names come from a static lookup table), never raw user input.
- **XSS** — Server-side views escape via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`; client-side rendering consistently routes untrusted fields through a shared `Utils.escapeHtml()`/`esc()` helper before `innerHTML` assignment (sampled across `planner.js`, `admin-users.js`, `deals.js`, `settings.js`).
- **CSRF** — Every `POST` route in `backend/routes/api.php` carries the `csrf` middleware tag, validated via `hash_equals()` against a session-stored token; OAuth callbacks (GET, browser-redirect-driven) are correctly exempt as CSRF doesn't apply to them.
- **Session security** — `HttpOnly`, `SameSite=Lax`, configurable `Secure` (enabled in the live `.env`), session ID regeneration on login/logout, a UA-based fingerprint check, and a session-revocation feature (Settings → Security) are all in place.
- **Authentication/Authorization** — Every sensitive route carries `['auth']` and/or `['role:admin']`/`non_admin` middleware, enforced server-side (`RoleMiddleware`); `SettingsController::updateProfile()` only ever mutates the session's own `userId` and never accepts a `role` field from the client.
- **Google/TikTok/YouTube OAuth** — All three properly bind `state` (and, for TikTok, a PKCE `code_verifier`) to the session and validate with `hash_equals()`.
- **File uploads** — Server-side MIME sniffing via `finfo` against a fixed extension whitelist (never trusting the client-supplied MIME/filename), fully random destination filenames (no path traversal), 10MB cap, and `public/uploads/.htaccess` disables PHP execution and blocks dotfiles in that directory. Sampled uploaded files are genuine re-encoded JPEGs, not disguised executables.
- **File inclusion / path traversal** — No dynamic `include`/`require` paths built from user input; no file-serving endpoint accepts a raw filename/path parameter.
- **Dangerous PHP functions** — No `eval`, `exec`, `shell_exec`, `system`, `passthru`, `popen`, or `proc_open` calls outside legitimate `PDO::exec()` usage.
- **Malware / Safe Browsing sweep** — No obfuscated code, `base64_decode`/`gzinflate` payload chains, hidden iframes, unexpected external `<script src>` references, or web-shell-like files anywhere in the tree.
- **Composer dependencies** — `composer audit` reports no known vulnerability advisories for the two runtime/dev dependencies (`phpmailer/phpmailer` 6.12.0, `phpunit/phpunit` 9.6, dev-only).
- **Security headers** — `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, and `Referrer-Policy` are already set in `backend/index.php`. A Content-Security-Policy was deliberately **not** added — see SECURITY_FIX_PLAN.md for why and how to add one safely later.
