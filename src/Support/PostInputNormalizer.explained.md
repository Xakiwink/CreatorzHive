# PostInputNormalizer.php — Explained

**File:** `src/Support/PostInputNormalizer.php`
**Namespace:** `CreatorzHive\Support`

---

## Purpose

Cleans messy input from the post create/update form. The frontend may send platforms and IDs as JSON strings, arrays, or comma-separated values depending on how fetch() was called. This class normalizes all formats to clean PHP arrays.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | Tag ownership validation in `syncPostTags()` |
| `$posts` | `PostRepository` | `syncTags()` call after validation |

---

## Methods

### `normalizeIdList($raw): array`

Accepts: null, empty string, JSON string `"[1,2,3]"`, PHP array `[1,2,3]`, or comma-separated string `"1,2,3"`.

Returns a deduplicated list of positive integers.

**Used for:** `media_ids`, `tag_ids`

### `normalizePlatforms($raw): array`

Accepts: null, empty string, JSON string `'["instagram","tiktok"]'`, PHP array, or comma-separated string `"instagram,tiktok"`.

Returns deduplicated list of lowercase platform strings.

**Used for:** `platforms` field in post create/update.

### `syncPostTags(int $postId, array $tagIds, int $userId): void`

Before syncing, validates each tag ID belongs to the requesting user (prevents cross-user tag injection).

If any tag ID is invalid for this user, immediately returns a 422 error via `http_json_error()`.

After validation, calls `PostRepository::syncTags()` (full replace: delete all, re-attach).

---

## Why This Exists

Without this normalization, the post controller would need repeated `json_decode()` / `is_array()` / `array_filter()` chains. Isolating this into a support class keeps the controller clean and makes the logic testable.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/PostController.php` | Primary consumer |
| `src/Repositories/PostRepository.php` | `syncTags()` |
