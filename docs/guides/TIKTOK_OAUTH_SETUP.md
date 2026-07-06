# TikTok OAuth Setup Guide

## Overview

TikTok uses its own OAuth 2.0 implementation ("Login Kit") which — unlike Google/Meta — **requires PKCE** (a `code_verifier` / `code_challenge` pair) on every authorization request. Access tokens expire every **24 hours**; the app automatically refreshes them using the stored refresh token (valid up to 365 days). Publishing goes through TikTok's Content Posting API.

Before this, TikTok only supported a single, admin-entered static access token (for testing/publishing as one account). Creators can now connect their own TikTok account via OAuth, the same way Instagram and YouTube work.

---

## Step 1 — TikTok for Developers

### 1.1 Create the app

1. Go to [developers.tiktok.com](https://developers.tiktok.com) → **Manage apps** → **Create an app**
2. Fill in app name, description, category
3. Under **Platform**, add **Web** and enter:
   ```
   https://creatorzhive.infinityfree.io
   ```
4. Set **Privacy Policy URL** and **Terms of Service URL** to:
   ```
   https://creatorzhive.infinityfree.io/?route=privacy-policy
   https://creatorzhive.infinityfree.io/?route=terms-of-service
   ```

### 1.2 Verify domain ownership

TikTok will ask you to verify the domain before it accepts the redirect URI or the privacy/terms URLs. You'll be asked to choose:

- **Domain + DNS record** — requires editing a DNS TXT record. Skip this; InfinityFree's free subdomain DNS zone isn't reliably editable and propagation is slow.
- **URL prefix + signature file** — use this one. No DNS access needed:
  1. Choose **URL prefix**, verify prefix `https://creatorzhive.infinityfree.io/`
  2. Download the signature file TikTok gives you (e.g. `tiktokXXXXXXXX.txt`)
  3. Upload it unmodified via FTP to `/htdocs/` (site root, alongside `index.php`)
  4. Confirm it loads at `https://creatorzhive.infinityfree.io/<filename>.txt`
  5. Click **Verify** in the TikTok portal

Because this app routes everything through `?route=...` on the same root path rather than real subpaths, verifying the root prefix covers the callback URL, privacy policy, and terms of service URLs all at once.

### 1.3 Add products

On the app's **Products** tab, add:
- **Login Kit** — provides `client_key` / `client_secret` and the `user.info.basic` / `user.info.stats` scopes
- **Content Posting API** — provides `video.publish` (or `video.upload` if publish isn't approved yet)

### 1.4 Configure the redirect URI

Under **Login Kit → Configure**, add this exact redirect URI:
```
https://creatorzhive.infinityfree.io/?route=tiktok-callback
```
It must match byte-for-byte or TikTok will reject the callback.

### 1.5 Add yourself as a tester (sandbox / unaudited mode)

New apps start in **sandbox mode** — only accounts added as testers can complete OAuth or see real stats:

1. **App → Manage users → Add users**
2. Add your TikTok account (and any other creator accounts you want to test with)

> Until TikTok reviews and approves the app for production, only added testers can connect, and some scopes (e.g. `video.publish`) may silently fall back to draft/inbox-only posting.

### 1.6 Copy your credentials

From the app's **Basic information** tab, copy:
- **Client key**
- **Client secret**

---

## Step 2 — Upload Files to InfinityFree

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

## Step 3 — Enter Credentials (Admin, no FTP/.env edit needed)

1. Log in as **admin**
2. **Settings → Integrations**
3. Find the **TikTok** credentials group
4. Enter **TikTok client key** and **TikTok client secret** (from Step 1.6)
5. Save — these are encrypted and stored in the database, same as the other platform secrets

> Alternatively, set `TIKTOK_CLIENT_KEY` / `TIKTOK_CLIENT_SECRET` in `.env` on the server — the app checks `.env` first, then falls back to the saved admin value.

---

## Step 4 — Connect TikTok

1. Log in as a **creator** account (not admin)
2. Go to **Settings → Integrations**
3. Click **Connect** on TikTok
4. TikTok's consent screen appears — approve access
5. You're redirected back to Settings with "TikTok account connected successfully"

---

## Step 5 — Verify the Connection

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

---

## How Token Refresh Works

TikTok access tokens expire after **24 hours**, refresh tokens after **365 days**:

- On every `fetch_analytics` job run, the job checks if the token expires within 5 minutes
- If so, it calls TikTok's token endpoint with `grant_type=refresh_token`
- The new access token is encrypted and stored in the database
- The analytics fetch continues with the fresh token

If the refresh token itself expires (a year of inactivity) or is revoked, the creator must reconnect via Settings → Integrations.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| "TikTok integration is disabled" on Settings page | Admin has disabled it | Log in as admin → enable TikTok integration |
| "TikTok client key and secret must be configured..." | Credentials not saved yet | Complete Step 3 |
| "Invalid OAuth state" / "OAuth session expired" | Took too long on TikTok's consent screen, or cookies blocked | Retry the connect flow |
| Redirect error / "redirect_uri does not match" from TikTok | Redirect URI not registered exactly | Re-check Step 1.4 — must match exactly, including trailing slash rules |
| "Authorization was denied or failed" | User not added as a tester in sandbox mode | Complete Step 1.5 |
| Stuck on domain/URL verification | Signature file not reachable, or DNS record chosen instead | Re-check Step 1.2 — use URL prefix + signature file, confirm the file URL loads in a browser first |
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
