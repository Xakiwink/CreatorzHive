# notifications.js — Explained

**File:** `frontend/js/notifications.js`

---

## Purpose

Renders and manages the notifications page. Supports tab filtering, pagination (load more), mark-read, bulk delete-all-read, and per-notification delete.

---

## State

```js
{ tab: 'all', page: 1, perPage: 20, hasMore: false, loading: false }
```

---

## Tabs

| Tab | Filter |
|-----|--------|
| `all` | All notifications |
| `unread` | Unread only |
| `posts` | `post_published`, `post_failed` |
| `deals` | `deal_updated`, `deal_completed` |
| `invoices` | `invoice_paid` |

---

## API Calls

| Action | Route | Method |
|--------|-------|--------|
| Load notifications | `notifications_data` | GET |
| Mark single as read | `notification_mark_read` | POST |
| Mark all as read | `notifications_mark_all_read` | POST |
| Delete single | `notification_delete` | POST |
| Delete all read | `notifications_delete_read` | POST |

---

## Key Behaviors

### `loadPage(reset: bool)`
Fetches a page of notifications. If `reset = true`, clears the list and starts from page 1. Appends items to `#notifListMount` on load-more. Shows `#notifEmpty` when no items.

### Notification Icons
Each notification type has a distinct SVG icon. Unknown types fall back to a bell emoji.

### Mark as Read
Clicking a notification calls `notification_mark_read`. The notification row gets `is_read = 1` styling. Calls `window.refreshNotifBadge()` to update the header badge count.

### Bulk Actions
- "Mark all read" button → marks all unread, reloads list
- "Delete read" button → deletes all read notifications, reloads list

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/NotificationController.php` | Serves all notification API routes |
| `frontend/js/app.js` | `window.api()`, `window.refreshNotifBadge()` |
