# MediaController.php — Explained

**File:** `src/Controllers/MediaController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Handles media file upload, listing, and deletion. Uploads are validated by MIME type (not extension), stored in dated subdirectories under `public/uploads/YYYY/MM/`, and thumbnails auto-generated for images.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$media` | `MediaFileRepository` | DB CRUD for media_files table |
| `$uploads` | `MediaUploadHelper` | MIME→extension map, thumbnail generation |

---

## Methods

### `index()` — GET media (page)
Renders `media/index` template.

### `list()` — GET api/media_list

Returns paginated list of user's uploaded files.

Query params: `type` (image|video), `page`, `per_page` (max 50).

Fetches all from DB, then applies in-PHP pagination (simple offset slice). Fixes relative `cdn_url` and `thumbnail_url` paths using `upload_url()`.

**Note:** In-PHP pagination is acceptable for current scale but would need DB-level pagination for large libraries.

### `upload()` — POST api/upload_media

**Upload pipeline:**

1. Validate file present and `UPLOAD_ERR_OK`
2. Check size ≤ `MediaUploadHelper::MAX_BYTES` (10MB)
3. Detect MIME type via `finfo_file()` (libmagic — not client-supplied `Content-Type`)
4. Look up extension from MIME map (rejects unknown types)
5. Create `public/uploads/YYYY/MM/` directory if needed
6. Generate cryptographically random filename: `bin2hex(random_bytes(16)).<ext>`
7. `move_uploaded_file()` to destination (or `copy()` in test mode)
8. For images: `getimagesize()` for dimensions, generate 300px thumbnail via `MediaUploadHelper::writeImageThumbnail()`
9. Insert record into `media_files` table
10. Return public URL via `upload_url()`

**Test isolation:** If `CREATORZHIVE_PHPUNIT` constant is defined, `copy()` is used instead of `move_uploaded_file()` to allow test fixtures.

### `delete()` — POST api/delete_media

Calls `MediaFileRepository::fileDelete($id, $userId)` — only deletes if owned by requesting user.

---

## Security Notes

- MIME detection via `finfo_file()` — file magic bytes, not extension or `$_FILES['type']`
- Random filename prevents enumeration and directory traversal
- Ownership enforced on delete (can't delete another user's media)

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/MediaFileRepository.php` | DB operations |
| `src/Support/MediaUploadHelper.php` | MIME map, thumbnail generation |
| `frontend/js/media.js` | Calls upload/list/delete endpoints |
