# CreatorzHive — Feature Map

> Maps every user-facing feature to the code that implements it.

---

## 1. Authentication Features

### 1.1 Register with Email/Password
- **Route:** `POST ?route=register`
- **Controller:** `AuthController::register()`
- **Service:** `AuthService::generateVerificationToken()`
- **Repository:** `UserRepository::create()`
- **DB Tables:** `users`, `email_verifications`
- **Email Template:** `backend/storage/email-templates/verify-email.html`
- **Frontend:** `frontend/pages/auth/register.php`, `frontend/js/auth.js`

### 1.2 Email Verification
- **Route:** `GET ?route=verify?token=…`
- **Controller:** `AuthController::verify()`
- **Service:** `AuthService::validateVerificationToken()`, `markEmailVerificationUsed()`
- **DB Tables:** `email_verifications`, `users` (sets `email_verified=1`)
- **Frontend:** `frontend/pages/auth/verify-email.php`

### 1.3 Login
- **Route:** `POST ?route=login`
- **Controller:** `AuthController::login()`
- **Service:** `AuthService::checkPassword()`, `AuthRateLimitService::check()`
- **Repository:** `UserRepository::findByEmail()`
- **DB Tables:** `users`, `rate_limits`
- **Frontend:** `frontend/pages/auth/login.php`, `frontend/js/auth.js`

### 1.4 Google Sign-In
- **Routes:** `GET ?route=google-auth` → `GET ?route=google-callback`
- **Controller:** `GoogleAuthController::start()`, `GoogleAuthController::callback()`
- **Service:** `GoogleAuthService`
- **Repository:** `UserRepository`
- **External:** Google OAuth 2.0 (`accounts.google.com/o/oauth2/v2/auth`)

### 1.5 Forgot Password
- **Route:** `POST ?route=forgot-password`
- **Controller:** `AuthController::forgotPassword()`
- **Service:** `AuthService::generatePasswordResetToken()` or `generatePasswordResetOtp()`
- **Email Template:** `backend/storage/email-templates/reset-password.html`

### 1.6 Reset Password
- **Route:** `POST ?route=reset-password`
- **Controller:** `AuthController::resetPassword()`
- **Service:** `AuthService::validateResetToken()`, `markPasswordResetUsed()`, `hashPassword()`
- **Frontend:** `frontend/pages/auth/reset-password.php`

### 1.7 Logout
- **Route:** `POST ?route=logout`
- **Controller:** `AuthController::logout()`
- **Action:** `session_destroy_all()`

---

## 2. Dashboard Features

### 2.1 Dashboard Overview
- **Route:** `GET ?route=dashboard` (page), `GET ?route=dashboard_data` (API)
- **Controller:** `DashboardController::index()`, `DashboardController::data()`
- **Service:** `DashboardService`
- **Repository:** `DashboardRepository`
- **DB Views:** `v_creator_summary`, `v_upcoming_posts`
- **Frontend:** `frontend/pages/dashboard/index.php`, `frontend/js/dashboard.js`

**Data displayed:**
- Total followers (sum across platforms)
- Total posts, published, scheduled, failed
- Upcoming scheduled posts (next 14 days)
- Recent notifications
- Revenue summary
- Active deals count

---

## 3. Content Planner Features

### 3.1 View Posts (Calendar/List)
- **Route:** `GET ?route=planner` (page), `GET ?route=posts` or `posts_calendar` (API)
- **Controller:** `PostController::plannerPage()`, `index()`, `calendar()`
- **Repository:** `PostRepository`
- **Frontend:** `frontend/pages/planner/index.php`, `frontend/js/planner.js`

### 3.2 Create Post
- **Route:** `POST ?route=create_post`
- **Controller:** `PostController::store()`
- **Support:** `PostInputNormalizer::normalize()`
- **Repository:** `PostRepository::create()`, `attachMedia()`, `attachTags()`
- **DB Tables:** `posts`, `post_media`, `post_tags`
- **Side effect:** Dispatches `PublishPostJob` if `scheduled_at` is set

### 3.3 Edit Post
- **Route:** `POST ?route=update_post`
- **Controller:** `PostController::update()`
- **Repository:** `PostRepository::update()`

### 3.4 Delete Post (Soft)
- **Route:** `POST ?route=delete_post`
- **Controller:** `PostController::destroy()`
- **Repository:** `PostRepository::softDelete()`

### 3.5 Duplicate Post
- **Route:** `POST ?route=duplicate_post`
- **Controller:** `PostController::duplicate()`
- **Repository:** `PostRepository::duplicate()`

### 3.6 Bulk Status Update
- **Route:** `POST ?route=bulk_posts`
- **Controller:** `PostController::bulk()`

### 3.7 Background Publish
- **Triggered by:** cron.php every minute
- **Job:** `PublishPostJob`
- **Service:** `SocialApiService::publish()`
- **Platforms:** Instagram (2-step), Facebook, TikTok, YouTube, Twitter
- **DB Tables:** `platform_post_results`, updates `posts.status`

---

## 4. Analytics Features

### 4.1 Analytics Dashboard
- **Route:** `GET ?route=analytics` (page), `GET ?route=analytics_data` (API)
- **Controller:** `AnalyticsController::index()`, `data()`
- **Repository:** `AnalyticsRepository`
- **Support:** `AnalyticsReportHelper`
- **DB Views:** `v_deal_revenue`, `v_post_performance`
- **Frontend:** `frontend/pages/analytics/index.php`, `frontend/js/analytics.js`
- **Charts:** Chart.js (self-hosted)

**Displayed:**
- Follower growth over time
- Engagement rate trends
- Impressions by platform
- Revenue summary
- Post performance per platform

### 4.2 Analytics Sync (Background)
- **Triggered by:** cron.php every 60 minutes
- **Job:** `FetchAnalyticsJob`
- **Service:** `SocialApiService::getAnalytics()`
- **DB Tables:** `analytics_snapshots`, `analytics`

---

## 5. Deal Management Features

### 5.1 Deals Board (Kanban)
- **Route:** `GET ?route=deals` (page), `GET ?route=deals_data` (API)
- **Controller:** `DealController::index()`, `data()`
- **Repository:** `DealRepository`
- **Frontend:** `frontend/pages/monetization/deals.php`, `frontend/js/deals.js`

### 5.2 Create Deal
- **Route:** `POST ?route=create_deal`
- **Controller:** `DealController::store()`
- **Support:** `DealWorkflowHelper`

### 5.3 Update Deal Status (Kanban Move)
- **Route:** `POST ?route=update_deal_status`
- **Controller:** `DealController::updateStatus()`
- **Support:** `DealWorkflowHelper` (creates audit log + notification)
- **Notification:** "Deal moved to [new stage]"

### 5.4 Delete Deal
- **Route:** `POST ?route=delete_deal`
- **Controller:** `DealController::destroy()`
- **Action:** Soft delete (`is_deleted=1`)

---

## 6. Invoice Features

### 6.1 Invoice List
- **Route:** `GET ?route=invoices` (page), `GET ?route=invoices_data` (API)
- **Controller:** `InvoiceController::index()`, `list()`
- **Frontend:** `frontend/pages/monetization/invoices.php`, `frontend/js/invoices.js`

### 6.2 Create Invoice
- **Route:** `POST ?route=create_invoice`
- **Controller:** `InvoiceController::store()`
- **Repository:** `InvoiceRepository::create()`

### 6.3 Mark Invoice Paid
- **Route:** `POST ?route=mark_invoice_paid`
- **Controller:** `InvoiceController::markPaid()`
- **Side effect:** Notification "Invoice paid", updates `analytics.total_revenue`

---

## 7. Media Library Features

### 7.1 Browse Media
- **Route:** `GET ?route=media` (page), `GET ?route=media_list` (API)
- **Controller:** `MediaController::index()`, `list()`
- **Frontend:** `frontend/pages/media/index.php`, `frontend/js/media.js`

### 7.2 Upload Media
- **Route:** `POST ?route=upload_media`
- **Controller:** `MediaController::upload()`
- **Support:** `MediaUploadHelper`
- **Storage:** `public/uploads/YYYY/MM/{md5_hash}.ext`
- **Thumbnail:** Auto-generated for images

### 7.3 Delete Media
- **Route:** `POST ?route=delete_media`
- **Controller:** `MediaController::delete()`
- **Action:** Physical file delete + DB record delete

---

## 8. Notification Features

### 8.1 Notification Feed
- **Route:** `GET ?route=notifications` (page), `GET ?route=notifications_data` (API)
- **Controller:** `NotificationController::index()`, `data()`
- **Frontend:** `frontend/pages/notifications/notifications.php`, `frontend/js/notifications.js`

### 8.2 Unread Badge
- **Route:** `GET ?route=notifications_count`
- **Controller:** `NotificationController::unreadCount()`
- **Usage:** Polled by dashboard to update notification bell badge

### 8.3 Notification Actions
- `POST ?route=mark_read` — single notification
- `POST ?route=mark_all_read` — all notifications
- `POST ?route=delete_notification` — single notification
- `POST ?route=delete_read_notifications` — bulk delete read

---

## 9. Settings Features

### 9.1 Profile Settings
- **Route:** `GET ?route=settings-profile` (page), `GET ?route=profile_data` (API)
- **Controller:** `SettingsController::profile()`, `profileData()`
- **POST:** `update_profile` — name, username, bio, website, avatar
- **Frontend:** `frontend/pages/settings/profile.php`, `frontend/js/settings.js`

### 9.2 Security Settings
- **Route:** `GET ?route=settings-security`
- **POST:** `update_password`, `revoke_session`, `revoke_all_sessions`
- **Data:** `GET ?route=user_sessions`

### 9.3 Platform Integrations
- **Route:** `GET ?route=settings-integrations`
- **Data:** `GET ?route=integrations_data`
- **Actions:** `connect_platform`, `disconnect_platform`
- **OAuth:** `?route=oauth-connect?platform=instagram`

### 9.4 Notification Preferences
- **Route:** `GET ?route=settings-notifications`
- **Data:** `GET ?route=notification_prefs`
- **POST:** `update_notification_prefs`

### 9.5 UI Preferences
- **Route:** `GET ?route=settings-preferences`
- **POST:** `update_preferences` (theme, language, currency, date_format)

---

## 10. Admin Features

### 10.1 User Management
- **Route:** `GET ?route=admin-users` (page), `GET ?route=admin_users` (API)
- **Controller:** `AdminUserController::usersPage()`, `usersIndex()`
- **Actions:** `admin_create_user`, `admin_update_user`, `admin_delete_user`, `admin_verify_user`
- **Frontend:** `frontend/pages/settings/admin-users.php`, `frontend/js/admin-users.js`

### 10.2 Platform Credential Management
- **Route:** `GET ?route=admin_platform_credentials`
- **Controller:** `AdminUserController::platformCredentials()`
- **Service:** `PlatformApiSecretsService`
- **Frontend:** `frontend/js/admin-platform-credentials.js`

### 10.3 Audit Logs
- **Route:** `GET ?route=admin_audit_logs`
- **Controller:** `AdminUserController::auditLogsIndex()`
- **Repository:** `AuditLogRepository`

### 10.4 System Overview
- **Route:** `GET ?route=admin_overview`
- **Controller:** `AdminUserController::platformOverview()`

---

## 11. Tags Feature

### 11.1 List Tags
- **Route:** `GET ?route=tags`
- **Controller:** `TagController::index()`

### 11.2 Create Tag
- **Route:** `POST ?route=create_tag`
- **Controller:** `TagController::store()`
- **Repository:** `TagRepository::create()`
