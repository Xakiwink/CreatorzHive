# NotificationPreferenceRepository.php — Explained

**File:** `src/Repositories/NotificationPreferenceRepository.php`

---

## Purpose

Read and write the `notification_preferences` table. One row per user. Supports upsert (insert on first save, update on subsequent saves). All preference values are boolean (stored as 0/1 integers).

---

## Default Preferences

```php
[
    'email_post_published' => 1,
    'email_post_failed' => 1,
    'email_deal_updated' => 1,
    'email_invoice_paid' => 1,
    'email_weekly_summary' => 1,
    'push_post_published' => 1,
    'push_deal_updated' => 1,
]
```

All email and push notifications are on by default.

---

## Methods

### `preferenceGetByUserId(int $userId): ?array`
Returns the preference row or `null` if no row exists yet (user hasn't saved preferences).

### `defaultPreferences(int $userId): array`
Returns the default preference array with `user_id` set.

### `preferenceUpsert(int $userId, array $data): void`
- If no existing row: merges defaults with allowed keys from `$data`, then inserts
- If existing row: builds an UPDATE for only the keys present in `$data` that match the allowed preference keys
- All values coerced to `(int)(bool)$v` — accepts any truthy/falsy input, stores as 0 or 1
- Unknown keys in `$data` are silently ignored

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/NotificationService.php` | Calls `preferenceGetByUserId()` to check if notifications are allowed |
| `src/Controllers/SettingsController.php` | Calls `preferenceUpsert()` on settings save |
| `backend/compat/models.php` | `notification_preference_*` global function wrappers |
