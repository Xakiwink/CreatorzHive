# PlatformHelper.php — Explained

**File:** `src/Helpers/PlatformHelper.php`
**Namespace:** `CreatorzHive\Helpers`

---

## Purpose

Central registry of valid social media platform slugs and a normalization utility. Used anywhere a platform name is received from user input or database to ensure consistency and prevent injection via unexpected values.

---

## Methods

### `slugs(): array`

Returns the canonical platform slug list:
```php
['instagram', 'tiktok', 'youtube', 'facebook', 'twitter']
```

### `normalize(?string $platform): ?string`

Converts a platform string to its canonical lowercase slug, or returns `null` if invalid.

- `null` or empty → `null`
- `'Instagram'` → `'instagram'`
- `'TIKTOK'` → `'tiktok'`
- `'snapchat'` → `null` (not supported)

**Used for:**
- Filter validation in `PostRepository::getAllByUser()`
- Platform parameter in `AnalyticsRepository` queries
- Input validation in `SocialAccountRepository::accountFetch()`

---

## Why This Exists

Without normalization, a user-supplied `?platform=Instagram` query param would fail a direct database comparison against lowercase stored values. More importantly, it prevents SQL injection via platform name in `JSON_CONTAINS` queries.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/PostRepository.php` | Platform filter in `getAllByUser()` |
| `src/Repositories/AnalyticsRepository.php` | `sqlWithPlatformFilter()` |
| `src/Repositories/SocialAccountRepository.php` | `accountFetch()` |
