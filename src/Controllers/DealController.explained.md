# DealController.php — Explained

**File:** `src/Controllers/DealController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Manages sponsorship deals through a 6-stage Kanban workflow (lead → negotiation → contract → active → completed → cancelled). All CRUD operations plus a dedicated Kanban status-update endpoint. Every mutation is audit-logged and may trigger notifications.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$deals` | `DealRepository` | Deal DB queries |
| `$invoices` | `InvoiceRepository` | Fetch linked invoices for deal detail view |
| `$workflow` | `DealWorkflowHelper` | Audit logging and deal completion notification |
| `$notifications` | `NotificationService` | In-app/email notifications on status changes |

---

## Methods

### `index()` — GET deals (page)
Renders `monetization/deals` template.

### `data()` — GET api/deals_data
Returns the full deals page payload:
- `kanban`: all deals grouped by status with counts (from `DealRepository::getKanban()`)
- `revenue_summary`: total earned, pending, this-month amounts

### `show()` — GET api/deal?id=
Returns full deal detail including:
- Deal fields (brand_logo_url URL-fixed)
- `linked_posts`: associated social posts
- `invoices`: linked invoices
- `activity`: audit history for this deal

### `store()` — POST api/create_deal
Validates: `brand_name`, `title`, `amount` (numeric), `currency` (TZS/USD/EUR), `status` (enum), `deal_type` (enum), `brand_email`, `deadline_at`. Creates deal, logs `deal.created` audit event.

### `update()` — POST api/update_deal
Updates allowed fields: brand_name, brand_email, brand_logo_url, title, description, amount, currency, status, deal_type, deliverables, deadline_at, notes.

Auto-sets `completed_at`/`contracted_at` timestamps when status transitions to `completed`/`contract` respectively.

Fires notification if:
- Status becomes `completed` → `NotificationService::dealCompleted()`
- Status changes to anything else → `NotificationService::dealStatusChanged()`

### `updateStatus()` — POST api/update_deal_status
Kanban drag-and-drop endpoint. Only changes the status field. Checks for no-op (same status). Logs `deal.status_changed` audit. Notifies if completed.

### `destroy()` — POST api/delete_deal
Soft-deletes the deal (`is_deleted=1` on deals table). Logs `deal.deleted` audit event.

---

## Deal Status Enum

```
lead → negotiation → contract → active → completed → cancelled
```

---

## Currency Support

Validates: `TZS` (default), `USD`, `EUR`

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/DealRepository.php` | All deal DB queries |
| `src/Repositories/InvoiceRepository.php` | Linked invoice lookup |
| `src/Support/DealWorkflowHelper.php` | Audit logging, completion notify |
| `src/Services/NotificationService.php` | Deal notifications |
| `src/Controllers/InvoiceController.php` | Creates invoices from deals |
