# SettingsPageHelper.php — Explained

**File:** `src/Support/SettingsPageHelper.php`

---

## Purpose

Helper for the settings controller. Handles avatar file upload, profile payload normalization, and user data preparation for the settings page response.

---

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `AVATAR_MAX_BYTES` | `2097152` (2MB) | Maximum avatar file size |

---

## Methods

### `publicUser(array $user): array`
Prepares a user record for display in settings:
- Strips `password` from the array
- Converts relative `avatar_url` to full URL via `upload_url()`

### `normalizeProfilePayload(array $input): array`
Normalizes the profile form submission:
```php
[
    'name' => trim(string),
    'username' => strtolower(trim(string)),
    'bio' => trim(string),
    'website_url' => trim(string),
    'timezone' => trim(string, default: 'Africa/Dar_es_Salaam'),
]
```

### `avatarUploadErrorMessage(int $code): string`
Maps PHP `$_FILES[x]['error']` codes to human-readable messages.

### `processAvatarUpload(array $file, int $userId): ?string`
Handles avatar upload end-to-end:
1. Validates that the file was actually uploaded (`is_uploaded_file()`)
2. Checks file size ≤ 2MB
3. Detects MIME type via `finfo_file()` (magic bytes, not client Content-Type)
4. Validates against allowed types: JPEG, PNG, GIF, WebP
5. Ensures `public/uploads/avatars/` directory exists
6. Generates filename: `user_{userId}_{16-hex-chars}.{ext}`
7. Moves uploaded file to destination
8. Resizes to 200×200 square via `resizeAvatarSquare()`
9. Returns relative path: `uploads/avatars/user_42_abc123.jpg`
10. Returns `null` on any failure

### `resizeAvatarSquare(string $path, int $size): void` (private)
GD-based square crop + resize:
1. Uses `getimagesize()` to detect image type
2. Loads image with the appropriate `imagecreatefrom*()` function
3. Crops to square by center-cropping from the shortest dimension
4. Resamples to `$size × $size` pixels (200×200 for avatars)
5. Saves in-place in the original format (JPEG quality 88, PNG compression 8)
6. Silent no-op if GD extension not loaded or image cannot be read

---

## Notes

- The MIME detection uses `finfo_file()` — reads magic bytes from the actual file content, not the client-reported `Content-Type` or filename extension.
- GD resize overwrites the original uploaded file — there is no separate thumbnail; the stored file IS the 200×200 avatar.
- Unlike `MediaUploadHelper`, which generates random filenames, avatar filenames include the user ID (`user_42_...`) for easy identification.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/SettingsController.php` | Calls `processAvatarUpload()`, `normalizeProfilePayload()`, `publicUser()` |
| `src/Support/MediaUploadHelper.php` | Similar upload logic for post media (larger files, thumbnails stored separately) |
