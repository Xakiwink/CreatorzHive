# CreatorzHive — Security Fix Plan

Companion to SECURITY_AUDIT_REPORT.md. Lists every planned fix, why it's required, the files touched, and expected impact. Fixes are grouped into **Applied** (done in this pass, zero behavior change to legitimate users) and **Recommended** (documented per the audit brief's instruction not to auto-apply anything that risks changing behavior).

---

## Applied fixes

### 1. IP-gate `public/verify-deployment.php`

- **Why:** Publicly readable by anyone on the internet; discloses DB host/name/user, `APP_SECRET` presence+length, and business-data row counts (Finding 1).
- **Files:** `public/verify-deployment.php`
- **Change:** Added the same `SETUP_ALLOWED_IPS`-based allowlist check `public/setup.php` already uses, returning HTTP 403 before any diagnostic logic or `display_errors` toggle runs.
- **Expected impact:** None for legitimate use — once you add your IP to `SETUP_ALLOWED_IPS` in `.env` (see deployment instructions below), the page behaves exactly as before. Anonymous visitors now get a 403 instead of your infrastructure details.

### 2. ~~Remove unconditional plaintext OAuth token logging~~ — REVERTED

- **Why it was attempted:** Long-lived Instagram access tokens were written to `backend/storage/logs/oauth-instagram-debug.json` in plaintext on every connection, regardless of `APP_DEBUG` (Finding 3).
- **Files:** `src/Services/InstagramOAuthService.php`
- **What actually happened:** This was assumed to be dead debug code and removed. It was not dead code — `705f6d0 Fix: Add OAuth exchange debug logging for Instagram` shows it was added deliberately in a prior session to diagnose Instagram's token exchange on InfinityFree, and is active diagnostic tooling for a fragile integration. **Reverted in full** at the user's request. See item 9 below for how to address the underlying disclosure concern without repeating this mistake.

### 3. ~~Add an expiry window to the Instagram OAuth `state` token~~ — REVERTED

- **Why it was attempted:** The signed `state` token had no expiry, so a captured/leaked value would remain valid indefinitely (part of Finding 2).
- **Files:** `src/Controllers/InstagramOAuthController.php`, `ig-cb.php`
- **What actually happened:** The payload format was changed from `userId.nonce.hmac` to `userId.nonce.issuedAt.hmac` in both verifiers. This directly modified a state format (`01b27a4`, `6b96c0a`) that was deliberately built session-independent to work around PHP workers not sharing session files across the OAuth redirect on InfinityFree shared hosting — a real, already-solved production constraint, not an oversight. Worse, the user's re-upload only replaced `InstagramOAuthController.php` (not `ig-cb.php`, the file actually wired up as the live callback), so the two verifiers briefly disagreed on state format and **every Instagram connection failed** with "Invalid OAuth state" — reproducing the exact class of problem the original fix solved. **Reverted in full** (all three files — Controller, Service, and `ig-cb.php` — confirmed via `git diff` to exactly match the pre-audit commit). See item 10 below for how this could be revisited safely.

### 4. Block direct HTTP access to unrouted legacy code

- **Why:** `app/` (old `App\*` prototype) and root `routes.php` are not part of the live request path but are directly reachable over HTTP since Apache serves real files before the rewrite-to-front-controller rule applies (Finding 4).
- **Files:** root `.htaccess`
- **Change:** Added `app` to the existing `RewriteRule ^(backend|database|vendor|scripts|tests|...)(/|$) - [F,L,NC]` block list, and a dedicated block rule for `routes.php`.
- **Expected impact:** None — nothing in the live app requests these paths (verified via a repo-wide grep for `App\Controllers`, `App\Core`, and `routes.php` requires outside `backend/routes/`).

---

## Recommended, not applied

**Note on items 9 and 10:** these both touch the Instagram OAuth integration that broke once already this session. Neither should be attempted again without (a) re-reading `01b27a4`, `6b96c0a`, `705f6d0`, and `25d6eaf` in full first, (b) changing `InstagramOAuthController.php`, `InstagramOAuthService.php`, and `ig-cb.php` together in the same commit if the state format changes at all, and (c) a real end-to-end test against the live InfinityFree deployment (not just `php -l`) before considering it done.

### 9. Redact tokens from the Instagram debug log without removing it

- **Why:** `oauth-instagram-debug.json` still writes the plaintext long-lived access token and a 30-character prefix of the short-lived token (Finding 3's original concern is unresolved — logging was restored as-is).
- **Why not just delete it again:** It's actively used to diagnose a real, fragile integration; item 2 above already demonstrated the cost of removing it outright.
- **Suggested approach:** Keep every field except the raw token values — replace `short_token_pre` and the `access_token` inside `exchange_data` with a hash or `strlen()` only (both already logged). This preserves 100% of the diagnostic value (lengths, success/failure, HTTP status, the rest of the exchange response) while dropping the one field that's an actual secret.

### 10. Session-bind the Instagram OAuth `state` (defense in depth)

- **Why:** The Instagram flow's `state` is a signed bearer token valid from *any* browser/session that presents it (unlike Google/TikTok/YouTube), with no expiry (Finding 2, currently unmitigated).
- **Risk:** This exact design was built deliberately (`01b27a4`, `6b96c0a`) to work around PHP workers not sharing session files across the OAuth redirect on InfinityFree. Session-binding it, or even just adding an expiry, is not guaranteed safe without live testing — this session proved that changing it, even "safely," can break production Instagram connections.
- **Suggested approach when you're ready to test it:**
  1. At `connectStart()`, also store `session_set('instagram_oauth_user_id', $userId)` (mirroring the TikTok/YouTube pattern) — but treat it as optional/advisory, not authoritative.
  2. In the callback, prefer the session value when present and it agrees with the signed state; fall back to the signed `state` payload alone when the session value is missing (preserving today's behavior for the case that motivated the original workaround).
  3. Deploy to a staging subdomain or a low-traffic window first and confirm connections still complete, watching the same `session_id`/`db_session` diagnostics `ig-cb.php` already logs when `APP_DEBUG=true` (this is exactly the tooling item 9 above preserves).
  4. Only after confirming real connections succeed, consider adding an expiry window on top.

### 11. Content-Security-Policy header

- **Why:** No CSP is currently set. A carefully scoped one adds defense-in-depth against any future XSS regression.
- **Risk of auto-applying:** Google Sign-In and Instagram's OAuth redirect/consent flow both involve cross-origin navigation and (for Google) an origin-trial-style postMessage handshake in some configurations; a wrong `frame-ancestors`/`connect-src`/`script-src` value can silently break sign-in with no obvious error. The audit brief explicitly says not to introduce a CSP that risks breaking these flows.
- **Suggested approach:** Add a **report-only** CSP first (`Content-Security-Policy-Report-Only`) for a week or two, review violation reports, then promote to enforcing once you've confirmed it doesn't touch the Google/Instagram/TikTok/YouTube OAuth domains or any inline scripts the app relies on.

### 12. Investigate the prior Google Safe Browsing flag directly

- **Why:** The audit found no malware, backdoor, or malicious redirect in the codebase, so the flag likely predates this code, was caused by the now-fixed Finding 1 exposure, or is inherited from shared IP/domain reputation on InfinityFree's free tier (common for free hosts).
- **Suggested approach:** Use Google Search Console → Security Issues on the `creatorzhive.infinityfree.io` property to see the exact reason Google cited, and file a reconsideration request once Finding 1's fix has been deployed for a few days.

---

## Deployment note for fix #1

`SETUP_ALLOWED_IPS` in the live `.env` is currently empty, meaning both `setup.php` and (as of this fix) `verify-deployment.php` only allow `127.0.0.1`/`::1` — i.e., **nobody** can reach either page remotely right now, including you. If you need to use `verify-deployment.php` again, temporarily set `SETUP_ALLOWED_IPS=<your IP>` (or `SETUP_ALLOWED_IPS=*` briefly, then remove it) in `.env`, matching the existing instructions already printed on the `setup.php` "Access Denied" screen.
