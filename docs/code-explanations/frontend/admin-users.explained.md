# admin-users.js — Explained

**File:** `frontend/js/admin-users.js`

---

## Purpose

Renders the admin user management page. Handles: user list with inline editing, create user, verify email, delete user, admin settings form, platform integration status overview, and audit log table.

---

## Sections

### User Table (`#adminUsersBody`)
- Loaded via `admin_users` API
- Each row is editable in-place: name, username, email, role (select)
- Actions per row: Save, Verify Email, Delete

### Admin Overview (`admin_overview` API)
Loads and displays:
- **Admin settings form**: registration enabled, require email verification, forgot password enabled, site display name, support email, maintenance mode + message, max upload size, admin note
- **Summary cards**: total users, active users, unverified users, active sessions, pending jobs
- **Integration status table**: per platform: enabled toggle, token configured (UI/env/.env), connected accounts count, expiring-soon warning count, "Test connection" button

### Create User Form (`#adminCreateUserForm`)
Fields: name, username, email, password, role (creator/brand/admin). Posts to `admin_create_user`.

### Audit Log (`#adminAuditBody`)
Loads via `admin_audit_log` API. Shows: timestamp, actor email, action, entity type/ID, IP.

---

## API Routes Used

| Action | Route |
|--------|-------|
| Load users | `admin_users` |
| Load overview | `admin_overview` |
| Save user | `admin_user_update` |
| Verify user email | `admin_user_verify` |
| Delete user | `admin_user_delete` |
| Create user | `admin_create_user` |
| Save admin settings | `admin_settings_update` |
| Test integration | `admin_test_integration` |
| Load audit log | `admin_audit_log` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AdminUserController.php` | Serves all admin API routes |
| `frontend/js/admin-platform-credentials.js` | Mounted inside admin page for credential forms |
| `frontend/js/app.js` | `window.api()`, `window.Toast` |
