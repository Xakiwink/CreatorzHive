# CreatorzHive — Security Fix Plan

Companion to SECURITY_AUDIT_REPORT.md. Lists every planned fix, why it's required, the files touched, and expected impact. Fixes are grouped into **Applied** (done in this pass, zero behavior change to legitimate users) and **Recommended** (documented per the audit brief's instruction not to auto-apply anything that risks changing behavior).

---

## Applied fixes

### 1. IP-gate `public/verify-deployment.php`

- **Why:** Publicly readable by anyone on the internet; discloses DB host/name/user, `APP_SECRET` presence+length, and business-data row counts (Finding 1).
- **Files:** `public/verify-deployment.php`
- **Change:** Added the same `SETUP_ALLOWED_IPS`-based allowlist check `public/setup.php` already uses, returning HTTP 403 before any diagnostic logic or `display_errors` toggle runs.
- **Expected impact:** None for legitimate use — once you add your IP to `SETUP_ALLOWED_IPS` in `.env` (see deployment instructions below), the page behaves exactly as before. Anonymous visitors now get a 403 instead of your infrastructure details.

### 2. Remove unconditional plaintext OAuth token logging

- **Why:** Long-lived Instagram access tokens were written to `backend/storage/logs/oauth-instagram-debug.json` in plaintext on every connection, regardless of `APP_DEBUG` (Finding 3).
- **Files:** `src/Services/InstagramOAuthService.php`
- **Change:** Deleted both `file_put_contents(...)` debug-log blocks (and the now-unused `storage_path` import).
- **Expected impact:** None — this was dead diagnostic code; the connection flow, return values, and error handling are unchanged. The separate `igCbDebug()` helper in `ig-cb.php` (which never logged token values, only step/session metadata, and only fires when `APP_DEBUG=true`) was left in place since it's still useful for your documented "set APP_DEBUG=true to diagnose 500s" workflow and doesn't leak secrets.

### 3. Add an expiry window to the Instagram OAuth `state` token

- **Why:** The signed `state` token had no expiry, so a captured/leaked value would remain valid indefinitely (part of Finding 2).
- **Files:** `src/Controllers/InstagramOAuthController.php`, `ig-cb.php`
- **Change:** Payload format changed from `userId.nonce.hmac` to `userId.nonce.issuedAt.hmac`; both verifiers (the routed controller and the duplicate `ig-cb.php` handler, which must stay in sync since they parse the same string independently) now reject states older than 15 minutes (`InstagramOAuthController::STATE_MAX_AGE`).
- **Expected impact:** None for real users — a genuine OAuth round-trip (click connect → approve on Instagram → redirect back) completes in seconds to low minutes, well inside the 15-minute window.

### 4. Block direct HTTP access to unrouted legacy code

- **Why:** `app/` (old `App\*` prototype) and root `routes.php` are not part of the live request path but are directly reachable over HTTP since Apache serves real files before the rewrite-to-front-controller rule applies (Finding 4).
- **Files:** root `.htaccess`
- **Change:** Added `app` to the existing `RewriteRule ^(backend|database|vendor|scripts|tests|...)(/|$) - [F,L,NC]` block list, and a dedicated block rule for `routes.php`.
- **Expected impact:** None — nothing in the live app requests these paths (verified via a repo-wide grep for `App\Controllers`, `App\Core`, and `routes.php` requires outside `backend/routes/`).

---

## Recommended, not auto-applied

### 5. Session-bind the Instagram OAuth `state` (defense in depth)

- **Why:** Even with the expiry window from fix #3, the Instagram flow's `state` is still valid from *any* browser/session that presents it (unlike Google/TikTok/YouTube). This is the residual part of Finding 2.
- **Risk of auto-applying:** The self-contained design appears to be a deliberate workaround for a session-persistence bug across the Instagram redirect chain on InfinityFree (evidenced by the duplicate `ig-cb.php` handler and its extensive `session was NOT written!` debug diagnostics). Session-binding it the same way Google's flow does could silently break real Instagram connections in production if that original session-loss issue is still live, and this can't be verified without testing against the actual InfinityFree environment.
- **Suggested approach when you're ready to test it:**
  1. At `connectStart()`, also store `session_set('instagram_oauth_user_id', $userId)` (mirroring the TikTok/YouTube pattern).
  2. In the callback, prefer the session value when present; only fall back to the signed `state` payload if the session value is missing (preserving today's behavior for the case that motivated the workaround).
  3. Deploy to a staging subdomain or a low-traffic window first and confirm connections still complete, watching for the same `session_id`/`db_session` diagnostics `ig-cb.php` already logs when `APP_DEBUG=true`.

### 6. Make the OAuth `state` single-use

- **Why:** Right now a captured `state` can be replayed multiple times within its 15-minute window.
- **Risk of auto-applying:** Requires a new storage mechanism (a small table or reuse of `job_queue`-style storage) to track consumed nonces — a schema addition, which the audit brief asks to avoid unless clearly necessary. Given the expiry window added in fix #3 plus the fact that `state` is never displayed/logged anywhere reachable, this is a lower-priority hardening step, not an urgent one.
- **Suggested approach:** Add a `oauth_state_nonces` table (nonce, expires_at) and mark-consumed-on-first-use, or simply accept the residual risk given the short expiry window.

### 7. Content-Security-Policy header

- **Why:** No CSP is currently set. A carefully scoped one adds defense-in-depth against any future XSS regression.
- **Risk of auto-applying:** Google Sign-In and Instagram's OAuth redirect/consent flow both involve cross-origin navigation and (for Google) an origin-trial-style postMessage handshake in some configurations; a wrong `frame-ancestors`/`connect-src`/`script-src` value can silently break sign-in with no obvious error. The audit brief explicitly says not to introduce a CSP that risks breaking these flows.
- **Suggested approach:** Add a **report-only** CSP first (`Content-Security-Policy-Report-Only`) for a week or two, review violation reports, then promote to enforcing once you've confirmed it doesn't touch the Google/Instagram/TikTok/YouTube OAuth domains or any inline scripts the app relies on.

### 8. Investigate the prior Google Safe Browsing flag directly

- **Why:** The audit found no malware, backdoor, or malicious redirect in the codebase, so the flag likely predates this code, was caused by the now-fixed Finding 1 exposure, or is inherited from shared IP/domain reputation on InfinityFree's free tier (common for free hosts).
- **Suggested approach:** Use Google Search Console → Security Issues on the `creatorzhive.infinityfree.io` property to see the exact reason Google cited, and file a reconsideration request once Finding 1's fix has been deployed for a few days.

---

## Deployment note for fix #1

`SETUP_ALLOWED_IPS` in the live `.env` is currently empty, meaning both `setup.php` and (as of this fix) `verify-deployment.php` only allow `127.0.0.1`/`::1` — i.e., **nobody** can reach either page remotely right now, including you. If you need to use `verify-deployment.php` again, temporarily set `SETUP_ALLOWED_IPS=<your IP>` (or `SETUP_ALLOWED_IPS=*` briefly, then remove it) in `.env`, matching the existing instructions already printed on the `setup.php` "Access Denied" screen.
