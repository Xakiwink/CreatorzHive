# PostController.php — Explained

**File:** `src/Controllers/PostController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

CRUD and lifecycle management for social media posts. Handles creating, reading, updating, deleting, duplicating, and bulk-operating on posts. Triggers job queue dispatch, analytics recalculation, and notifications on status changes.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$posts` | `PostRepository` | All post DB operations |
| `$jobQueue` | `JobQueueRepository` | Enqueue/cancel `PublishPostJob` |
| `$media` | `MediaFileRepository` | Validate media ownership before attaching |
| `$analytics` | `AnalyticsRepository` | Recalculate totals after post changes |
| `$notifications` | `NotificationService` | Notify on post publish |
| `$input` | `PostInputNormalizer` | Clean up messy platform/media/tag inputs |

---

## Methods

### `plannerPage()` — GET planner
Renders `planner/index` HTML page (SPA shell; all data loaded via API).

### `index()` — GET api/posts
Returns paginated filtered post list. Accepts: `status`, `platform`, `date_from`, `date_to`, `search`, `sort`, `dir`, `page`, `per_page`. Fixes relative `cover_url`/`cover_thumb` paths using `upload_url()`.

### `calendar()` — GET api/posts_calendar
Returns posts grouped by `YYYY-MM-DD` date keys for a given `month`/`year`.

### `show()` — GET api/post?id=
Returns full post detail with media (URL-fixed). Verifies ownership.

### `store()` — POST api/create_post

1. Default status = `draft` if not supplied
2. Normalize platforms (JSON string or array → clean PHP array)
3. Validate title, content, status enum
4. Parse/validate `scheduled_at` for `scheduled` status
5. Verify cover media belongs to this user
6. Create post → sync media → sync tags
7. If `scheduled`: `jobQueue->queueEnqueuePublishPost($postId)` → queues `PublishPostJob`
8. Recalculate analytics
9. If `published`: send notification

### `update()` — POST api/update_post

Same as `store()` but:
- Verifies ownership before proceeding
- Cancels any pending publish job: `queueCancelPendingPublishForPost()`
- Re-enqueues job if new status is `scheduled`
- Preserves existing `published_at` if already published

### `destroy()` — POST api/delete_post
Cancels pending publish job → soft-delete (`is_deleted=1`) → recalculate analytics.

### `duplicate()` — POST api/duplicate_post
Creates a draft copy via `PostRepository::duplicate()`, recalculates analytics, returns new post.

### `bulk()` — POST api/bulk_posts

Actions:
- **`delete`**: Cancel jobs + soft-delete each
- **`status`**: Change status (except `scheduled` — must use editor for date assignment)

---

## Design Notes

- `auth` middleware already checks login, but methods also call `session_get_user()` defensively
- Media ownership validated before attach — prevents cross-user media theft via ID guessing
- `PostInputNormalizer` isolates the messy input normalization from controller logic

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/PostRepository.php` | Post DB queries |
| `src/Repositories/JobQueueRepository.php` | Publish job dispatch |
| `src/Jobs/PublishPostJob.php` | Actual publishing job |
| `src/Support/PostInputNormalizer.php` | Input cleaning helper |
