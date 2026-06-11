# models.php — Explained

**File:** `backend/compat/models.php`

---

## Purpose

Auto-generated procedural compatibility bridge. Exposes every public OOP Repository method as a global PHP function. Allows legacy procedural code and route handlers to call repository methods without needing the DI container directly.

---

## Pattern

Every function follows this pattern:
```php
function <entity>_<method>(...args) {
    return Application::instance()->get(SomeRepository::class)->method(...func_get_args());
}
```

`func_get_args()` is used so argument lists don't need to be maintained — changes to repository signatures automatically propagate.

---

## Repositories Bridged

| Global Function Prefix | Repository Class |
|-----------------------|-----------------|
| `user_*` | `UserRepository` |
| `deal_*` | `DealRepository` |
| `invoice_*` | `InvoiceRepository` |
| `tag_*` | `TagRepository` |
| `notification_*` | `NotificationRepository` |
| `notification_preference_*` | `NotificationPreferenceRepository` |
| `user_preferences_*` | `UserPreferencesRepository` |
| `user_session_*` | `UserSessionRepository` |
| `media_file_*` | `MediaFileRepository` |
| `social_account_*` | `SocialAccountRepository` |
| `job_queue_*` | `JobQueueRepository` |
| `audit_log_*` | `AuditLogRepository` |
| `analytics_*` | `AnalyticsRepository` |
| `post_*` | `PostRepository` |

---

## Examples

```php
// Find user by email (calls UserRepository::findByEmail)
$user = user_find_by_email('alice@example.com');

// Get all posts for a user
$posts = post_get_all_by_user($userId, ['status' => 'published']);

// Enqueue a publish job
job_queue_enqueue_publish_post($postId);
```

---

## Notes

- This file is auto-generated (marked as such in the comment at the top). Do not edit manually.
- The file must load after `bootstrap-oop.php` because `Application::instance()` requires the container to be initialized.
- OOP code (controllers, services) uses repositories directly via constructor injection — this bridge is for procedural callers only.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/` | All OOP repository classes bridged here |
| `backend/bootstrap-procedural.php` | Loads this file |
| `backend/compat/services.php` | Same pattern for Service classes |
