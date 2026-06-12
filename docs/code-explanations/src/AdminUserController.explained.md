# AdminUserController.php — Explained

**File:** `src/Controllers/AdminUserController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Admin-only controller. Manages users (CRUD), platform settings, integration credentials, audit logs, and integration health checks. All routes require `role:admin` middleware.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$users` | `UserRepository` | List/find/create/update/delete users |
| `$admin` | `AdminService` | Platform settings (feature flags, integration states) |
| `$audit` | `AuditLogRepository` | Write and read audit log entries |
| `$secrets` | `PlatformApiSecretsService` | Read/write platform API credentials |
| `$socialApi` | `SocialApiService` | HTTP requests for integration test |
| `$auth` | `AuthService` | Password hashing when creating/updating users |

---

## Page Methods

| Method | Route | Template |
|--------|-------|----------|
| `usersPage()` | `GET admin-users` | `settings/admin-users` |

---

## API Methods

### `usersIndex()` — GET api/admin_users
Lists all users with pagination (`limit`, `offset`). Returns user rows without password hashes.

### `platformOverview()` — GET api/admin_overview
Returns combined admin dashboard:
- `settings`: all app settings (registration enabled, etc.)
- `summary`: platform-wide stats (total users, posts, deals)
- `integrations`: which platforms have tokens configured
- `platform_credentials`: public (masked) credential values per group

### `platformCredentials()` — GET api/admin_platform_credentials
Returns credentials for a specific group (default: `meta`). Groups include `meta`, `google`, `tiktok`, `youtube`, `twitter`.

### `updatePlatformCredentials()` — POST api/admin_update_platform_credentials
Updates or clears API credentials for a platform group. Validates group name. Writes audit log entry. Returns validation warnings if any saved value looks suspicious.

### `settingsUpdate()` — POST api/admin_update_settings
Bulk-updates platform settings. Handles: registration_enabled, require_email_verification, forgot_password_enabled, admin_note, site_display_name, support_email, maintenance_mode, maintenance_message, max_upload_mb, and per-integration enabled flags.

All changes are audit-logged with before/after values.

### `integrationTest()` — GET api/admin_test_integration
Makes a live HTTP request to the platform's test URL using the configured token. Returns HTTP status on success. Platforms and their test URLs are defined in `AdminService::integrationProviders()`.

### `auditLogsIndex()` — GET api/admin_audit_logs
Returns recent N audit log entries (default 100). Used in admin UI to track admin actions.

### `usersStore()` — POST api/admin_create_user
Creates a user bypassing normal registration flow (no verification email, no rate limits). Supports any role including `admin`. Sets `email_verified` and `is_active` explicitly. Audit-logged.

### `usersUpdate()` — POST api/admin_update_user
Updates user fields individually. Validates username uniqueness, email uniqueness, role enum. Password update allowed (min 8 chars, no confirmation required). Audit-logged.

### `usersDestroy()` — POST api/admin_delete_user
Hard-deletes user (no soft-delete). Prevents self-deletion. Audit-logged.

### `usersVerify()` — POST api/admin_verify_user
Manually marks a user's email as verified without them clicking a link. Audit-logged.

---

## Security

- All routes have `role:admin` middleware (`RoleMiddleware::requireAdmin()`)
- Admin cannot delete their own account
- All mutations write to audit_logs with actor ID

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/AdminService.php` | Settings, platform summary, integration providers |
| `src/Services/PlatformApiSecretsService.php` | Credential storage/masking |
| `src/Repositories/AuditLogRepository.php` | Audit log writes/reads |
| `src/Repositories/UserRepository.php` | User management |
