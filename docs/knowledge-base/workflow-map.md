# CreatorzHive — Workflow Map

> End-to-end user workflows with step-by-step code traces.

---

## Workflow 1: New User Onboarding

```
1. User visits app
   URL: /?route=login
   → AuthController::loginPage() → renders frontend/pages/auth/login.php
   → "Sign in with Google" button visible if GOOGLE_CLIENT_ID configured

2. User clicks "Register"
   URL: /?route=register
   → AuthController::registerPage() → renders frontend/pages/auth/register.php

3. User submits registration form
   POST /?route=register { name, username, email, password, _csrf_token }
   → csrf_validate_post()
   → AuthController::register()
   → validate_required(), validate_email(), validate_length()
   → UserRepository::existsByEmail() — check duplicate
   → UserRepository::existsByUsername() — check duplicate
   → AuthService::hashPassword() — bcrypt cost 12
   → UserRepository::create() — INSERT INTO users
   → TRIGGER fires: creates analytics, notification_preferences, user_preferences rows
   → AuthService::generateVerificationToken()
   → mailer_send() — sends verify-email.html
   → Response: { success: true, redirect: "?route=login" }

4. User clicks verification link in email
   GET /?route=verify?token={64-char-hex}
   → AuthController::verify()
   → AuthService::validateVerificationToken()
   → UserRepository::update('email_verified', 1)
   → AuthService::markEmailVerificationUsed()
   → session_set_user($user)
   → redirect → ?route=dashboard

5. User lands on dashboard
   GET /?route=dashboard
   → AuthMiddleware::handle() — validates session
   → DashboardController::index()
   → DashboardService::getSummary()
   → Renders frontend/pages/dashboard/index.php
   → Page loads, JS calls GET /?route=dashboard_data
   → Returns: followers, posts, deals, notifications
```

---

## Workflow 2: Schedule and Publish a Post

```
1. Creator navigates to Planner
   GET /?route=planner
   → PostController::plannerPage()
   → Renders frontend/pages/planner/index.php
   → planner.js loads calendar view
   → Fetches GET /?route=posts_calendar to populate calendar

2. Creator clicks "New Post"
   → Modal opens (frontend/components/modal.html pattern)
   → Media picker fetches GET /?route=media_list
   → Tag list fetches GET /?route=tags

3. Creator fills form and sets schedule time
   POST /?route=create_post {
     title, content, caption,
     platforms: ["instagram","tiktok"],
     scheduled_at: "2026-06-15 14:00:00",
     media_ids: [5, 6],
     tag_ids: [2],
     _csrf_token
   }
   → auth_middleware_handle()
   → csrf_validate_post()
   → PostController::store()
   → PostInputNormalizer::normalize() — sanitizes, validates, parses JSON
   → PostRepository::create() — INSERT INTO posts (status='scheduled')
   → PostRepository::attachMedia([5, 6]) — INSERT INTO post_media
   → PostRepository::attachTags([2]) — INSERT INTO post_tags
   → AnalyticsRepository::incrementScheduled()
   → JobQueueRepository::dispatch('publish_post', {post_id, scheduled_at})
   → INSERT INTO job_queue (status='pending', available_at='2026-06-15 14:00:00')
   → NotificationService::create('post_scheduled')
   → Response: { success: true, data: { post_id: 42 } }

4. Calendar updates
   → planner.js re-fetches posts_calendar
   → Post appears as scheduled event on June 15

5. Cron runs at 14:00:00
   scripts/cron.php
   → job_runner_run('default', 10)
   → Finds pending job where available_at <= NOW()
   → PublishPostJob::handle({ post_id: 42, scheduled_at: ... })
   → PostRepository::findById(42) — fetches post
   → SocialAccountRepository::findByUserAndPlatforms(userId, ['instagram','tiktok'])
   → foreach platform:
       Instagram:
         → SocialApiService::publishToInstagram(account, post)
         → POST graph.facebook.com/v20.0/{ig_id}/media
         → POST graph.facebook.com/v20.0/{ig_id}/media_publish
         → Returns { success: true, platform_post_id: '...', platform_url: '...' }
       TikTok:
         → SocialApiService::publishToTiktok(account, post)
         → POST open.tiktokapis.com/v2/post/publish/inbox/video/init/
   → INSERT INTO platform_post_results (2 rows)
   → UPDATE posts SET status='published', published_at=NOW()
   → AnalyticsRepository::incrementPublished()
   → NotificationService::create('post_published')
   → UPDATE job_queue SET status='completed'
```

---

## Workflow 3: Brand Deal → Invoice

```
1. Creator adds new deal (lead stage)
   GET /?route=deals → deals.js loads Kanban board
   Fetches GET /?route=deals_data → returns deals grouped by status
   
   POST /?route=create_deal {
     title: "Samsung Brand Deal",
     brand_name: "Samsung",
     amount: 500000,
     currency: "TZS",
     deal_type: "sponsored_post",
     status: "lead",
     _csrf_token
   }
   → DealController::store()
   → DealRepository::create()
   → INSERT INTO deals
   → DealWorkflowHelper::afterCreate()
   → AuditLogRepository::log('deal.created')
   → NotificationService::create('deal_created')

2. Creator drags deal to "Negotiation"
   POST /?route=update_deal_status { deal_id: 15, status: "negotiation" }
   → DealController::updateStatus()
   → DealWorkflowHelper::transition(deal, 'negotiation')
   → DealRepository::updateStatus()
   → AuditLogRepository::log('deal.status_changed', old: 'lead', new: 'negotiation')
   → NotificationService::create('deal_updated')

3. Deal moves through pipeline to "Active"
   (same drag-and-drop action, repeated for contract → active stages)

4. Deal is marked "Completed"
   POST /?route=update_deal_status { deal_id: 15, status: "completed" }
   → DealWorkflowHelper::transition() marks completed_at = NOW()
   → AnalyticsRepository::addRevenue(500000)
   → UPDATE analytics SET total_revenue = total_revenue + 500000

5. Creator creates invoice from deal
   POST /?route=create_invoice {
     deal_id: 15,
     recipient_name: "Samsung Tanzania Ltd",
     recipient_email: "finance@samsung.co.tz",
     line_items: '[{"description":"Instagram Posts x3","qty":3,"unit_price":150000}]',
     tax_rate: 0,
     due_date: "2026-07-01",
     _csrf_token
   }
   → InvoiceController::store()
   → InvoiceRepository::generateNumber() → "INV-2026-001"
   → InvoiceRepository::create()
   → INSERT INTO invoices
   → NotificationService::create('invoice_created')

6. Invoice marked as paid
   POST /?route=mark_invoice_paid { invoice_id: 3 }
   → InvoiceController::markPaid()
   → InvoiceRepository::update(status='paid', paid_at=NOW())
   → NotificationService::create('invoice_paid')
```

---

## Workflow 4: Connect Instagram Account

```
1. Creator goes to Settings → Integrations
   GET /?route=settings-integrations
   → SettingsController::integrations()
   → Fetches GET /?route=integrations_data
   → Shows connected platforms, "Connect" buttons for unconnected ones

2. Creator clicks "Connect Instagram"
   → JS: window.location = '/?route=oauth-connect?platform=instagram'

3. OauthController::connectStart()
   → MetaOAuthService::isConfigured() — checks META_APP_ID, META_APP_SECRET
   → MetaOAuthService::allowedPlatforms() — ['instagram','facebook']
   → MetaOAuthService::authorizeUrl('instagram', $state)
   → Builds: https://facebook.com/v20.0/dialog/oauth?client_id={}&scope=instagram_basic,...
   → Store state in session: $_SESSION['oauth_state'] = $state
   → response_redirect($authUrl) → browser goes to Facebook

4. User grants permission on Facebook
   → Facebook redirects to: /?route=oauth-callback?code={code}&state={state}

5. OauthController::callbackHandler()
   → Validate state matches session (IMPORTANT - see security audit)
   → MetaOAuthService::completeConnection(userId, 'instagram', $code)
   → MetaOAuthService::exchangeCode($code)
     → GET graph.facebook.com/v20.0/oauth/access_token?code=...
     → Returns short-lived token (1 hour)
   → MetaOAuthService::longLivedToken($shortToken)
     → GET graph.facebook.com/v20.0/oauth/access_token?grant_type=fb_exchange_token...
     → Returns ~60 day token
   → MetaOAuthService::fetchPages($userToken)
     → GET graph.facebook.com/v20.0/me/accounts?fields=id,...,instagram_business_account{...}
     → Returns list of Facebook Pages with linked Instagram Business accounts
   → MetaOAuthService::saveInstagramAccount(userId, page, igAccount)
     → TokenCrypto::encryptDb($token) — AES-256-CBC encrypt
     → social_accounts UPSERT (INSERT or UPDATE if platform already connected)
   → JobQueueRepository::dispatch('fetch_analytics', {user_id, social_account_id})
   → redirect → ?route=settings-integrations?connected=instagram
```

---

## Workflow 5: Analytics Sync (Background)

```
Every 60 minutes (tracked in backend/storage/cron-state.json):
scripts/cron.php

1. SELECT id, user_id FROM social_accounts WHERE is_active = 1
   → For each account, dispatch fetch_analytics job

2. job_runner_run('analytics', 50)
   → FetchAnalyticsJob::handle({ user_id, social_account_id })

3. SocialAccountRepository::findById(social_account_id)
   → Decrypts access_token via TokenCrypto::decryptDb()

4. SocialApiService::getAnalytics(account, date)
   → Instagram/Facebook:
     GET graph.facebook.com/v20.0/{id}?fields=followers_count&access_token={token}
   → YouTube:
     GET googleapis.com/youtube/v3/channels?part=statistics&id={channelId}
   → Others: Computed from follower_count with reasonable heuristics

5. AnalyticsService::saveSnapshot()
   → INSERT INTO analytics_snapshots ON DUPLICATE KEY UPDATE
   → Stores daily snapshot for this account

6. AnalyticsService::updateRollingTotals(userId)
   → Aggregates across all accounts
   → UPDATE analytics SET total_followers=sum, updated_at=NOW()
```

---

## Workflow 6: Media Upload

```
1. Creator clicks "Upload" in Media Library or Post creation
   → File picker opens

2. POST /?route=upload_media (multipart/form-data)
   { file: [binary], alt_text: "...", _csrf_token }
   → MediaController::upload()
   → MediaUploadHelper::handle($_FILES['file'])
   
3. MediaUploadHelper validates:
   → MIME type against ALLOWED_IMAGE_TYPES/ALLOWED_VIDEO_TYPES
   → File size against UPLOAD_MAX_SIZE (10MB)
   → Generates: md5(filename) + extension
   → Saves to: public/uploads/YYYY/MM/{hash}.ext
   → For images: generates thumbnail (thumb_{hash}.ext)
   
4. MediaFileRepository::create()
   → INSERT INTO media_files {
     file_name, original_name, file_path, thumbnail_url,
     mime_type, file_size, width, height, user_id
   }
   
5. Response: { success: true, data: { media_id: 12, url: "...", thumb_url: "..." } }
   → JS updates media grid with new file
```
