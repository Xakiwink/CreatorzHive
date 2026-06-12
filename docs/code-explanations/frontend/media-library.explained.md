# media-library.js — Explained

**File:** `frontend/js/media-library.js`

---

## Purpose

Renders the media management page. Handles file upload via drag-and-drop or file picker, paginated media grid, type filtering (all/image/video), copy URL, and delete.

---

## State

- `page`: current page (1-based)
- `typeFilter`: `''` (all), `'image'`, or `'video'`
- `PER_PAGE = 24`

---

## Key Features

### Media Grid
Each card shows thumbnail (image `<img>` or muted `<video>` for videos), filename, MIME type, file size, copy URL button, and delete button.

### Upload
- Drag-and-drop support via `initDropZone()` from `window.Media`
- Click-to-pick via `<input type="file">` (hidden, triggered by "Upload files" button)
- Uses `window.Media.uploadFile()` for the actual upload
- Multiple file upload: loops through selected files sequentially
- Shows toast on success/error, refreshes grid after all uploads complete

### Pagination
"Load more" button fetches next page and appends cards to the grid (infinite scroll pattern).

### Copy URL
Copies the file's `cdn_url` to clipboard via `Utils.copyToClipboard()`.

### Delete
Calls `window.Media.deleteMedia()`, then removes the card from DOM without full reload.

### Type Filter
`<select>` with all/image/video resets page to 1 and reloads grid.

---

## API Routes Used

| Action | Route |
|--------|-------|
| List media | `media_list` |
| Upload file | `upload_media` (via `window.Media.uploadFile`) |
| Delete file | `delete_media` (via `window.Media.deleteMedia`) |

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/js/media.js` | Provides `window.Media.uploadFile`, `initDropZone`, `deleteMedia` |
| `src/Controllers/MediaController.php` | Serves API routes |
