# MediaFileRepository.php — Explained

**File:** `src/Repositories/MediaFileRepository.php`

---

## Purpose

All database queries for the `media_files` table. Handles listing, fetching, creating, and deleting media records. The `fileDelete()` method also physically removes files from disk.

---

## Methods

### `fileGetByUser(int $userId, ?string $type): array`
Lists all media files for a user, ordered by `created_at DESC`. Optional `$type` filter:
- `'image'` → `mime_type LIKE 'image/%'`
- `'video'` → `mime_type LIKE 'video/%'`
- `null` / other → all files

### `fileFindById(int $id): ?array`
Fetches a file by ID with no ownership check. Used by job runners and admin operations.

### `fileFindByIdForUser(int $id, int $userId): ?array`
Ownership-scoped fetch. Delegates to `findByIdForUser()`.

### `fileCreate(array $data): int`
Inserts a new media record. Expected fields:
- Required: `user_id`, `file_name`, `original_name`, `file_path`, `mime_type`, `file_size`
- Optional: `cdn_url`, `thumbnail_url`, `width`, `height`, `duration`, `alt_text`

Returns the new file ID.

### `fileDelete(int $id, int $userId): bool`
1. Fetches the record (ownership check — returns `false` if not found)
2. Deletes the physical file at `public_path(file_path)` with `@unlink`
3. Deletes the thumbnail file if `thumbnail_url` is set, different from `file_path`, and starts with `uploads/` (relative paths only — absolute URLs not deleted)
4. Deletes the DB record
5. Returns `true`

File deletion errors are silenced (`@unlink`) — DB record is deleted regardless.

---

## Notes

- No soft delete — records are hard-deleted.
- The orphan cleanup job (`CleanupMediaJob`) handles media files not referenced by any post after 30 days.
- Thumbnail deletion guard (`starts with 'uploads/'`) prevents trying to delete external CDN URLs.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/MediaController.php` | Calls `fileCreate()`, `fileDelete()` |
| `src/Jobs/CleanupMediaJob.php` | Queries orphan media files |
| `backend/compat/models.php` | `media_file_*` global function wrappers |
