# platforms.php — Explained

**File:** `backend/helpers/platforms.php`

---

## Purpose

Thin procedural bridge to the OOP `PlatformHelper` class. Provides two global functions for working with platform slugs.

---

## Functions

### `platform_slugs_list(): array`
Returns the canonical list of supported platform slugs by delegating to `PlatformHelper::slugs()`.

Result: `['instagram', 'tiktok', 'youtube', 'facebook', 'twitter']`

### `platform_normalize_slug(?string $platform): ?string`
Normalizes a platform string to its canonical slug by delegating to `PlatformHelper::normalize()`.

Returns `null` for unknown or unsupported platforms. Safe to use as a SQL filter guard.

---

## Notes

- Both functions are pure delegation — all logic lives in `src/Helpers/PlatformHelper.php`.
- These global functions allow procedural code (route handlers, compat bridges) to call platform normalization without needing the OOP container.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Helpers/PlatformHelper.php` | OOP class with actual implementation |
| `backend/compat/models.php` | Uses these functions indirectly via repository calls |
