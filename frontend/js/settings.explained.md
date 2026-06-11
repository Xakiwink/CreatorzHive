# settings.js — Explained

**File:** `frontend/js/settings.js`

---

## Purpose

Handles all settings page interactions across five tabs: profile, security, integrations (platform connections), notifications, and preferences. Also handles admin-specific integration settings view.

---

## Settings Tabs

### Profile Tab
- Avatar upload via file input (uses `FormData` + `apiForm()`)
- Profile fields: name, username, bio, website_url, timezone
- On save: updates `window.__USER__` and calls `window.hydrateSidebarUser()` to reflect changes in sidebar

### Security Tab
- Change password form (current + new + confirmation)
- Active sessions list: shows IP, user agent, last active
- Revoke individual session or "log out all other devices"

### Integrations Tab (Creator)
- Lists 5 platforms with connection status
- "Connect" → redirects to `oauth-connect?platform=<name>` for Meta platforms
- "Connect" for non-OAuth platforms → opens mock token input modal
- "Disconnect" → calls `disconnect_platform` API
- Refresh analytics button per platform → calls `settings_refresh_analytics`

### Integrations Tab (Admin)
- Different view: shows all platform API credential groups
- Displays field status (configured, source: env/UI, masked preview)

### Notifications Tab
- Renders email and push notification preference toggles
- Saves to `settings_notifications` API

### Preferences Tab
- Theme select (light/dark/system) → immediately applies via `window.syncThemeFromStorage()`
- Language, currency, date format, time format, week start, sidebar collapsed preferences
- Saves to `settings_preferences` API

---

## `apiForm(routeName, formData): Promise`
Sends multipart `FormData` POST with CSRF token. Throws on `!success` with all error messages joined.

---

## API Routes Used

| Action | Route |
|--------|-------|
| Save profile | `settings_profile_update` |
| Change password | `settings_password_update` |
| List sessions | `settings_sessions` |
| Revoke session | `settings_session_revoke` |
| Revoke others | `settings_sessions_revoke_others` |
| Load integrations | `settings_integrations_data` |
| Disconnect platform | `disconnect_platform` |
| Refresh analytics | `settings_refresh_analytics` |
| Save notifications | `settings_notifications` |
| Save preferences | `settings_preferences` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/SettingsController.php` | Serves all settings API routes |
| `frontend/js/app.js` | `window.syncThemeFromStorage()`, `window.hydrateSidebarUser()` |
