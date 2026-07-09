# CreatorzHive — Security Changelog

Companion to SECURITY_AUDIT_REPORT.md and SECURITY_FIX_PLAN.md. Every file touched in this security-hardening pass, the exact change, and the verification performed.

---

## `public/verify-deployment.php`

**Change:** Added an IP-allowlist gate (reusing `SETUP_ALLOWED_IPS` from `.env`, same pattern as `public/setup.php`) at the top of the file. Requests from disallowed IPs get HTTP 403 and a short message, before `error_reporting`/`display_errors` are touched and before any DB/env introspection runs.

**Security improvement:** Closes an unauthenticated information-disclosure endpoint that exposed `DB_HOST`/`DB_DATABASE`/`DB_USERNAME`, `APP_SECRET` presence + length, and row counts across `users`, `posts`, `social_accounts`, `deals`, `invoices`, `notifications`, `job_queue`.

**Verification:** `php -l public/verify-deployment.php` — no syntax errors. Logic mirrors `public/setup.php`'s already-deployed, working IP-gate.

---

## `src/Services/InstagramOAuthService.php` — REVERTED

**Change (applied then reverted):** Removed, then restored, two `file_put_contents()` calls inside `doCompleteConnection()` that write the full Instagram token-exchange payload (including the plaintext long-lived access token and a 30-character prefix of the short-lived token) to `backend/storage/logs/oauth-instagram-debug.json`.

**Why reverted:** This logging was added deliberately in a prior session (`705f6d0 Fix: Add OAuth exchange debug logging for Instagram`) to diagnose Instagram's token exchange on InfinityFree — active diagnostic tooling, not dead code. Removing it took away real troubleshooting capability for a fragile integration. Reverted via `git revert 53775bd` (commit `d62c1e8`); file is now byte-identical to before this audit touched it (`git diff` against the pre-audit commit is empty).

**Status:** Reverted. The plaintext-token-on-disk concern is still open — see SECURITY_FIX_PLAN.md item 9 for a way to address it without repeating this mistake (redact the token value, keep the rest of the diagnostic).

---

## `src/Controllers/InstagramOAuthController.php` — REVERTED

**Change (applied then reverted):** `buildState()`/`verifyState()` briefly embedded a signed issued-at timestamp in the `state` payload (`userId.nonce.issuedAt.hmac`, was `userId.nonce.hmac`) and rejected states older than a new `STATE_MAX_AGE` constant (900 seconds).

**Why reverted:** This state format (`userId.nonce.hmac`, no timestamp) was deliberately built in a prior session (`01b27a4`, `6b96c0a`) to make the Instagram OAuth flow session-independent, working around PHP workers not sharing session files across the OAuth redirect on InfinityFree shared hosting. Changing the format was not a safe, non-breaking change as this audit initially assessed it to be. Reverted via `git revert c229a6a` (commit `1663515`); file is now byte-identical to before this audit touched it.

**Status:** Reverted. Finding 2's underlying concern (no session binding, no expiry) remains open — see SECURITY_FIX_PLAN.md item 10 for a staged, test-before-trust approach.

---

## `ig-cb.php` — REVERTED

**Change (applied then reverted):** The inline `state` parser (this file independently re-implements state verification rather than reusing the controller) was updated to match the controller's new 4-part format and expiry check.

**Why reverted:** Same reason as the Controller change above — this file is the one actually configured as the live callback via `INSTAGRAM_OAUTH_REDIRECT_URI` in `.env`. Reverted in the same commit as the Controller (`1663515`); confirmed both files parse the original `userId.nonce.hmac` format identically again.

**Incident note:** Between the fix being applied and reverted, the user re-uploaded only the reverted `InstagramOAuthController.php` (which generates the old 3-part state) while this file on the live server still expected the new 4-part format — meaning every Instagram connection attempt failed with "Invalid OAuth state" until this file was also reverted locally and re-uploaded. This is why any future change to this OAuth flow must treat the Controller, Service, and this file as one atomic unit — see the note at the top of SECURITY_FIX_PLAN.md's "Recommended, not applied" section.

**Status:** Reverted.

---

## `.htaccess` (project root)

**Change:** Added `app` to the existing blocked-path pattern (`RewriteRule ^(backend|database|vendor|scripts|tests|app|\.git)(/|$) - [F,L,NC]`) and a new dedicated rule blocking `routes.php`.

**Security improvement:** Removes direct HTTP reachability of the dead, pre-OOP-migration `App\*` prototype and its root-level route table, reducing attack surface with zero effect on the live app (nothing in the current request path touches either).

**Verification:** Grepped the full live-path tree (`src/`, `backend/`, `public/`, `frontend/`, `webhook/`, `ig-cb.php`, `index.php`, `setup.php`) for any reference to `App\Controllers`, `App\Core`, or a `routes.php` require outside `backend/routes/` — none found.

---

## Files reviewed, not modified

Every other area in SECURITY_AUDIT_REPORT.md's "Areas reviewed with no confirmed findings" section (SQL layer, XSS escaping, CSRF middleware, session config, Google/TikTok/YouTube OAuth, file uploads, dependency versions, security headers) was inspected and found to already meet the relevant control — no changes made there to avoid touching working code without a confirmed defect.
