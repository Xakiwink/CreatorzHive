# InvoiceRepository.php — Explained

**File:** `src/Repositories/InvoiceRepository.php`

---

## Purpose

All database queries for the `invoices` table. Handles invoice lifecycle (draft → sent → paid), invoice number generation, line item serialization, pagination, and the `display_status` overdue calculation.

---

## Statuses

Hardcoded: `['draft', 'sent', 'paid', 'overdue', 'cancelled']`

Note: `overdue` is a **computed display status** only — it is not stored in the database. The actual DB `status` is `sent` for overdue invoices; `normalizeRow()` computes `display_status = 'overdue'` when `due_date` is in the past.

---

## Read Methods

### `getByUser(int $userId, array $filters): array`
Paginated list. Returns:
```php
['items' => [...], 'total' => N, 'page' => N, 'per_page' => N]
```
Filters: `status`, `page`, `per_page` (max 100). Ordered by `created_at DESC`.

### `findById(int $id): ?array`
No ownership check — use `findForUser()` for user-scoped access.

### `findForUser(int $id, int $userId): ?array`
Ownership-scoped fetch.

### `getByDeal(int $dealId): array`
All invoices for a deal, ordered by `created_at DESC`.

### `sumPaidTotal(int $userId): float`
Sum of `total` for all `status = 'paid'` invoices. Used for revenue reporting.

---

## Write Methods

### `create(array $data): int`
Insert a new invoice. Notable behavior:
- `line_items`: accepts PHP array or JSON string; defaults to `[{description: 'Services', qty: 1, unit_price: total}]` if empty
- `invoice_number`: uses `$data['invoice_number']` if provided, otherwise auto-generates via `generateNumber()`
- `subtotal`/`total` cross-calculation: if `total` not set but `subtotal` is, computes `total = subtotal + tax_amount`; vice versa
- `currency`: 3-char uppercase, defaults to `'TZS'`
- `deal_id`: stored as `null` if not provided (standalone invoices)

Returns the new invoice ID.

### `update(int $id, array $data): bool`
Partial update — only columns in `$data` are updated. Column allowlist: `recipient_name`, `recipient_email`, `due_date`, `status`, `notes`, `tax_rate`, `tax_amount`, `subtotal`, `total`, `line_items`. Returns `false` if nothing to update.

### `markPaid(int $id): bool`
Sets `status = 'paid'` and `paid_at = COALESCE(paid_at, NOW())`. Uses COALESCE to avoid overwriting an existing `paid_at` timestamp.

---

## generateNumber(int $userId): string

Format: `INV-{YEAR}-{NNNN}` (e.g., `INV-2025-0007`)

Number is based on `COUNT(*)` of the user's invoices in the current year + 1. Not collision-safe under concurrent inserts but acceptable for typical usage volumes.

---

## normalizeRow(array $row): array

Applied to all SELECT results:
- Casts `id`, `user_id` to int; `deal_id` to int or null
- Decodes `line_items` JSON string → PHP array
- Casts `subtotal`, `tax_rate`, `tax_amount`, `total` to string
- Computes `display_status`:
  - `paid` / `cancelled`: `display_status = status`
  - Otherwise: if `due_date` is in the past → `display_status = 'overdue'`; else `display_status = status`

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/InvoiceController.php` | Primary caller |
| `backend/compat/models.php` | `invoice_*` global function wrappers |
