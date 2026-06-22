# Meta (Facebook & Instagram) OAuth Setup Guide

Covers every step to connect CreatorzHive to the Meta Graph API so users can publish to Facebook Pages and Instagram Business accounts.

**Meta Graph API version used:** v25.0  
**Instagram method used:** Instagram API with Facebook Login (requires a Facebook Page linked to an Instagram Business account)

---

## How the Flow Works

```
User clicks Connect
  → Meta OAuth dialog (Facebook Login)
  → callback with auth code
  → code exchanged for short-lived token
  → upgraded to long-lived token (~60 days)
  → /me/accounts fetched (Facebook Pages + linked IG accounts)
  → token encrypted and stored in social_accounts table
```

Both Facebook and Instagram use the **same Meta app** and the **same OAuth flow**. Instagram access is obtained through the connected Facebook Page — this is the "Instagram API with Facebook Login" method.

---

## Prerequisites

Before starting you need:

- A **Meta account** at [facebook.com](https://www.facebook.com)
- A **Facebook Page** (required for both Facebook publishing and Instagram access — a personal profile is not enough)
- An **Instagram Business or Creator account** connected to that Facebook Page

To connect Instagram to a Facebook Page:
1. In the Instagram app: Settings → Account → Switch to Professional Account
2. Then in [Meta Business Suite](https://business.facebook.com): Settings → Instagram Accounts → Connect

---

## Step 1 — Create a Meta Developer App

1. Go to [developers.facebook.com](https://developers.facebook.com/) and log in
2. Click **My Apps → Create App**
3. On the **What do you want your app to do?** screen, select **Other** (at the bottom), then click **Next**
4. On the app type screen, select **Business**, then click **Next**
5. Fill in:
   - **App name** — e.g. `CreatorzHive`
   - **App contact email** — your email
   - **Business portfolio** — optional, skip if you don't have one
6. Click **Create App**

> Meta has moved to a use-case-driven setup wizard. If you see a use-case selector instead, choose **"Set up Facebook Login"** and **"Manage everything on your Page"** — both are needed.

---

## Step 2 — Add Required Products

In the left sidebar of your app dashboard, click **Add Product** (or look under **Products** section).

### Facebook Login for Business

1. Click **Set Up** next to **Facebook Login for Business** (or **Facebook Login** if that's what's shown)
2. Choose **Web**
3. In **Facebook Login → Settings**:
   - **Valid OAuth Redirect URIs** — add exactly:
     ```
     https://creatorz.freedev.app/?route=oauth-callback
     ```
   - **Client OAuth Login**: On
   - **Web OAuth Login**: On
   - **Enforce HTTPS**: On (required)
   - **Embedded Browser OAuth Login**: Off
   - Save changes

### Instagram Graph API

1. Click **Add Product**
2. Find **Instagram** and click **Set Up**
3. On the Instagram setup screen, select **Instagram API with Facebook Login** — this is the method that uses the Facebook Page connection
4. No additional configuration needed here; scopes are requested at runtime

---

## Step 3 — App Settings

Go to **Settings → Basic** and fill in:

| Field | Value |
|-------|-------|
| App Domains | `creatorz.freedev.app` |
| Privacy Policy URL | Your privacy policy URL |
| Terms of Service URL | Your terms URL |
| Category | Business and Pages |

**Copy these values — you will need them in Step 4:**

- **App ID** — shown at the top of Settings → Basic
- **App Secret** — click **Show** (you may need to re-enter your Facebook password)

---

## Step 4 — Add Credentials to CreatorzHive

### Option A — Admin UI (recommended)

1. Log in to CreatorzHive as an **admin**
2. Go to **Settings → Integrations**  
   (`https://creatorz.freedev.app/?route=settings-integrations`)
3. Find the **Meta** section
4. Enter your **App ID** and **App Secret**
5. Save — credentials are AES-encrypted and written to `backend/storage/platform-api-secrets.json`

### Option B — `.env` via FTP

Edit `/htdocs/.env` and add:

```env
META_APP_ID=your_app_id_here
META_APP_SECRET=your_app_secret_here
SOCIAL_API_MOCK_FALLBACK=false
```

> The redirect URI defaults to `https://creatorz.freedev.app/?route=oauth-callback` automatically from `APP_URL`. Only set `META_OAUTH_REDIRECT_URI=` if you need to override it.

---

## Step 5 — Connect an Account

1. Log in as a **creator** account (admins cannot connect social accounts by design)
2. Go to **Settings → Connected Accounts**
3. Click **Connect Instagram** or **Connect Facebook**
4. The Meta OAuth dialog opens — log in and approve the permissions
5. You are redirected back and the account is saved

### OAuth scopes requested

| Platform | Scopes |
|----------|--------|
| Instagram | `instagram_basic` `instagram_content_publish` `instagram_manage_insights` `business_management` `pages_show_list` `pages_read_engagement` |
| Facebook | `pages_manage_posts` `pages_read_engagement` `pages_show_list` |

---

## Step 6 — Access Levels Explained

Meta uses two access levels:

| | Standard Access | Advanced Access |
|-|----------------|----------------|
| Who can use the app | Only you (app owner) and added testers | Any user |
| Requires App Review | No | Yes |
| Requires Business Verification | No | Yes |
| Good for | Development and testing | Production / going live |

**In Standard Access (Development mode)** you can only call the API against your own Facebook Page and your own Instagram account. This is enough to test that everything works.

**To go live for real users:**

1. **Complete Business Verification**
   - Dashboard → App Review → Business Verification
   - Submit your business name, address, and supporting documentation
   - Meta typically reviews within 2–5 business days

2. **Submit for App Review**
   - Dashboard → App Review → Permissions and Features
   - Request **Advanced Access** for each permission:
     - `instagram_content_publish`
     - `instagram_manage_insights`
     - `pages_manage_posts`
     - `pages_read_engagement`
   - For each permission, you must provide a **screencast video** demonstrating how your app uses it, and a written description
   - Also submit your app for review under **App Review → Requests**

3. **Switch App to Live**
   - Settings → Basic → App Status → toggle from **In Development** to **Live**
   - This only works after App Review approval

> Until Advanced Access is granted, only users you add under **Roles → Testers** can connect their accounts.

---

## Step 7 — Add Test Users (while in Development)

While waiting for App Review, add test accounts so your team can use the app:

1. Dashboard → **Roles → Test Users** (or **Roles → Testers**)
2. Add Facebook accounts that own Pages with linked Instagram accounts
3. Those users can now complete the OAuth flow and connect their accounts

---

## Step 8 — Verify Publishing Works

1. Create a test post in CreatorzHive
2. Select Facebook or Instagram as the platform
3. Set status to **Scheduled** 2–3 minutes from now
4. Wait for the webhook job runner to fire (UptimeRobot pings `/webhook/process-jobs.php` every 5 min)
5. Check the post status in CreatorzHive — should become **Published**
6. Check your actual Facebook Page / Instagram account to confirm

---

## Troubleshooting

### "No Facebook Pages found"

The user has no Facebook Page, or the Page isn't visible to the app:
- Confirm the user has a **Facebook Page** (not just a personal profile)
- In Development mode, only the app owner and added testers can see their Pages
- Make sure the user approved "Manage your Pages" during the OAuth dialog

### "No Instagram Business account found on your Pages"

- The Instagram account must be **Business** or **Creator** (not Personal)
- It must be linked to a Facebook Page — check in [Meta Business Suite](https://business.facebook.com) → Settings → Instagram Accounts
- The Facebook Page must be one the user manages (not just follows)

### "Could not exchange authorization code"

The redirect URI doesn't match exactly:
- Open your Meta app → Facebook Login → Settings
- Confirm **Valid OAuth Redirect URIs** contains exactly:
  ```
  https://creatorz.freedev.app/?route=oauth-callback
  ```
- The URI is case-sensitive and must include the `?route=oauth-callback` query string

### Posts save but show `mock_xxx` as the platform post ID

`SOCIAL_API_MOCK_FALLBACK` is enabled — all publishes are returning fake data:
- Open `/htdocs/.env` via FTP
- Set `SOCIAL_API_MOCK_FALLBACK=false`
- Save and re-upload

### "User not authorized to perform this action" or permission errors

Your app is in Development mode and the account is not added as a tester:
- Add the account under Dashboard → Roles → Testers
- Or complete App Review to get Advanced Access for all users

### Token expired after ~60 days

CreatorzHive automatically refreshes tokens within 7 days of expiry via `PublishPostJob`. If a token is already expired and cannot be refreshed, the user must reconnect:
- Settings → Connected Accounts → click **Reconnect** next to the platform

### App stuck in "In Development" — can't switch to Live

You must complete **both** Business Verification and App Review before switching to Live. The toggle in Settings → Basic will be grayed out until both are approved.

---

## Permissions Reference

| Permission | Required for | Advanced Access needed |
|-----------|-------------|----------------------|
| `instagram_basic` | Reading IG profile info after connect | No |
| `instagram_content_publish` | Publishing photos/videos to Instagram | Yes |
| `instagram_manage_insights` | Reading impressions, reach, engagement | Yes |
| `business_management` | Accessing Business-type IG accounts | Yes |
| `pages_show_list` | Listing Facebook Pages the user manages | No |
| `pages_read_engagement` | Reading Page insights and post engagement | Yes |
| `pages_manage_posts` | Creating/publishing posts to a Facebook Page | Yes |

---

## Local Development

For testing on localhost, add to your `/var/www/html/creatorzhive/.env`:

```env
APP_URL=http://localhost/creatorzhive
META_OAUTH_REDIRECT_URI=http://localhost/creatorzhive/?route=oauth-callback
```

And add `http://localhost/creatorzhive/?route=oauth-callback` to the **Valid OAuth Redirect URIs** in your Meta app settings. Meta allows multiple URIs — one per line. Note that Meta requires HTTPS for production but allows HTTP for localhost.

---

## Choosing Between Instagram Login Methods

Meta now offers two Instagram API methods. CreatorzHive uses **Instagram API with Facebook Login** (the established method). Here is when each applies:

| | Instagram API with Facebook Login | Instagram API with Instagram Login |
|-|----------------------------------|-----------------------------------|
| Requires Facebook Page | Yes | No |
| User connects | Facebook account + Page | Instagram account directly |
| Facebook Page publishing | Yes | No |
| Instagram publishing | Yes | Yes |
| Instagram Insights | Yes | Yes |
| Best for | Tools managing both FB + IG together | Instagram-only tools |

CreatorzHive is designed around Facebook Login because it manages both platforms in a single OAuth flow. If you want to support Instagram-only creators (without a Facebook Page), that would require adding a separate Instagram Login flow — this is a future enhancement.

---

*Last updated: 2026-06-22*
