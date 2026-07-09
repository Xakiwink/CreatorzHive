# CreatorzHive — Security Changelog

Companion to SECURITY_AUDIT_REPORT.md and SECURITY_FIX_PLAN.md. Every file touched in this security-hardening pass, the exact change, and the verification performed.

---

## `public/verify-deployment.php`

**Change:** Added an IP-allowlist gate (reusing `SETUP_ALLOWED_IPS` from `.env`, same pattern as `public/setup.php`) at the top of the file. Requests from disallowed IPs get HTTP 403 and a short message, before `error_reporting`/`display_errors` are touched and before any DB/env introspection runs.

**Security improvement:** Closes an unauthenticated information-disclosure endpoint that exposed `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`, `APP_SECRET` presence + length, and row counts across `users`, `posts`, `social_accounts`, `deals`, `invoices`, `notifications`, `job_queue`.

**Verification:** `php -l public/verify-deployment.php` — no syntax errors. Logic mirrors `public/setup.php`'s already-deployed, working IP-gate.

---

## `src/Services/InstagramOAuthService.php`

**Change:** Removed two unconditional `file_put_contents()` calls inside `doCompleteConnection()` that wrote the full Instagram token-exchange payload (including the plaintext long-lived access token and a 30-character prefix of the short-lived token) to `backend/storage/logs/oauth-instagram-debug.json`. Removed the now-unused `use function storage_path;` import.

**Security improvement:** Eliminates a persistent plaintext-secret-on-disk exposure that bypassed the app's own token-at-rest encryption (`TokenCrypto`).

**Verification:** `php -l src/Services/InstagramOAuthService.php` — no syntax errors. `tests/unit/InstagramOAuthTest.php` does not exercise `doCompleteConnection()`'s logging (only `authorizeUrl()`), so no test changes were needed. Confirmed no other code reads `oauth-instagram-debug.json` (grepped repo-wide).

---

## `src/Controllers/InstagramOAuthController.php`

**Change:** `buildState()`/`verifyState()` now embed a signed issued-at timestamp in the `state` payload (`userId.nonce.issuedAt.hmac`, was `userId.nonce.hmac`) and reject states older than a new `STATE_MAX_AGE` constant (900 seconds / 15 minutes).

**Security improvement:** Bounds the replay window for the Instagram OAuth `state` token (see SECURITY_AUDIT_REPORT.md Finding 2). Does not change happy-path behavior — real connections complete in well under 15 minutes.

**Verification:** `php -l` clean. `tests/unit/InstagramOAuthTest.php` only asserts on `authorizeUrl()` output (the opaque `state` string it's given, not its internal format), so it's unaffected.

---

## `ig-cb.php`

**Change:** Updated the inline `state` parser (this file independently re-implements state verification rather than reusing the controller) to match the new 4-part format and expiry check, referencing `InstagramOAuthController::STATE_MAX_AGE` so both verifiers can't drift out of sync.

**Security improvement:** Keeps this file — the one actually configured as the live callback via `INSTAGRAM_OAUTH_REDIRECT_URI` in `.env` — consistent with the hardened verification in the controller class.

**Verification:** `php -l ig-cb.php` — no syntax errors. Confirmed the class reference resolves (`namespace CreatorzHive\Controllers` in the source file, referenced fully-qualified from the global-namespace script, after `vendor/autoload.php` is required earlier in the same file).

---

## `.htaccess` (project root)

**Change:** Added `app` to the existing blocked-path pattern (`RewriteRule ^(backend|database|vendor|scripts|tests|app|\.git)(/|$) - [F,L,NC]`) and a new dedicated rule blocking `routes.php`.

**Security improvement:** Removes direct HTTP reachability of the dead, pre-OOP-migration `App\*` prototype and its root-level route table, reducing attack surface with zero effect on the live app (nothing in the current request path touches either).

**Verification:** Grepped the full live-path tree (`src/`, `backend/`, `public/`, `frontend/`, `webhook/`, `ig-cb.php`, `index.php`, `setup.php`) for any reference to `App\Controllers`, `App\Core`, or a `routes.php` require outside `backend/routes/` — none found.

---

## Files reviewed, not modified

Every other area in SECURITY_AUDIT_REPORT.md's "Areas reviewed with no confirmed findings" section (SQL layer, XSS escaping, CSRF middleware, session config, Google/TikTok/YouTube OAuth, file uploads, dependency versions, security headers) was inspected and found to already meet the relevant control — no changes made there to avoid touching working code without a confirmed defect.
