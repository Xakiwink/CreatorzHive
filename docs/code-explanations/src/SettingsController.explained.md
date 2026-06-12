# SettingsController.php — Explained

**File:** `src/Controllers/SettingsController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Handles all user settings pages and their JSON API endpoints. Covers: profile (name, avatar, bio), password change, session management, social account connections, notification preferences, and UI preferences. One controller, five settings panels.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$settingsPage` | `SettingsPageHelper` | Avatar upload processing, public-safe user normalization |
| `$users` | `UserRepository` | Fetch/update user records |
| `$preferences` | `UserPreferencesRepository` | Theme, language, currency, date format prefs |
| `$sessions` | `UserSessionRepository` | List and revoke sessions |
| `$notificationPrefs` | `NotificationPreferenceRepository` | Email/push notification toggles |
| `$socialAccounts` | `SocialAccountRepository` | Platform connection state |
| `$auth` | `AuthService` | Password verification and hashing |
| `$admin` | `AdminService` | Check integration enabled flags |
| `$metaOAuth` | `MetaOAuthService` | Get configured OAuth platforms |
| `$jobs` | `JobQueueRepository` | Dispatch analytics fetch after platform connect |

---

## Page Render Methods

All render `settings/profile` template with a `settings_panel` variable that controls which tab the SPA shows.

| Method | Route | Panel |
|--------|-------|-------|
| `profile()` | `GET settings-profile` | `profile` |
| `security()` | `GET settings-security` | `security` |
| `integrations()` | `GET settings-integrations` | `integrations` |
| `notifications()` | `GET settings-notifications` | `notifications` |
| `preferences()` | `GET settings-preferences` | `preferences` |

---

## API Methods

### `profileData()` — GET api/profile_data
Returns `{ user: {...public fields...}, preferences: {...} }`. Uses `SettingsPageHelper::publicUser()` to strip sensitive fields.

### `updateProfile()` — POST api/update_profile
Updates: name, username, bio, website_url, timezone (default Africa/Dar_es_Salaam).

If `avatar` file uploaded:
- Validates upload error code
- Delegates to `SettingsPageHelper::processAvatarUpload()` for resize/save
- Stores relative path in `users.avatar_url`

After save, refreshes session with new user data (keeps session current without re-login).

### `updatePassword()` — POST api/update_password
Verifies current password before allowing change. Validates new password match. Min 8 chars.

### `getSessions()` — GET api/user_sessions
Lists sessions from DB. Marks which one is current via `session_id()` comparison.

### `revokeSession()` — POST api/revoke_session
Revokes a specific session by ID (only if owned by current user).

### `revokeAllSessions()` — POST api/revoke_all_sessions
Revokes all sessions **except** the current one (so user stays logged in).

### `integrationsData()` — GET api/integrations_data
Returns connected social account summaries + which platforms have OAuth configured.

### `connectPlatform()` — POST api/connect_platform
Connects a platform manually (non-OAuth mock flow). Validates platform name, checks admin integration-enabled flag. Generates mock tokens if not provided. After save, dispatches `fetch_analytics` job for the new account.

### `disconnectPlatform()` — POST api/disconnect_platform
Sets `social_accounts.is_active = 0` for the platform.

### `notificationPrefs()` — GET api/notification_prefs
Returns user's notification preferences. Falls back to defaults if no record exists.

### `updateNotificationPrefs()` — POST api/update_notification_prefs
Updates specific notification preference toggles (email_post_published, push_post_published, etc.).

### `updatePreferences()` — POST api/update_preferences
Updates UI preferences: theme (light/dark/system), language, default_currency, date_format, time_format (12h/24h), week_starts_on, sidebar_collapsed.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Support/SettingsPageHelper.php` | Avatar upload, payload normalization |
| `src/Repositories/UserSessionRepository.php` | Session list/revoke |
| `src/Repositories/SocialAccountRepository.php` | Platform connections |
| `src/Services/MetaOAuthService.php` | OAuth platform config |
| `frontend/js/settings.js` | Calls all these endpoints |
