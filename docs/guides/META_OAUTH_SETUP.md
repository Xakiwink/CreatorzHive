# Meta (Facebook & Instagram) OAuth Setup Guide

This guide covers every step to connect CreatorzHive to the Meta Graph API so users can publish posts directly to Facebook Pages and Instagram Business accounts.

---

## Overview

CreatorzHive uses **Meta Graph API v20.0** with the standard OAuth 2.0 code flow:

```
User clicks Connect → Meta login dialog → callback URL → code exchange
→ short-lived token → long-lived token (~60 days) → stored encrypted in DB
```

Both Facebook Pages and Instagram Business accounts are connected through the **same Meta app** and the **same OAuth flow** — Instagram access is obtained via the connected Facebook Page.

---

## Prerequisites

Before starting:

- A **Meta (Facebook) account** — personal account is fine; you own the app
- A **Facebook Page** (not a personal profile) — required for both Facebook publishing and Instagram access
- An **Instagram Business or Creator account** linked to that Facebook Page
  - Instagram → Settings → Account → Switch to Professional Account → then link to a Facebook Page in Meta Business Suite

---

## Step 1 — Create a Meta Developer App

1. Go to [developers.facebook.com](https://developers.facebook.com/) and log in
2. Click **My Apps → Create App**
3. Choose app type: **Business** (not Consumer or Gaming)
4. Fill in:
   - **App name**: anything, e.g. `CreatorzHive`
   - **App contact email**: your email
   - **Business portfolio**: optional, skip if you don't have one
5. Click **Create App**

---

## Step 2 — Add Required Products

In the left sidebar under your new app, click **Add Product** and add both:

### Facebook Login

1. Click **Set Up** next to **Facebook Login**
2. Choose **Web**
3. In **Facebook Login → Settings**:
   - Set **Valid OAuth Redirect URIs** to:
     ```
     https://creatorz.freedev.app/?route=oauth-callback
     ```
   - Enable **Client OAuth Login**: Yes
   - Enable **Web OAuth Login**: Yes
   - Save changes

### Instagram Graph API

1. Click **Add Product** again
2. Click **Set Up** next to **Instagram Graph API**
3. No extra config needed at this stage — permissions are requested at runtime

---

## Step 3 — Configure App Settings

In **Settings → Basic**:

| Field | Value |
|-------|-------|
| App Domains | `creatorz.freedev.app` |
| Privacy Policy URL | your privacy page or `https://creatorz.freedev.app` |
| Terms of Service URL | same as above |
| App Mode | **Development** for testing; **Live** for real users |

> **Note:** In Development mode only you (and testers you add) can connect accounts. Switch to Live mode once everything works so other users can connect.

Copy the values you will need next:

- **App ID** — shown at the top of Settings → Basic
- **App Secret** — click **Show** next to the secret field (you may need to confirm your password)

---

## Step 4 — Add the Credentials to CreatorzHive

### Option A — Admin UI (recommended)

1. Log in to CreatorzHive as an **admin**
2. Go to **Settings → Integrations** (`https://creatorz.freedev.app/?route=settings-integrations`)
3. Find the **Meta** section
4. Enter:
   - **Meta App ID** → paste the App ID from Step 3
   - **Meta App Secret** → paste the App Secret from Step 3
5. Save — credentials are encrypted and stored in `backend/storage/platform-api-secrets.json`

### Option B — `.env` file (InfinityFree FTP)

Add these two lines to `/htdocs/.env`:

```env
META_APP_ID=your_app_id_here
META_APP_SECRET=your_app_secret_here
```

Also ensure mock publishing is disabled:

```env
SOCIAL_API_MOCK_FALLBACK=false
```

> The redirect URI defaults to `https://creatorz.freedev.app/?route=oauth-callback` automatically.
> Only set `META_OAUTH_REDIRECT_URI` in `.env` if you need to override this (e.g. custom domain or local dev).

---

## Step 5 — Connect a Facebook or Instagram Account

1. Log in as a **creator** (not admin — admins cannot connect social accounts by design)
2. Go to **Settings → Connected Accounts** or the Accounts page
3. Click **Connect Facebook** or **Connect Instagram**
4. You are redirected to Meta's login dialog
5. Approve the requested permissions
6. You are redirected back — the account is saved

### What happens behind the scenes

```
1. App generates a random state token and stores it in the session
2. User is sent to:
   https://www.facebook.com/v20.0/dialog/oauth?
     client_id=APP_ID
     &redirect_uri=https://creatorz.freedev.app/?route=oauth-callback
     &scope=SCOPES
     &state=RANDOM_STATE
     &response_type=code

3. Meta returns ?code=AUTH_CODE&state=RANDOM_STATE to the callback
4. App verifies the state matches the session value
5. App exchanges the code for a short-lived token:
   GET /v20.0/oauth/access_token?grant_type=authorization_code&code=...
6. App upgrades to a long-lived token (~60 days):
   GET /v20.0/oauth/access_token?grant_type=fb_exchange_token&fb_exchange_token=...
7. App fetches /me/accounts to get Facebook Pages (and linked IG business accounts)
8. Token is encrypted with TokenCrypto and stored in the social_accounts table
```

### Required OAuth scopes

| Platform | Scopes requested |
|----------|-----------------|
| Instagram | `instagram_basic` `instagram_content_publish` `instagram_manage_insights` `business_management` `pages_show_list` `pages_read_engagement` |
| Facebook | `pages_manage_posts` `pages_read_engagement` `pages_show_list` |

---

## Step 6 — Verify the Connection Works

After connecting, test publishing:

1. Create a test post in CreatorzHive, select Instagram or Facebook as the platform
2. Set status to **Scheduled** with a time 1–2 minutes in the future
3. Wait for the webhook job runner to fire (UptimeRobot pings `/webhook/process-jobs.php` every 5 minutes)
4. Check the post status — it should change to **Published**
5. Check the actual Facebook Page / Instagram account to confirm the post appeared

---

## Troubleshooting

### "No Facebook Pages found"

- You must have a **Facebook Page** (not just a personal profile)
- The page must be linked to your Meta app in **Meta Business Suite → Pages**
- If your app is in **Development mode**, only the app owner and added testers can authorize

### "No Instagram Business account found on your Pages"

- Your Instagram account must be **Business** or **Creator** (not Personal)
- It must be linked to the Facebook Page via **Instagram Settings → Linked Accounts**
- Confirm in Meta Business Suite: Business Settings → Instagram Accounts

### "Could not exchange authorization code"

- The redirect URI in your Meta app (**Facebook Login → Settings → Valid OAuth Redirect URIs**) must **exactly** match what CreatorzHive sends, including the `?route=oauth-callback` query string
- Exact required value: `https://creatorz.freedev.app/?route=oauth-callback`

### Posts publish but show "mock_xxx" IDs

- `SOCIAL_API_MOCK_FALLBACK` in `.env` is `true` or missing
- Set it to `false` and re-upload `.env`

### Token expired / posts failing after ~60 days

- CreatorzHive automatically refreshes tokens when fewer than 7 days remain before expiry (done in `PublishPostJob` before each publish run)
- If a token is already expired, the user must re-connect their account via **Settings → Connected Accounts → Reconnect**

### App is in Development mode — other users can't connect

- In the Meta Developer dashboard, go to **App Review → Permissions and Features**
- Request **Advanced Access** for each permission your app uses
- Switch app status to **Live** in **Settings → Basic**

---

## Permissions Reference

| Permission | Why needed |
|-----------|-----------|
| `instagram_basic` | Read basic profile info to confirm which IG account is connected |
| `instagram_content_publish` | Publish photos and videos to Instagram |
| `instagram_manage_insights` | Read impressions, reach, and engagement analytics |
| `business_management` | Required to access Business-type Instagram accounts |
| `pages_show_list` | See which Facebook Pages the user manages |
| `pages_read_engagement` | Read page engagement data |
| `pages_manage_posts` | Create and publish posts to a Facebook Page |

---

## Local Development Notes

When developing locally, the redirect URI will differ. Set this in `.env`:

```env
APP_URL=http://localhost/creatorzhive
META_OAUTH_REDIRECT_URI=http://localhost/creatorzhive/?route=oauth-callback
```

Add `http://localhost/creatorzhive/?route=oauth-callback` to the **Valid OAuth Redirect URIs** list in your Meta app. Meta allows multiple redirect URIs — one per line.

---

*Last updated: 2026-06-22*
