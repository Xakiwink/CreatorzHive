# AuditLogRepository.php — Explained

**File:** `src/Repositories/AuditLogRepository.php`

---

## Purpose

Write and read the `audit_logs` table. Audit logs record all significant actions (deal status changes, admin settings updates, credential saves, etc.) with actor, entity, before/after values, IP address, and user agent.

---

## Methods

### `logCreate(?int $userId, string $action, ?string $entityType, ?int $entityId, array $oldValues, array $newValues): void`

Inserts a new audit log entry:
- `$userId`: null for system-generated actions
- `$action`: dot-notation string, e.g. `'deal.status_changed'`, `'admin.settings_updated'`
- `$entityType` / `$entityId`: the entity affected (e.g., `'deal'`, `42`)
- `$oldValues` / `$newValues`: stored as JSON strings; empty arrays stored as `null`
- `ip_address`: resolved via `request_ip()`
- `user_agent`: truncated to 500 characters

### `logListRecent(int $limit = 100): array`
Returns the most recent audit log entries (up to `$limit`, max 500), JOINed with `users` to include `actor_email`. Ordered by `id DESC`.

---

## Public Interface Note

The compat bridge exposes these as:
- `audit_log_create()` → `logCreate()`
- `audit_log_list_recent()` → `logListRecent()`

The method names in the OOP class (`logCreate`, `logListRecent`) differ from typical repository naming (`create`, `listRecent`) to avoid conflicts with the compat bridge prefix.

---

## Usage

Primarily called from:
- `DealWorkflowHelper::logAudit()` for deal lifecycle events
- `AdminUserController::settingsUpdate()` for admin settings changes
- `AdminUserController::updatePlatformCredentials()` for credential saves

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Support/DealWorkflowHelper.php` | Calls `logCreate()` for deal audits |
| `src/Controllers/AdminUserController.php` | Calls `logCreate()` for admin actions |
| `backend/compat/models.php` | `audit_log_*` global function wrappers |
