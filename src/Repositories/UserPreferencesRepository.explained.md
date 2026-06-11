# UserPreferencesRepository.php — Explained

**File:** `src/Repositories/UserPreferencesRepository.php`

---

## Purpose

Read and write the `user_preferences` table. One row per user. Stores UI/UX preferences like theme, language, currency, date format, and sidebar state. Supports upsert with defaults.

---

## Default Preferences

```php
[
    'theme' => 'system',
    'language' => 'en',
    'default_currency' => 'TZS',
    'date_format' => 'Y-m-d',
    'time_format' => '24h',
    'week_starts_on' => 1,     // 1 = Monday
    'sidebar_collapsed' => 0,
]
```

---

## Methods

### `preferencesGetByUserId(int $userId): ?array`
Returns the preference row or `null` if no row exists.

### `preferencesUpsert(int $userId, array $data): void`
- If no existing row: merges defaults with `$data` (including `user_id`), then inserts
- If existing row: updates only the allowed columns that are present in `$data`
- Allowed update columns: `theme`, `language`, `default_currency`, `date_format`, `time_format`, `week_starts_on`, `sidebar_collapsed`

---

## Notes

- The `week_starts_on` value is an integer (1 = Monday, 0 = Sunday) matching MySQL's convention.
- The `trg_after_user_insert` MySQL trigger auto-creates this row on user registration, so `null` results are rare in practice.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/SettingsController.php` | Calls `preferencesUpsert()` on preferences save |
| `backend/compat/models.php` | `user_preferences_*` global function wrappers |
