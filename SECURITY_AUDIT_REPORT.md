# CreatorzHive — Security Audit Report

**Scope:** Full codebase (183 PHP files across `src/`, `backend/`, `public/`, `frontend/`, root-level entry scripts).
**Method:** Manual review — live request-path tracing, SQL query audit, OAuth flow tracing, output-escaping sampling, `.htaccess`/header review, dependency audit (`composer audit`), malware/backdoor sweep.
**Constraint:** No architecture, routing, UI, or business-logic changes. Only confirmed vulnerabilities were fixed; risky changes are documented as recommendations only (see SECURITY_FIX_PLAN.md).

## Executive Summary

The codebase is in noticeably good shape for a hand-rolled procedural/OOP hybrid PHP app: **all SQL access goes through a single PDO wrapper using prepared statements** (no string-concatenated queries were found anywhere), **CSRF protection is applied consistently to every state-changing POST route**, **output escaping (`htmlspecialchars` server-side, a shared `escapeHtml()` helper client-side) is applied consistently** everywhere sampled, session cookies are `HttpOnly`/`SameSite=Lax` with a configurable `Secure` flag, and passwords are hashed with bcrypt (cost 12). **No malware, web shells, obfuscated payloads, or backdoors were found** — the oddly-named `tiktokzewzmpsjzQhD8YOLrokyLMUavbPvshxz.txt` at the project root is a legitimate TikTok domain-verification file, not malicious.

Four confirmed issues were found; two required changing routing, UI, or business logic outcomes for the Instagram integration in ways this audit initially got wrong (see the correction note below Finding 2 and Finding 3) and were reverted at the user's request. The other two (Findings 1 and 4) required no functional change and remain fixed.

**Correction (post-review):** Findings 2 and 3 below were initially "fixed" by this audit, but those fixes were reverted after deployment because they broke a working production integration. Both the Instagram OAuth state design (Finding 2) and its debug logging (Finding 3) turned out to be **deliberate fixes from a prior session** (see git history `01b27a4`, `6b96c0a`, `705f6d0`) for a real InfinityFree hosting constraint — PHP workers not sharing session files across the OAuth redirect — not oversights. This audit did not check recent commit history closely enough before changing them. They are documented below as-found, with their status corrected to **Reverted**, and the security concern each raised is carried forward as a recommendation only, to be implemented carefully (if at all) with the working integration in mind. See SECURITY_FIX_PLAN.md and SECURITY_CHANGELOG.md for the full correction.

None of these findings point to an obvious cause for the prior Google Safe Browsing "Dangerous Site" flag (no malicious script/redirect/payload exists in the code). The most plausible code-level contributor is Finding 1 below (a public, unauthenticated diagnostic page); it's also common for InfinityFree's shared IP ranges to inherit reputation flags from other free-tier tenants unrelated to your code. See SECURITY_FIX_PLAN.md for the Search Console recommendation.

| # | Finding | Severity | Status |
|---|---------|----------|--------|
| 1 | `public/verify-deployment.php` publicly discloses DB host/name/user, `APP_SECRET` presence, and table row counts with no auth | **High** | ✅ Fixed (deleted) |
| 2 | Instagram OAuth `state` is a stateless, session-independent bearer token (unlike Google/TikTok/YouTube) | **High** | ↩️ Reverted — deliberate design, not a bug; see correction above |
| 3 | Instagram OAuth token exchange logged plaintext long-lived access token to disk unconditionally | **Medium** | ↩️ Reverted — deliberate diagnostic tooling, not leftover debug code; see correction above |
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

First gated the page behind the same IP-allowlist mechanism `public/setup.php` already uses. While verifying that fix, the user found the page also fatals with an uncaught `Error: Call to undefined function env()` — the "Environment & Configuration" section calls `env()` before anything loads that helper (`backend/index.php`, which defines it, isn't required until a later section). A second, more serious bug was found while investigating: that later `require_once .../backend/index.php` runs the full front controller including `router_dispatch()`, which — since no route is registered for `verify-deployment.php` — ends by calling `response_html(...); exit;` on a 404. That silently truncates the page after Section 1 every time, meaning the DB/Application/Security/Job/Features checks have likely never actually run for anyone.

Given the page had two independent, pre-existing functional bugs on top of the disclosure issue and was already documented as a one-time, delete-after-use tool, the user chose deletion over a repair. **The file has been removed** (`git rm public/verify-deployment.php`) rather than patched.

### Status: Fixed (deleted)

---

## Finding 2 — Instagram OAuth `state` is a stateless bearer token, not bound to the initiating session

- **Severity:** High
- **Affected files:** `src/Controllers/InstagramOAuthController.php` (`buildState`/`verifyState`), `ig-cb.php` (duplicate callback handler)
- **Risk:** Cross-session OAuth state replay / forced account-linking (related to OWASP A01:2021 – Broken Access Control, and CSRF-class login/linking attacks)

### Technical explanation

Google, TikTok, and YouTube's connect flows all store the expected `state` value **in the initiating browser's session** (`session_set('google_auth_state', $state)`, etc.) and compare it against the session on callback — meaning only the same browser that started the flow can complete it.

Instagram's flow (both entry points — the routed `InstagramOAuthController::callbackHandler()` and the duplicate root-level `ig-cb.php`, which is the one actually configured via `INSTAGRAM_OAUTH_REDIRECT_URI` in `.env`) instead uses a **self-contained, HMAC-signed token** (`userId.nonce.hmac`) that is valid regardless of which browser/session presents it, and — critically — the callback calls `session_set_user($user)` using the `userId` embedded in the state, effectively **logging the requesting browser in as that user** purely on possession of a valid state string.

This means: a user who legitimately starts "Connect Instagram" and obtains a valid `state` for their own account could send the resulting Instagram authorize URL to a victim. If the victim completes Instagram's own consent screen (for their own real Instagram Business account), the callback fires with the attacker's `state` value + the victim's authorization `code`. The app would then (a) log the victim's browser in as the attacker's CreatorzHive account, and (b) link the victim's Instagram Business account (and its access token) to the attacker's account — because nothing ties the `state` to the session that initiated it.

### Why this wasn't blindly "fixed" to match the other three flows — and what happened when it was

The self-contained design (and the very existence of the duplicate `ig-cb.php` handler with extensive session-debug logging) strongly suggested this was a deliberate workaround for a session-loss problem specific to the Instagram redirect chain on InfinityFree. That suspicion was correct: git history (`01b27a4 Fix: Make Instagram OAuth session-independent, fix fingerprint on shared hosting`, `6b96c0a Fix: Three-part Instagram OAuth session fix`) confirms this exact three-part `state` format was built, deliberately, to work around **PHP workers not sharing session files across the OAuth round-trip on InfinityFree shared hosting**.

This audit initially applied a "safe, non-breaking" mitigation anyway — adding a signed issued-at timestamp to the state payload (`userId.nonce.timestamp.hmac`, a fourth part) with a 15-minute validity window, updated in both `InstagramOAuthController::verifyState()` and `ig-cb.php`'s independent inline verifier. This was deployed, but the user only re-uploaded the reverted `InstagramOAuthController.php` (not `ig-cb.php`, which is the file actually wired up as the live callback via `INSTAGRAM_OAUTH_REDIRECT_URI`), so for a period the two verifiers disagreed on the state format and every Instagram connection attempt failed with "Invalid OAuth state." **All three files have since been reverted to the exact pre-audit state** (confirmed via `git diff` against the pre-audit commit — no remaining differences).

### Recommended follow-up (not applied — see SECURITY_FIX_PLAN.md)

The underlying concern (a signed `state` with no expiry, valid across sessions) is real, but any fix must be tested against the actual InfinityFree environment before being trusted, given how narrowly the original session-loss bug was worked around. See SECURITY_FIX_PLAN.md for a staged approach.

### Status: Reverted — not currently mitigated. Treat any future change here as requiring live testing on InfinityFree before considering it done, not just a local `php -l` check.

---

## Finding 3 — Instagram token exchange logged plaintext long-lived access token to disk

- **Severity:** Medium
- **Affected file:** `src/Services/InstagramOAuthService.php` (`doCompleteConnection()`)
- **Risk:** Sensitive data exposure at rest (OWASP A02:2021 – Cryptographic Failures)

### Technical explanation

Every Instagram connection wrote the full token-exchange response — including the **long-lived access token in plaintext** (`exchange_data`) and a 30-character prefix of the short-lived token — to `backend/storage/logs/oauth-instagram-debug.json`, **unconditionally**, regardless of `APP_DEBUG`. `backend/` is blocked from direct web access by `.htaccess`, so this wasn't remotely readable in the current configuration, but it directly undermines the app's own `TokenCrypto`/encrypted-token-at-rest design by leaving a plaintext copy on disk indefinitely (the file is overwritten, not rotated, but persists between connections), and would become exposed if the `.htaccess` rule were ever misapplied or the host ignored it.

### Correction — this was not dead debugging code

This audit initially removed both `file_put_contents(...oauth-instagram-debug.json...)` calls, on the assumption they were leftover debug cruft with no functional purpose. That was wrong: git history (`705f6d0 Fix: Add OAuth exchange debug logging for Instagram`) shows this logging was added deliberately, in the same debugging session that produced the working OAuth design in Finding 2, to diagnose Instagram's token-exchange behavior. It's active diagnostic tooling for a fragile, still-monitored integration, not dead code. It has been restored in full.

### Recommended follow-up (not applied — see SECURITY_FIX_PLAN.md)

The plaintext-token-on-disk concern is still valid and worth addressing, but *without* removing the diagnostic capability — e.g. redact the token value while keeping the rest of the diagnostic fields, or gate the token-containing fields specifically behind `APP_DEBUG`. This needs sign-off before being applied again, given what happened the first time.

### Status: Reverted — logging restored as-is; the data-exposure concern remains open, see SECURITY_FIX_PLAN.md.

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
