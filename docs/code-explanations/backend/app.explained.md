# app.php — Explained

**File:** `backend/config/app.php`

---

## Purpose

Defines global PHP constants for application-wide configuration. Loaded very early in bootstrap — before any framework code runs.

---

## Constants Defined

| Constant | Value / Source | Description |
|----------|---------------|-------------|
| `APP_NAME` | `'CreatorzHive'` | Application display name |
| `APP_VERSION` | `'1.0.0'` | Current version string |
| `APP_URL` | `env('APP_URL', 'http://localhost/creatorzhive')` | Base URL; used for email links, OAuth redirects |
| `APP_ENV` | `env('APP_ENV', 'development')` | Environment: `development` or `production` |
| `APP_DEBUG` | `env('APP_DEBUG', true)` | Enable verbose error output |
| `APP_TIMEZONE` | `'Africa/Dar_es_Salaam'` | Hardcoded; applied by `date_default_timezone_set()` in `index.php` |
| `UPLOAD_MAX_SIZE` | `10 * 1024 * 1024` (10 MB) | Maximum file upload size in bytes |
| `ALLOWED_IMAGE_TYPES` | `['image/jpeg', 'image/png', 'image/webp', 'image/gif']` | MIME types allowed for image uploads |
| `ALLOWED_VIDEO_TYPES` | `['video/mp4', 'video/webm']` | MIME types allowed for video uploads |

---

## Notes

- `APP_DEBUG=true` in development causes 500 error responses to include exception message and stack trace.
- `ALLOWED_IMAGE_TYPES` and `ALLOWED_VIDEO_TYPES` are defined here but `MediaUploadHelper` (`src/Support/MediaUploadHelper.php`) maintains its own allowed list — these constants are not currently used by the upload flow.
- `functions.php` is auto-loaded if `env()` is not yet defined (guards against direct inclusion).

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Loads this file and calls `date_default_timezone_set(APP_TIMEZONE)` |
| `src/Config/AppConfig.php` | OOP config class with similar values |
| `src/Support/MediaUploadHelper.php` | Has its own MIME type list independent of these constants |
