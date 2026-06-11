# NotificationRepository.php — Explained

**File:** `src/Repositories/NotificationRepository.php`

---

## Purpose

All database queries for the `notifications` table. Handles listing, counting, mark-read, create, and delete operations.

---

## Category Filtering

Both `getByUser()` and `countForUser()` support a `$category` parameter that maps to notification type IN clauses:

| Category | Types Included |
|----------|---------------|
| `'posts'` | `post_published`, `post_failed` |
| `'deals'` | `deal_updated`, `deal_completed` |
| `'invoices'` | `invoice_paid` |
| `null` / other | All types |

---

## Methods

### `getByUser(int $userId, bool $unreadOnly, int $limit, int $offset, ?string $category): array`
Returns paginated notifications for a user, ordered by `created_at DESC`. Optionally filter to unread only or by category.

### `countForUser(int $userId, bool $unreadOnly, ?string $category): int`
Count query matching the same filters as `getByUser()`.

### `countUnread(int $userId): int`
Quick count of `is_read = 0` notifications. Used for the notification badge.

### `markRead(int $id, int $userId): bool`
Sets `is_read = 1` and `read_at = COALESCE(read_at, NOW())` (preserves original `read_at` if already set).

### `markAllRead(int $userId): bool`
Bulk-marks all unread notifications as read. `read_at = NOW()` (overwrites any existing value, unlike `markRead`).

### `create(int $userId, string $type, string $title, string $body, string $actionUrl, ?string $icon): int`
Inserts a notification. `body`, `action_url`, `icon` stored as `null` when empty string. Returns the new notification ID.

### `delete(int $id, int $userId): bool`
Hard-deletes a single notification. Ownership-scoped.

### `deleteRead(int $userId): bool`
Bulk-deletes all read notifications for a user. Called by `NotificationController::postDeleteRead()`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/NotificationController.php` | Primary caller |
| `src/Services/NotificationService.php` | Calls `create()` via `createInApp()` |
| `backend/compat/models.php` | `notification_*` global function wrappers |
