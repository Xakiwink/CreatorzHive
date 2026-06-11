# AdminService.php — Explained

**File:** `src/Services/AdminService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

Manages platform-wide configuration settings stored in a JSON file, and provides admin dashboard data (platform summary, integration health). Settings are not in the database — they're stored in `backend/storage/app-settings.json`.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | Platform summary queries (user counts, job counts) |

---

## Settings Storage

Settings are stored as JSON at `backend/storage/app-settings.json`. This file is:
- Created on first save
- Merged with defaults on read (new settings added to code don't break old JSON files)
- Protected from web access (under `backend/` not `public/`)

---

## Methods

### `settingsDefaults(): array`
Returns all setting keys with their default values. Add new settings here to make them available without requiring a DB migration.

**Default values:**
```
registration_enabled: true
require_email_verification: true
forgot_password_enabled: true
admin_note: ''
site_display_name: 'CreatorzHive'
support_email: ''
maintenance_mode: false
maintenance_message: ''
max_upload_mb: 2
integration_enabled_instagram: true
integration_enabled_tiktok: true
integration_enabled_youtube: true
integration_enabled_twitter: true
integration_enabled_facebook: true
```

### `settingsGetAll(): array`
Reads and parses `app-settings.json`. Merges with defaults so missing keys always have values. Returns defaults if file missing or invalid JSON.

### `settingsSave(array $settings): bool`
Writes settings to JSON file atomically (with `LOCK_EX`). Creates storage directory if needed. Returns false on write failure.

### `settingBool(string $key, bool $default): bool`
Convenience method: get a single boolean setting. Used in controllers to check feature flags.

### `platformSummary(): array`
Direct DB queries for admin overview:
- `users_total`: all users
- `users_active`: is_active=1
- `users_unverified`: email_verified=0
- `sessions_active`: count in sessions table
- `jobs_pending`: count pending+running in job_queue

### `integrationProviders(): array`
Static registry of platform metadata (label, token env key, API test URL). Used by both `integrationStatuses()` and `AdminUserController::integrationTest()`.

### `integrationEnabled(string $platform): bool`
Returns whether an integration is admin-enabled. Missing key defaults to `true` (safe default).

### `integrationStatuses(): array`
For each platform: enabled flag, token configured, token source, active connected accounts count, expiring-soon accounts count (within 7 days). Used in admin overview panel.

### `validateSavedCredentials(string $group, array $savedFieldKeys): array`
After saving credentials, runs live HTTP tests against each platform's test URL. Accumulates warnings if tokens are empty or requests fail. Returns `{ ok: bool, warnings: string[] }`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AdminUserController.php` | Calls most methods |
| `src/Services/PlatformApiSecretsService.php` | Credential resolution used in integration statuses |
| `backend/storage/app-settings.json` | Persistent storage file |
| `backend/compat/services.php` | `admin_service_*()` procedural wrappers |
