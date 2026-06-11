# PostRepository.php — Explained

**File:** `src/Repositories/PostRepository.php`
**Namespace:** `CreatorzHive\Repositories`

---

## Purpose

All SQL operations for posts and their relationships (media attachments, tags). Handles the JSON `platforms` column transparently via `normalizeRow()`.

---

## Data Model

- Posts have a JSON `platforms` column (e.g., `["instagram","tiktok"]`)
- Posts use soft-delete (`is_deleted = 1`)
- Posts have many-to-many relationships with `media_files` (via `post_media`) and `tags` (via `post_tags`)
- Cover image is stored as `cover_media_id` FK + joined on reads

---

## Query Methods

### `findById(int $id): ?array`
Finds non-deleted post by ID. Returns normalized row (platforms decoded from JSON).

### `findByIdForUser(int $id, int $userId): ?array`
Like `findById()` but enforces user ownership. Used before any mutation.

### `create(array $data): int`
Inserts post. Encodes `platforms` array to JSON before insert. Returns new post ID.

### `save(int $id, array $data): bool`
Partial update. Encodes platforms array if present.

### `softDelete(int $id, int $userId): bool`
Sets `is_deleted=1` WHERE `id AND user_id` — ownership enforced in the WHERE clause.

### `getRecentByUser(int $userId, int $limit): array`
Recent posts with `cover_thumb` and `cover_url` from `media_files` JOIN. Sorted by `updated_at DESC`.

### `getUpcoming(int $userId, int $limit): array`
Reads from `v_upcoming_posts` database view (pre-filtered scheduled posts in the future).

### `countByStatus(int $userId): array`
Returns `{ draft: N, scheduled: N, published: N, failed: N }` for dashboard status breakdown.

### `getCalendarPosts(int $userId, string $month, string $year): array`
Adds a computed `calendar_date` column using a CASE expression:
- `scheduled` status → `scheduled_at`
- `published` status → `published_at`
- Otherwise → `created_at`

Filters to the given `YYYY-MM` month. Used by the planner calendar view.

### `getAllByUser(int $userId, array $filters): array`
Full paginated/filtered list. Features:
- Sorting by date, title, or status
- Status filter (enum validation)
- Platform filter: uses MySQL `JSON_CONTAINS()` on the platforms JSON column
- Date range filter
- Full-text search: MySQL `MATCH...AGAINST` for terms ≥4 chars, `LIKE` fallback for shorter terms
- Attaches tags to all posts in one IN-query batch call

### `getFullForUser(int $id, int $userId): ?array`
Fetches post + all attached media (ordered by `sort_order`) + all tags. The complete post detail object.

---

## Relationship Management

### `syncMedia(int $postId, array $mediaIdsOrdered): void`
Full replace: delete all current media → re-insert in order. `sort_order` = array index.

### `syncTags(int $postId, array $tagIds): void`
Full replace: delete all current tags → re-attach.

### `attachTagsToPosts(array $posts): array`
Bulk-fetches tags for multiple posts in one query using IN clause, then maps tags back to posts. Avoids N+1.

---

## Job-Related Methods

### `publish(int $id): bool`
Sets `status='published'`, `published_at=NOW()`. Called by `PublishPostJob`.

### `markFailed(int $id, string $reason): bool`
Sets `status='failed'`. The `$reason` parameter is accepted but not stored (stored in `platform_post_results` instead).

### `duplicate(int $id, int $userId): int`
Creates a draft copy of a post (title prefixed with "Copy of", no schedule). Copies both media and tag associations.

---

## `normalizeRow(array $row): array`

Decodes the JSON `platforms` string to a PHP array. Called on every SELECT result.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/PostController.php` | Primary consumer |
| `src/Jobs/PublishPostJob.php` | `findById()`, `publish()`, `markFailed()` |
| `src/Repositories/AnalyticsRepository.php` | Injected for `recalculate()` |
| `database/schema.sql` | `posts`, `post_media`, `post_tags`, `v_upcoming_posts` |
