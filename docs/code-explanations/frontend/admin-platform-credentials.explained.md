# admin-platform-credentials.js — Explained

**File:** `frontend/js/admin-platform-credentials.js`

---

## Purpose

Renders and handles the platform API credentials management forms within the admin page. Each platform group (Meta, TikTok, YouTube/Google, Twitter/X) has its own form section with credential fields, status indicators, and connection test buttons.

---

## Rendering

### `renderField(field): string`
Renders a single credential field:
- Secret fields: `type="password"`, `autocomplete="new-password"`
- Non-secret fields: `type="text"`
- Shows current value source: "Saved in admin UI" (green), "From server .env" (yellow), "Not set" (red)
- Shows masked preview (e.g., `••••••••1234`) if configured
- "Remove stored value" checkbox (sets `clear_{fieldKey} = 1` in form submission) — only shown when field is configured

### `renderGroup(container, groupData): void`
Renders an entire credential group (e.g., Meta) with all its fields + connection test buttons.

---

## Connection Test Buttons

Each group has test buttons per platform:
- Meta group: "Test Instagram", "Test Facebook"
- TikTok: "Test TikTok"
- YouTube: "Test YouTube"
- Twitter: "Test X"

Test calls `admin_test_integration?platform=<name>`. Shows Toast success or error.

---

## Form Submission

On form submit:
1. Reads all `credential_*` and `clear_*` inputs
2. POSTs to `admin_update_platform_credentials` with CSRF token
3. Shows success/error toast
4. Reloads the credential form to reflect updated state (clears inputs, shows new status)

---

## API Routes Used

| Action | Route |
|--------|-------|
| Save credentials | `admin_update_platform_credentials` |
| Test connection | `admin_test_integration` |
| Load credential status | (data passed in from `admin_overview` → mounted by `admin-users.js`) |

---

## Notes

- This file is loaded on the admin page alongside `admin-users.js`.
- `admin-users.js` mounts credential forms into `#adminPlatformCredentialsMount` using the data from `admin_overview`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AdminUserController.php` | `admin_update_platform_credentials`, `admin_test_integration` |
| `src/Services/PlatformApiSecretsService.php` | Backend credential storage |
| `frontend/js/admin-users.js` | Mounts this component within the admin page |
