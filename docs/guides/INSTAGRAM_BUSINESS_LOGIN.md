# Instagram Business Login Setup Guide

This guide covers configuring Instagram Business Login (Meta Graph API v25) for CreatorzHive.

---

## Prerequisites

- A Meta Developer account at https://developers.facebook.com
- An Instagram Business or Creator account
- Admin access to CreatorzHive

---

## 1. Create a Meta App

1. Go to **https://developers.facebook.com/apps** → **Create App**
2. Select **Business** as the app type
3. Complete the setup wizard

---

## 2. Add Instagram Business Login Product

1. Inside your app dashboard → **Add Product** → **Instagram**
2. Under **Instagram** → **API setup with Instagram login**
3. Set the **Valid OAuth Redirect URIs** to:
   ```
   https://creatorz.freedev.app/?route=instagram-callback
   ```
   Adjust the domain for your deployment.

---

## 3. Configure Permissions (Scopes)

Ensure the following permissions are added and approved for your app:

| Scope | Purpose |
|-------|---------|
| `instagram_business_basic` | Read profile and media |
| `instagram_business_content_publish` | Publish images and videos |
| `instagram_business_manage_insights` | Read reach, impressions, engagement |

---

## 4. Add Credentials to CreatorzHive

### Option A — Admin UI (recommended)

1. Log in as admin → **Settings** → **Integrations**
2. Under **Instagram Business Login**, enter:
   - **Instagram App ID** — from Meta app → Basic settings
   - **Instagram App Secret** — from Meta app → Basic settings
3. Click **Save**

### Option B — Environment variables

Add to your `.env` file:

```env
INSTAGRAM_APP_ID=your_app_id_here
INSTAGRAM_APP_SECRET=your_app_secret_here
# Optional: override the default callback URL
# INSTAGRAM_OAUTH_REDIRECT_URI=https://yourdomain.com/?route=instagram-callback
```

---

## 5. Test the Connection

1. Log in as a **creator** account (not admin)
2. Go to **Settings** → **Integrations**
3. Click **Connect Instagram**
4. Complete the Instagram authorization dialog
5. Verify the account appears as connected

---

## OAuth Flow Summary

```
Creator clicks "Connect Instagram"
        ↓
GET /?route=instagram-connect
        ↓  (state token stored in session)
Redirect → https://graph.facebook.com/v25.0/dialog/oauth
           ?client_id={INSTAGRAM_APP_ID}
           &redirect_uri={APP_URL}/?route=instagram-callback
           &scope=instagram_business_basic,instagram_business_content_publish,instagram_business_manage_insights
           &response_type=code
           &state={random_hex}
        ↓
Instagram authorization dialog
        ↓
Redirect → GET /?route=instagram-callback?code={code}&state={state}
        ↓  (state validated, code exchanged)
POST https://graph.facebook.com/v25.0/oauth/access_token
        ↓
GET https://graph.instagram.com/v25.0/me?fields=id,username,name,account_type
        ↓
Account saved to social_accounts table (token AES-256 encrypted)
        ↓
Redirect → Settings → Integrations (flash success message)
```

---

## Token Lifecycle

| Event | Action |
|-------|--------|
| Initial connect | Long-lived token stored (valid ~60 days) |
| 7 days before expiry | Auto-refreshed during post publish |
| Manual refresh | Via `GET https://graph.instagram.com/v25.0/refresh_access_token?grant_type=ig_refresh_token` |
| Disconnect | Token removed from DB; no API revocation required |

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| "Instagram App ID and App Secret must be configured" | Missing credentials | Add credentials in Admin → Integrations |
| "Invalid OAuth state" | Session expired or CSRF | Retry the connection flow |
| "Token exchange failed (HTTP 400)" | Wrong redirect URI | Verify the callback URL in Meta app settings |
| "Could not retrieve Instagram Business account info" | Account not Business/Creator type | Convert Instagram account to Business or Creator |
| "Instagram integration is disabled by admin" | Integration toggled off | Admin → Integrations → enable Instagram |
