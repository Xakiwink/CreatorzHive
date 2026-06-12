# services.php — Explained

**File:** `backend/compat/services.php`

---

## Purpose

Auto-generated procedural compatibility bridge for Service classes. Exposes every public OOP Service method as a global PHP function. Mirrors the pattern of `models.php` but for services rather than repositories.

---

## Pattern

```php
function <service_prefix>_<method>(...args) {
    return Application::instance()->get(SomeService::class)->method(...func_get_args());
}
```

---

## Services Bridged

| Global Function Prefix | Service Class |
|-----------------------|--------------|
| `admin_service_*` | `AdminService` |
| `meta_oauth_*` | `MetaOAuthService` |
| `platform_api_secrets_*` | `PlatformApiSecretsService` |
| `analytics_service_*` | `AnalyticsService` |
| `social_api_service_*` | `SocialApiService` |
| `notification_service_*` | `NotificationService` |

---

## Notable Grouped Functions

### AdminService
- `admin_service_settings_get_all()` / `admin_service_settings_save()`
- `admin_service_integration_statuses()` — connected accounts per platform
- `admin_service_validate_saved_credentials()` — live HTTP test after credential save

### MetaOAuthService
- `meta_oauth_authorize_url()`, `meta_oauth_exchange_code()`, `meta_oauth_long_lived_token()`
- `meta_oauth_fetch_pages()` — gets Facebook Pages + linked Instagram accounts
- `meta_oauth_save_facebook_page()`, `meta_oauth_save_instagram_account()`
- `meta_oauth_complete_connection()` — full OAuth flow in one call

### PlatformApiSecretsService
- `platform_api_secrets_resolve(fieldKey)` — env var first, DB stored second
- `platform_api_secrets_encrypt()` / `platform_api_secrets_decrypt()`
- `platform_api_secrets_apply_group_update()` — save platform credentials

### AnalyticsService
- `analytics_service_sync_from_platform()` — fetch from social API, write snapshots
- `analytics_service_aggregate_totals()` — recalculate `analytics` table totals
- `analytics_service_generate_demo_data()` — seed fake data for dev

### SocialApiService
- `social_api_service_publish(account, post)` — publish to a platform
- `social_api_service_get_analytics(account, date)` — fetch metrics
- `social_api_service_mock_enabled()` — check mock mode

### NotificationService
- `notification_service_create_in_app()` — create in-app notification
- `notification_service_maybe_email()` — send email only if preference allows
- Event helpers: `notification_service_post_published()`, `notification_service_deal_completed()`, etc.

---

## Notes

- Auto-generated — do not edit manually.
- Loads after `bootstrap-oop.php` (requires container to be initialized).
- OOP controllers use services via constructor injection; this bridge is for procedural callers, CLI scripts, and the job runner.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/` | All OOP service classes bridged here |
| `backend/compat/models.php` | Same pattern for repositories |
| `backend/compat/auth.php` | Same pattern for AuthService |
| `backend/bootstrap-procedural.php` | Loads this file |
