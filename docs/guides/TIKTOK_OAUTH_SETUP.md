# TikTok OAuth Setup Guide

## Overview

TikTok uses its own OAuth 2.0 implementation ("Login Kit") which — unlike Google/Meta — **requires PKCE** (a `code_verifier` / `code_challenge` pair) on every authorization request. Access tokens expire every **24 hours**; the app automatically refreshes them using the stored refresh token (valid up to 365 days). Publishing goes through TikTok's Content Posting API.

Before this, TikTok only supported a single, admin-entered static access token (for testing/publishing as one account). Creators can now connect their own TikTok account via OAuth, the same way Instagram and YouTube work.

### Why this guide uses a second domain, not creatorzhive.infinityfree.io directly

TikTok requires **domain ownership verification** before it will accept a redirect URI or the privacy/terms URLs. It offers two methods, and **both are blocked on InfinityFree's free tier**:

- **URL prefix + signature file** — TikTok's verifier fetches the file with a plain server-side HTTP request. InfinityFree's free-tier edge serves a JavaScript "anti-bot" challenge page to any request that doesn't already have a session cookie (confirmed by testing — `curl` and TikTok's own verifier both get the JS challenge stub instead of the real file, never the actual content).
- **Domain + DNS TXT record** — InfinityFree's free DNS Zone Editor for `*.infinityfree.io` subdomains only supports A (root/`www` only), CNAME (subdomains), MX, and SPF — no arbitrary TXT record, which is what TikTok's verification string needs.

The fix: point a domain you have **real DNS control** over (via Cloudflare's free plan, or any registrar) at the same InfinityFree hosting, and use that domain for TikTok's app registration instead. Full DNS control means TXT verification just works.

**No code changes are needed for this** — `TiktokOAuthService::redirectUri()` already checks a `TIKTOK_OAUTH_REDIRECT_URI` env override before falling back to the primary `APP_URL`. And because the app builds all internal links as relative paths (`route_url()` / `routeQuery()` return `/?route=...`, never an absolute `APP_URL`-based link), there's no session-cookie conflict — as long as a creator starts the "Connect TikTok" click from the new domain, the whole connect → TikTok → callback round trip stays on that one domain.

---

## Step 1 — Get a domain with full DNS control

1. Register a domain (a cheap ~$1–10/yr domain from any registrar works; you don't need anything fancy)
2. Add it to [Cloudflare](https://dash.cloudflare.com) (free plan) and switch its nameservers to Cloudflare's
3. In Cloudflare DNS, add an **A record** pointing to this site's IP:
   ```
   Type: A
   Name: @  (or a subdomain, e.g. app)
   Value: 185.27.134.164
   Proxy status: DNS only (grey cloud) — a proxied orange-cloud record can interfere with InfinityFree's own SSL cert issuance
   ```
4. In your **InfinityFree control panel** → **Addon domains** (or **Subdomains**, depending on panel version) → add this new domain so InfinityFree serves the same `/htdocs/` content for it
5. Wait for DNS propagation and for InfinityFree to issue a certificate for the new domain (can take up to a few hours)
6. Confirm it works: visit `https://yournewdomain.com/` in a browser and see the same CreatorzHive login page

---

## Step 2 — TikTok for Developers

### 2.1 Create the app

1. Go to [developers.tiktok.com](https://developers.tiktok.com) → **Manage apps** → **Create an app**
2. Fill in app name, description, category
3. Under **Platform**, add **Web** and enter your new domain:
   ```
   https://yournewdomain.com
   ```
4. Set **Privacy Policy URL** and **Terms of Service URL** to:
   ```
   https://yournewdomain.com/?route=privacy-policy
   https://yournewdomain.com/?route=terms-of-service
   ```

### 2.2 Verify domain ownership (DNS TXT record)

1. In the TikTok portal, choose **Add property → Domain → DNS record**
2. Copy the **Host** and **TXT value** it gives you
3. In Cloudflare DNS (not InfinityFree's DNS editor), add:
   ```
   Type: TXT
   Name: (as given by TikTok, often @ or a specific subdomain)
   Value: (the exact string TikTok gave you)
   ```
4. Wait a few minutes for DNS propagation, then click **Verify** in the TikTok portal

### 2.3 Add products

On the app's **Products** tab, add:
- **Login Kit** — provides `client_key` / `client_secret` and the `user.info.basic` / `user.info.stats` scopes
- **Content Posting API** — provides `video.publish` (or `video.upload` if publish isn't approved yet)

### 2.4 Configure the redirect URI

Under **Login Kit → Configure**, add this exact redirect URI (note: the new domain, not `creatorzhive.infinityfree.io`):
```
https://yournewdomain.com/?route=tiktok-callback
```
It must match byte-for-byte or TikTok will reject the callback.

### 2.5 Add yourself as a tester (sandbox / unaudited mode)

New apps start in **sandbox mode** — only accounts added as testers can complete OAuth or see real stats:

1. **App → Manage users → Add users**
2. Add your TikTok account (and any other creator accounts you want to test with)

> Until TikTok reviews and approves the app for production, only added testers can connect, and some scopes (e.g. `video.publish`) may silently fall back to draft/inbox-only posting.

### 2.6 Copy your credentials

From the app's **Basic information** tab, copy:
- **Client key**
- **Client secret**

---

## Step 3 — Upload Files to InfinityFree

| Local path | Remote path |
|---|---|
| `src/Services/TiktokOAuthService.php` | `/htdocs/src/Services/TiktokOAuthService.php` |
| `src/Controllers/TiktokOAuthController.php` | `/htdocs/src/Controllers/TiktokOAuthController.php` |
| `src/Providers/AppServiceProvider.php` | `/htdocs/src/Providers/AppServiceProvider.php` |
| `src/Controllers/SettingsController.php` | `/htdocs/src/Controllers/SettingsController.php` |
| `src/Jobs/FetchAnalyticsJob.php` | `/htdocs/src/Jobs/FetchAnalyticsJob.php` |
| `src/Services/PlatformApiSecretsService.php` | `/htdocs/src/Services/PlatformApiSecretsService.php` |
| `backend/routes/web.php` | `/htdocs/backend/routes/web.php` |
| `backend/compat/services.php` | `/htdocs/backend/compat/services.php` |

---

## Step 4 — Enter Credentials

Two values need to go in `.env` on the server (FTP-edit `.env` directly, since the redirect URI override isn't something the admin credentials UI exposes):

```
TIKTOK_OAUTH_REDIRECT_URI=https://yournewdomain.com/?route=tiktok-callback
```

The client key/secret can go either in `.env` or via the admin UI:

**Option A — Admin UI (no FTP edit needed):**
1. Log in as **admin** → **Settings → Integrations**
2. Find the **TikTok** credentials group
3. Enter **TikTok client key** and **TikTok client secret** (from Step 2.6) → Save

**Option B — `.env` directly:**
```
TIKTOK_CLIENT_KEY=...
TIKTOK_CLIENT_SECRET=...
```

The app checks `.env` first, then falls back to the saved admin value, for both fields.

---

## Step 5 — Connect TikTok

**Important:** start this from the **new domain**, not `creatorzhive.infinityfree.io` — that's what's registered as TikTok's redirect URI, and the OAuth state/PKCE verifier is stored in a session cookie scoped to whichever domain you click "Connect" from.

1. Log in as a **creator** account (not admin) via `https://yournewdomain.com`
2. Go to **Settings → Integrations**
3. Click **Connect** on TikTok
4. TikTok's consent screen appears — approve access
5. You're redirected back to Settings with "TikTok account connected successfully"

---

## Step 6 — Verify the Connection

There's no dedicated `test_tiktok` diagnostic yet (unlike YouTube/Instagram), but you can confirm it's working with the generic endpoints:

**1. Force a fresh analytics fetch for all connected accounts:**
```
https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=YOUR_WEBHOOK_SECRET&refresh_analytics=1
```

**2. Process the job queue:**
```
https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=YOUR_WEBHOOK_SECRET
```

**3. Check the dashboard** — the TikTok card should now show your real follower count instead of seed/fake data.

You can also confirm the connection status on **Settings → Integrations** (Admin view shows `connected_accounts` and `expiring_soon` per platform).

> Note: the same anti-bot JS challenge that blocks TikTok's own domain-verification bot also intercepts plain `curl`/webhook-bot requests to `creatorzhive.infinityfree.io` (confirmed on `webhook/process-jobs.php` too). If UptimeRobot pings ever stop showing up as processed, this is worth investigating separately — it may affect the whole background job pipeline, not just TikTok.

---

## How Token Refresh Works

TikTok access tokens expire after **24 hours**, refresh tokens after **365 days**:

- On every `fetch_analytics` job run, the job checks if the token expires within 5 minutes
- If so, it calls TikTok's token endpoint with `grant_type=refresh_token`
- The new access token is encrypted and stored in the database
- The analytics fetch continues with the fresh token

If the refresh token itself expires (a year of inactivity) or is revoked, the creator must reconnect via Settings → Integrations (from the new domain).

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| "TikTok integration is disabled" on Settings page | Admin has disabled it | Log in as admin → enable TikTok integration |
| "TikTok client key and secret must be configured..." | Credentials not saved yet | Complete Step 4 |
| "Invalid OAuth state" / "OAuth session expired" | Started the connect flow from `creatorzhive.infinityfree.io` instead of the new domain, or took too long on TikTok's consent screen | Retry entirely from `https://yournewdomain.com` |
| Redirect error / "redirect_uri does not match" from TikTok | Redirect URI not registered exactly, or `TIKTOK_OAUTH_REDIRECT_URI` not set in `.env` | Re-check Step 2.4 and Step 4 |
| "Authorization was denied or failed" | User not added as a tester in sandbox mode | Complete Step 2.5 |
| Stuck on domain verification, "couldn't find your verification signature" | Used URL prefix + signature file — blocked by InfinityFree's anti-bot layer | Use Step 1–2.2's domain + DNS TXT approach instead |
| New domain shows InfinityFree's default page instead of CreatorzHive | Addon domain not added in InfinityFree panel yet, or DNS hasn't propagated | Re-check Step 1.4–1.5 |
| Follower count stays at 0 or fake seed values | `user.info.stats` scope not approved/requested | Check Login Kit scope config in TikTok Developer Portal |
| Publishing only lands in TikTok inbox/drafts, never public | App not approved for `video.publish` | Expected in sandbox mode — request app review for production posting |

---

## What Data Is Collected

| Field | Source | Notes |
|---|---|---|
| Follower count | `follower_count` from `/v2/user/info/` | Requires `user.info.stats` scope |
| Display name | `display_name` | Used as both username and display name fallback |
| Avatar | `avatar_url` | Stored as-is |
| Open ID | `open_id` | TikTok's stable per-app user identifier, stored as `platform_user_id` |

> TikTok's public API does not expose historical/per-video analytics for most apps without additional review — only current follower count is synced today.

---

**Last Updated:** 2026-07-06
