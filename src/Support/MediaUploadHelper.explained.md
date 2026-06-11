# MediaUploadHelper.php — Explained

**File:** `src/Support/MediaUploadHelper.php`
**Namespace:** `CreatorzHive\Support`

---

## Purpose

Upload utility: provides the MIME-to-extension map and thumbnail generation for `MediaController`. Isolates the GD library dependency from the controller.

---

## Constants

```php
MAX_BYTES = 10485760  // 10MB
```

---

## Methods

### `mimeExtensions(): array`

Returns the allowed MIME types and their corresponding file extensions:

| MIME Type | Extension |
|-----------|-----------|
| `image/jpeg` | `jpg` |
| `image/png` | `png` |
| `image/gif` | `gif` |
| `image/webp` | `webp` |
| `video/mp4` | `mp4` |
| `video/webm` | `webm` |

Files with any other MIME type are rejected by `MediaController`.

### `writeImageThumbnail(string $sourcePath, string $destPath, int $maxWidth): bool`

Creates a resized JPEG thumbnail using PHP's GD library.

**Process:**
1. Requires GD extension — returns false if not loaded
2. Reads image dimensions via `getimagesize()`
3. Loads image resource based on type (JPEG, PNG, GIF, WebP)
4. Calculates scale factor: `min(1.0, maxWidth / srcWidth)` — never upscales
5. Resamples to new dimensions with `imagecopyresampled()` (high-quality)
6. Saves as JPEG at quality 85 to `$destPath`
7. Creates destination directory if needed

Returns `false` if: GD not loaded, invalid image, GD operation fails.

---

## Notes

- WebP support requires GD compiled with WebP support (`imagecreatefromwebp`)
- Videos are allowed but no thumbnail is generated for them (would need FFmpeg)
- Thumbnails are always saved as JPEG regardless of source format

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/MediaController.php` | Calls `mimeExtensions()` and `writeImageThumbnail()` |
