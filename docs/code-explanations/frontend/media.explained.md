# media.js — Explained

**File:** `frontend/js/media.js`

---

## Purpose

Shared media upload and media library utilities exposed as `window.Media`. Used by `planner.js` and `media-library.js` for file upload and media selection. Does NOT render the media management page itself — that is handled by `media-library.js`.

---

## `window.Media` Object

### `uploadFile(file, onProgress): Promise<object>`
Uploads a file to `upload_media` via `multipart/form-data` POST with CSRF token. Returns parsed JSON response. `onProgress` parameter accepted but not currently implemented.

### `initDropZone(el, onFiles): void`
Adds drag-and-drop handlers to a DOM element. Calls `onFiles(filesArray)` when files are dropped.

### `renderMediaGrid(files, onPick): HTMLElement`
Creates a grid of clickable media file cards. Each card shows thumbnail (or "File" fallback) and truncated filename. Calls `onPick(file)` when clicked.

### `openMediaLibrary(onPick): void`
Opens the shared modal with a media grid:
1. Fetches `media_list?per_page=48`
2. Renders grid via `renderMediaGrid()`
3. Opens modal with the grid
4. Calls `onPick(file)` when a file is selected (and closes modal)

### `deleteMedia(mediaId): Promise<bool>`
Shows `window.confirm()` before deleting. Calls `delete_media` API. Returns `false` if cancelled.

---

## Notes

- This file is a utility library — it runs at load time to expose `window.Media` but doesn't render any UI itself.
- The actual media library page (grid, upload, bulk delete) is handled by `media-library.js`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/js/media-library.js` | Media management page (uses `Media.uploadFile`, `Media.initDropZone`) |
| `frontend/js/planner.js` | Uses `Media.openMediaLibrary()` to attach media to posts |
| `src/Controllers/MediaController.php` | `upload_media`, `media_list`, `delete_media` routes |
