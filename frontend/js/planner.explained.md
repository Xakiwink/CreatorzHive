# planner.js — Explained

**File:** `frontend/js/planner.js`

---

## Purpose

Renders the content planner page. Provides two views: a monthly calendar (posts as pills on dates) and a list view with filters. Handles create/edit/duplicate/delete posts, media attachment, and tag management.

---

## Views

### Calendar View
- Renders a month grid with post pills per date
- Up to 3 pills per cell; `+N more` overflow indicator
- Click cell → open create modal with pre-filled date
- Click post pill → open edit modal
- Navigation buttons for prev/next month

### List View
- Filterable table: status, platform, date range, search, sort
- Pagination (page-based, 10 per page)
- Actions: edit, duplicate, delete, status change

---

## State

```js
calYear, calMonth        // current calendar month
currentView              // 'calendar' or 'list'
listPage                 // current list view page
allTags                  // loaded tag list
composerMedia            // array of attached media files in open composer
selectedTagIds           // Set of tag IDs being applied
selectedPostIds          // Set of post IDs for bulk operations
```

---

## Post Composer (Create/Edit Modal)

Fields: title, content, platforms (multi-select checkboxes), scheduled_at, status, media attachments, tags, cover media selection.

### Media Attachment
- Drag-and-drop via `window.Media.initDropZone()`
- "Pick from library" → `window.Media.openMediaLibrary()`
- Inline upload via `window.Media.uploadFile()`

### Tag Management
- Loads tags on modal open
- Create new tag inline (with color picker)
- Toggle tag selection

---

## Bulk Operations

Multi-select via checkboxes in list view. Bulk actions:
- Delete selected
- Change status of selected

---

## API Routes Used

| Action | Route |
|--------|-------|
| Load calendar | `posts_calendar` |
| Load list | `posts` |
| Get post | `post_get` |
| Create post | `post_create` |
| Update post | `post_update` |
| Delete post | `post_delete` |
| Duplicate post | `post_duplicate` |
| Bulk action | `posts_bulk` |
| List tags | `tags` |
| Create tag | `tag_create` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/PostController.php` | Serves all post API routes |
| `src/Controllers/TagController.php` | Serves tag routes |
| `frontend/js/media.js` | `window.Media.*` for media attachment |
| `frontend/js/app.js` | `window.api()`, `window.Modal`, `window.Toast` |
