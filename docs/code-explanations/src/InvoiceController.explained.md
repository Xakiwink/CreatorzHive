# InvoiceController.php — Explained

**File:** `src/Controllers/InvoiceController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

CRUD for invoices. Invoices can be created standalone or auto-populated from a deal. Supports lifecycle: draft → sent → paid → overdue → cancelled. Triggers notification when marked paid.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$invoices` | `InvoiceRepository` | Invoice DB operations |
| `$deals` | `DealRepository` | Fetch deal data when creating invoice from deal |
| `$notifications` | `NotificationService` | `invoicePaid()` notification |

---

## Methods

### `index()` — GET invoices (page)
Renders `monetization/invoices` template.

### `list()` — GET api/invoices_data

Filters: `status`, `page`, `per_page`. Returns paginated invoices plus `paid_total` sum (all-time paid amount in TZS).

### `show()` — GET api/invoice?id=
Returns single invoice. Ownership enforced.

### `store()` — POST api/create_invoice

**Two creation flows:**

**From deal** (`deal_id > 0`):
- Auto-populates: recipient_name from brand_name, recipient_email from brand_email, currency, total from deal amount
- Auto-creates a single line item: `{ description: deal.title, qty: 1, unit_price: deal.amount }`

**Standalone:**
- Requires: recipient_name, recipient_email, total (numeric), currency (TZS/USD/EUR), status (enum)
- Accepts custom line_items array

### `update()` — POST api/update_invoice
Updates editable fields: recipient_name, recipient_email, due_date, status, notes, tax_rate, tax_amount, subtotal, total, line_items (as JSON array).

### `markPaid()` — POST api/mark_invoice_paid
Sets status to `paid`, sets `paid_at` timestamp. Sends `invoicePaid` notification including invoice number and amount.

---

## Line Items Format

Stored as JSON in `invoices.line_items`:
```json
[{ "description": "Campaign", "qty": 1, "unit_price": 500000 }]
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/InvoiceRepository.php` | Invoice DB queries |
| `src/Repositories/DealRepository.php` | Deal lookup for pre-population |
| `src/Services/NotificationService.php` | invoicePaid notification |
| `frontend/js/invoices.js` | Invoice UI |
