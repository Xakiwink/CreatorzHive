# NotificationController.php — Explained

**File:** `src/Controllers/NotificationController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Manages user notification read state and deletion. Provides the notification bell count, paginated notification feed with tab filters, and all mutation endpoints (mark read, mark all read, delete, delete read).

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$notifications` | `NotificationRepository` | All notification DB operations |

---

## Methods

### `index()` — GET notifications (page)
Renders `notifications/notifications` template.

### `data()` — GET api/notifications_data

Query params: `tab` (all|unread|posts|deals|invoices), `page`, `per_page` (max 50, default 20).

Tab logic:
- `all` → no filter
- `unread` → `unread_only = true`
- `posts`, `deals`, `invoices` → filter by `category`

Returns: `{ notifications, unread_count, has_more, page, per_page, total }`.

### `unreadCount()` — GET api/notifications_count
Returns `{ unread_count: N }`. Used by the header bell badge.

### `postMarkRead()` — POST api/mark_read
Marks a single notification as read. Validates `id` > 0. Ownership enforced in repository layer.

### `postMarkAllRead()` — POST api/mark_all_read
Marks all of the user's notifications as read in one query.

### `postDelete()` — POST api/delete_notification
Deletes a single notification. Ownership enforced in repository.

### `postDeleteRead()` — POST api/delete_read_notifications
Bulk deletes all read notifications for the user (inbox cleanup).

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/NotificationRepository.php` | All notification DB queries |
| `src/Services/NotificationService.php` | Creates notifications (post published, deal completed, etc.) |
| `frontend/js/notifications.js` | Renders this data |
