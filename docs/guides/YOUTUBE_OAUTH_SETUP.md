# YouTube OAuth Setup Guide

## Overview

YouTube uses Google's OAuth 2.0. The same Google Cloud project that handles Google Login also handles YouTube channel connections. Access tokens expire every hour — the app automatically refreshes them using the stored refresh token.

---

## Step 1 — Google Cloud Console

### 1.1 Enable the YouTube Data API v3

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Select your project
3. **APIs & Services → Library**
4. Search **"YouTube Data API v3"** → click it → **Enable**

### 1.2 Add the Redirect URI

1. **APIs & Services → Credentials**
2. Open your existing OAuth 2.0 Client ID (`887145908155-...`)
3. Under **Authorized redirect URIs** click **Add URI** and enter exactly:
   ```
   https://creatorzhive.infinityfree.io/?route=youtube-callback
   ```
4. Click **Save**

### 1.3 Configure the OAuth Consent Screen

1. **APIs & Services → OAuth consent screen**
2. Click **Edit App**
3. On the **Scopes** step, click **Add or Remove Scopes** and add:
   - `https://www.googleapis.com/auth/youtube.readonly`
   - `https://www.googleapis.com/auth/youtube.upload`
4. Save and continue through remaining steps

### 1.4 Add Test Users (if app is in Testing mode)

If your OAuth consent screen status is **Testing** (not Published):

1. **APIs & Services → OAuth consent screen → Test users**
2. Click **Add Users**
3. Add: `mposojunior15@gmail.com`
4. Save

> **Note:** In Testing mode only accounts listed as test users can complete the OAuth flow. Publish the app to remove this restriction.

---

## Step 2 — Upload Files to InfinityFree

Upload all of the following (these were updated as part of the YouTube fix):

| Local path | Remote path |
|---|---|
| `src/Services/YoutubeOAuthService.php` | `/htdocs/src/Services/YoutubeOAuthService.php` |
| `src/Repositories/SocialAccountRepository.php` | `/htdocs/src/Repositories/SocialAccountRepository.php` |
| `src/Jobs/FetchAnalyticsJob.php` | `/htdocs/src/Jobs/FetchAnalyticsJob.php` |
| `backend/compat/models.php` | `/htdocs/backend/compat/models.php` |
| `public/webhook/process-jobs.php` | `/htdocs/public/webhook/process-jobs.php` |

---

## Step 3 — Connect YouTube

1. Log in to CreatorzHive as a creator account (not admin)
2. Go to **Settings → Integrations**
3. Click **Connect YouTube**
4. Google's consent screen will appear — approve access
5. You will be redirected back to Settings with a success message

---

## Step 4 — Verify the Connection

Run the test endpoint:

```
https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=YOUR_WEBHOOK_SECRET&test_youtube=1
```

Expected response:
```json
{
  "account_id": 1,
  "channel_id": "UCxxxxx",
  "token_length": 220,
  "expires_at": "2026-07-04 12:00:00",
  "has_refresh": true,
  "is_expired": false,
  "channel_status": 200,
  "channel_response": {
    "items": [{ "id": "UCxxxxx", "snippet": { "title": "..." }, "statistics": { "subscriberCount": "..." } }]
  }
}
```

`channel_status: 200` and `has_refresh: true` confirm the connection is healthy.

---

## Step 5 — Fetch Real Analytics

Run in order:

**1. Dispatch a fresh fetch_analytics job:**
```
?secret=YOUR_WEBHOOK_SECRET&refresh_analytics=1
```

**2. Process the job:**
```
?secret=YOUR_WEBHOOK_SECRET
```

The dashboard will now show your real YouTube subscriber count.

---

## How Token Refresh Works

YouTube access tokens expire after **1 hour**. The app handles this automatically:

- On every `fetch_analytics` job run, the job checks if the token expires within 5 minutes
- If so, it calls Google's token endpoint with `grant_type=refresh_token`
- The new access token is encrypted and stored in the database
- The analytics fetch continues with the fresh token

The refresh token does not expire unless the user revokes access or the app's OAuth consent is reset.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| "YouTube integration is disabled" on Settings page | Admin has disabled it | Log in as admin → enable YouTube integration |
| "No YouTube channel found on this Google account" | Google account has no YouTube channel | Create a channel on YouTube first |
| `channel_status: 401` in test_youtube | Token expired, refresh failed | Disconnect and reconnect YouTube |
| `channel_status: 403` | YouTube Data API v3 not enabled | Enable it in GCC (Step 1.1) |
| Redirect error from Google | Redirect URI not in GCC | Add the URI exactly as shown in Step 1.2 |
| "Authorization was denied" flash message | User not in test users list | Add email to test users (Step 1.4) |

---

## What Data Is Collected

| Field | Source | Notes |
|---|---|---|
| Subscribers | `statistics.subscriberCount` | Real-time from YouTube Data API |
| Total views | `statistics.viewCount` | Cumulative all-time views, used as impressions |
| Channel name | `snippet.title` | Used as display name |
| Handle | `snippet.customUrl` | Stored without leading `@` |

> YouTube Data API v3 does not provide per-day view counts at channel level. Daily breakdown requires the YouTube Analytics API (a future enhancement).

---

**Last Updated:** 2026-07-04
