# DealWorkflowHelper.php — Explained

**File:** `src/Support/DealWorkflowHelper.php`
**Namespace:** `CreatorzHive\Support`

---

## Purpose

Thin orchestration helper for deal-related side effects: writing audit log entries and triggering deal completion notifications. Keeps `DealController` focused on HTTP concerns.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$audit` | `AuditLogRepository` | Write audit log entries |
| `$notifications` | `NotificationService` | Send completion notification |
| `$deals` | `DealRepository` | Fetch deal title for notification message |

---

## Methods

### `logAudit(int $userId, string $action, string $entityType, int $entityId, ?array $oldValues, ?array $newValues): void`

Thin wrapper around `AuditLogRepository::logCreate()`. Converts null values to empty arrays. Used by `DealController` for all deal mutations:
- `deal.created`
- `deal.updated`
- `deal.status_changed`
- `deal.deleted`

### `notifyDealCompleted(int $userId, int $dealId, string $brandName): void`

Fetches the deal title from DB (falls back to `$brandName` if deal not found), then calls `NotificationService::dealCompleted()`.

---

## Why This Exists

`DealController` needs both audit logging and notifications on mutations. Rather than injecting `AuditLogRepository` and `NotificationService` directly (adding 2 more params), they're wrapped in this helper. Keeps controller constructor smaller and groups deal-specific workflow logic together.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/DealController.php` | Primary consumer |
| `src/Repositories/AuditLogRepository.php` | Audit persistence |
| `src/Services/NotificationService.php` | Completion notification |
