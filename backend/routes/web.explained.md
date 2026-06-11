# web.php — Explained

**File:** `backend/routes/web.php`

---

## Purpose

Registers all HTML page routes. These routes respond with rendered PHP/HTML views (not JSON). Loaded by `backend/index.php` before `routes/api.php`.

---

## Route Table

| Route | Controller | Method | Middleware |
|-------|-----------|--------|-----------|
| `login` | `AuthController` | `loginPage` | — |
| `register` | `AuthController` | `registerPage` | — |
| `forgot-password` | `AuthController` | `forgotPage` | — |
| `reset-password` | `AuthController` | `resetPage` | — |
| `verify-email` | `AuthController` | `verifyPage` | — |
| `logout` | `AuthController` | `logoutPage` | — |
| `dashboard` | `DashboardController` | `index` | `auth` |
| `planner` | `PostController` | `plannerPage` | `auth`, `non_admin` |
| `analytics` | `AnalyticsController` | `index` | `auth`, `non_admin` |
| `deals` | `DealController` | `index` | `auth`, `non_admin` |
| `invoices` | `InvoiceController` | `index` | `auth`, `non_admin` |
| `media` | `MediaController` | `index` | `auth`, `non_admin` |
| `notifications` | `NotificationController` | `index` | `auth` |
| `settings` | `SettingsController` | `profile` | `auth` |
| `settings-profile` | `SettingsController` | `profile` | `auth` |
| `settings-security` | `SettingsController` | `security` | `auth` |
| `settings-integrations` | `SettingsController` | `integrations` | `auth` |
| `settings-notifications` | `SettingsController` | `notifications` | `auth` |
| `settings-preferences` | `SettingsController` | `preferences` | `auth` |
| `admin-users` | `AdminUserController` | `usersPage` | `auth`, `role:admin` |
| `google-auth` | `GoogleAuthController` | `start` | — |
| `google-callback` | `GoogleAuthController` | `callback` | — |
| `oauth-connect` | `OauthController` | `connectStart` | `auth` |
| `oauth-callback` | `OauthController` | `callbackHandler` | — |

---

## Notes

- All routes use `GET` only (HTML pages don't handle POST — POST requests go to API routes in `api.php`)
- Routes without `auth` middleware are publicly accessible (login, register, Google auth callbacks, OAuth callback)
- `non_admin` middleware blocks admin users from creator-only pages
- `settings` and `settings-profile` point to the same handler — `settings` is the canonical alias
- Google and OAuth callback routes have no `auth` middleware because the user is completing a flow that starts unauthenticated

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Loads this file |
| `backend/routes/api.php` | JSON API routes loaded after this |
| `backend/core/router.php` | `router_get_action()` function |
