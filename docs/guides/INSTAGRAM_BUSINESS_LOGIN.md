# Instagram Business Login — Detailed Setup Guide

This guide walks through every step to connect CreatorzHive with Instagram using Meta Graph API v25 Business Login.

---

## Before You Start

You need three things:

1. **A Meta Developer account** — free at developers.facebook.com. Use any Facebook/Meta account.
2. **An Instagram Business or Creator account** — a personal Instagram account will NOT work. See [Convert your Instagram account](#convert-instagram-to-business-or-creator) below if needed.
3. **Admin access to CreatorzHive** — to enter the App ID and Secret.

---

## Step 1: Create a Meta Developer Account

1. Go to **https://developers.facebook.com**
2. Click **Get Started** in the top-right corner
3. Log in with your Facebook/Meta account
4. Accept the Meta Platform Terms
5. You are now a developer — you will land on the **My Apps** dashboard

If you already have a developer account, just go to **https://developers.facebook.com/apps** and skip to Step 2.

---

## Step 2: Create a New App

1. On the **My Apps** page, click the green **Create App** button
2. You will be asked to choose an **app type**:
   - Select **Business** (not Consumer, not Gaming)
   - Click **Next**
3. Fill in the app details:
   - **App name**: anything — e.g. `CreatorzHive`
   - **App contact email**: your email
   - **Business portfolio**: leave blank or select if you have one
4. Click **Create App**
5. Meta may ask you to re-enter your Facebook password — do so
6. You will land on the **App Dashboard** for your new app

At the top of the dashboard you will see your **App ID** (a long number like `1234567890123456`). Note it — you will need it later.

---

## Step 3: Add the Instagram Product

1. In your app dashboard, scroll down to find the **Add Products to Your App** section
2. Find **Instagram** in the list and click **Set Up**
3. You will be taken to the Instagram product page
4. Under **Generate access tokens**, click **API setup with Instagram login**

   > This is the Business Login flow. Do NOT use "API setup with Instagram Basic Display" — that is a deprecated personal-account API.

5. You are now in the Instagram Business Login configuration panel

---

## Step 4: Set the OAuth Redirect URI

This is the URL Meta redirects to after the user approves the connection.

> **Note**: On the Instagram API Setup page you will also see a **"Configure webhooks"** section with a "Callback URL" field — that is for Meta server-to-server push notifications and is **not** what you need here. Ignore it.

The OAuth redirect URI is set in a separate location:

1. In the left sidebar under **Instagram**, click **API integration helper**
   — or look for **Instagram Business Login Settings** if visible in the sidebar
2. Find the **Valid OAuth Redirect URIs** field
3. Click **Add URI** and enter exactly:
   ```
   https://creatorzhive.infinityfree.io/?route=instagram-callback
   ```
4. Click **Save Changes**

If you cannot find this field, go to: **App Settings → Basic** and scroll down to the **Instagram** section, or check under **Instagram → Business Login Settings**.

**Important**: The URI must match exactly including `?route=instagram-callback`. Any difference — extra slash, different domain, missing query string — will cause a `redirect_uri_mismatch` error.

Also while on **App Settings → Basic**, update:
- **App domains**: change `creatorz.freedev.app` → `creatorzhive.infinityfree.io`
- **Privacy policy URL** and **Terms of Service URL**: update both to use `creatorzhive.infinityfree.io`

---

## Step 5: Configure Permissions

Permissions (called "scopes") control what data your app can access.

1. In the left sidebar, click **App Review** → **Permissions and Features**
2. Search for each of these three permissions and click **Request** next to each:

   | Permission | What it does |
   |-----------|-------------|
   | `instagram_business_basic` | Read the user's Instagram profile (username, name, account type) |
   | `instagram_business_content_publish` | Publish photos and videos to their Instagram |
   | `instagram_business_manage_insights` | Read analytics: reach, impressions, engagement |

3. For each permission, Meta will show a description — accept it

**In Development mode** (before app review): You can use all permissions, but only accounts you have added as **Testers** can connect. See [Adding Test Users](#adding-test-users-in-development-mode) below.

**In Live mode** (after app review): Any Instagram Business/Creator account can connect. App review takes 1–5 business days.

---

## Step 6: Get Your Instagram App ID and App Secret

In your Meta app there are **two different App IDs** — make sure you use the right one:

| ID | Where it appears | Use for CreatorzHive? |
|----|-----------------|----------------------|
| **Meta App ID** (e.g. `2024150598198508`) | App Settings → Basic, top of dashboard | ❌ No |
| **Instagram App ID** (e.g. `1710012066701003`) | Instagram → API setup with Instagram login | ✅ Yes |

To get the correct values:

1. In the left sidebar, click **Instagram** → **API setup with Instagram login**
2. You will see a panel titled **"API setup with Instagram business login"** showing:
   - **Instagram app name** — your app name
   - **Instagram app ID** — copy this number
   - **Instagram app secret** — click **Show**, re-enter your Facebook password, copy the value
3. These are the two values you need — `INSTAGRAM_APP_ID` and `INSTAGRAM_APP_SECRET`

**Do not use** the App ID from App Settings → Basic — that is the parent Meta app identifier and will not work for Instagram OAuth.

**Keep the App Secret private.** Never share it or commit it to git.

---

## Step 7: Add Credentials to CreatorzHive

### Option A — Admin UI (recommended)

1. Log in to CreatorzHive as **admin**
2. Go to **Settings** → **Integrations**
3. Find the **Instagram Business Login** section
4. Enter:
   - **Instagram App ID** — the **Instagram app ID** from Step 6 (the one on the Instagram API Setup page, not the Meta App ID)
   - **Instagram App Secret** — the **Instagram app secret** from Step 6 (click Show on that same page)
5. Click **Save**

CreatorzHive encrypts the secret with AES-256 before storing it.

### Option B — `.env` file

Open `/htdocs/.env` on the server (via FTP) and fill in:

```env
INSTAGRAM_APP_ID=your_app_id_here
INSTAGRAM_APP_SECRET=your_app_secret_here
```

Then reload the page.

---

## Step 8: Add Test Users (Development Mode)

While your app is in **Development** mode, only accounts you explicitly add as testers can connect. To add yourself or a team member:

1. In your app dashboard, go to **Roles** → **Roles** in the left sidebar
2. Click **Add People**
3. Search for the Facebook account linked to the Instagram you want to test with
4. Assign the role **Tester**
5. That person must accept the invite at **https://developers.facebook.com/apps** → the invitation notification

Once accepted, that Instagram account can complete the OAuth flow even while the app is in Development mode.

---

## Step 9: Test the Connection

1. Log in to CreatorzHive as a **creator** account (not admin — admins are blocked from connecting social accounts)
2. Go to **Settings** → **Integrations**
3. Click **Connect Instagram**
4. You will be redirected to Facebook's login/authorization dialog at `graph.facebook.com`
5. Log in with the Facebook account linked to your Instagram Business account
6. Review the permissions and click **Allow**
7. You will be redirected back to CreatorzHive → Settings → Integrations
8. The Instagram account should now appear as **Connected**

---

## What Happens Under the Hood

```
Creator clicks "Connect Instagram"
        ↓
GET /?route=instagram-connect
  → Generates a random state token, stores it in session
  → Redirects to:
        https://graph.facebook.com/v25.0/dialog/oauth
          ?client_id={INSTAGRAM_APP_ID}
          &redirect_uri=https://creatorzhive.infinityfree.io/?route=instagram-callback
          &scope=instagram_business_basic,instagram_business_content_publish,instagram_business_manage_insights
          &response_type=code
          &state={random_hex}
        ↓
User sees Facebook/Instagram authorization dialog
User clicks Allow
        ↓
Meta redirects to:
  GET /?route=instagram-callback?code={code}&state={state}
  → Validates state matches session (CSRF protection)
  → Exchanges code for token:
      POST https://graph.facebook.com/v25.0/oauth/access_token
        client_id, client_secret, redirect_uri, code
  → Fetches Instagram profile:
      GET https://graph.instagram.com/v25.0/me?fields=id,username,name,account_type
  → Saves account to social_accounts table (token AES-256 encrypted)
        ↓
Redirect → Settings → Integrations (success message shown)
```

---

## Token Lifecycle

Instagram tokens expire after approximately 60 days. CreatorzHive handles refresh automatically:

| Event | What happens |
|-------|-------------|
| User connects | Long-lived token stored (~60 days) |
| Token has 7 days left | Auto-refreshed next time a post is published |
| Token expires | User must reconnect via Settings → Integrations |
| User disconnects | Token deleted from database |

---

## Going Live (App Review)

While in Development mode, only added testers can connect. To allow any Instagram Business account to connect:

1. In app dashboard → **App Review** → **Go Live**
2. For each permission you requested, provide:
   - A short description of how you use it
   - A screencast or screenshots showing the feature in your app
3. Submit for review
4. Meta typically responds within 1–5 business days
5. Once approved, flip the toggle to **Live** in your app dashboard

---

## Convert Instagram to Business or Creator

A personal Instagram account cannot connect to Business Login. To convert:

1. Open Instagram on your phone
2. Go to your profile → **Menu (☰)** → **Settings and Privacy**
3. Tap **Account type and tools** → **Switch to Professional Account**
4. Choose **Creator** or **Business**
5. Follow the steps — you can choose a category and optionally link a Facebook Page
6. Once converted, your account will work with CreatorzHive

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| "Instagram App ID and App Secret must be configured" | Credentials not saved | Add them in Admin → Integrations or `.env` |
| `redirect_uri_mismatch` | Callback URL in Meta doesn't match | Verify the URI in Meta app → Instagram Business Login Settings matches exactly |
| "Invalid OAuth state" | Session expired between redirect steps | Clear browser cookies and retry |
| "Token exchange failed (HTTP 400)" | Code already used, or wrong client secret | Retry the flow; double-check App Secret |
| "Could not retrieve Instagram Business account info" | Account is Personal type | Convert to Business or Creator (see above) |
| "This app is in development mode" error | App not in Live mode and user is not a tester | Add user as Tester in App → Roles, or submit for App Review |
| "Instagram integration is disabled" | Admin toggled it off | Admin → Integrations → enable Instagram |
| Connected but analytics show zero | Analytics sync hasn't run yet | Wait for UptimeRobot to call the webhook, or trigger manually |
