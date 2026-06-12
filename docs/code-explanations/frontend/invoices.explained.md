# invoices.js — Explained

**File:** `frontend/js/invoices.js`

---

## Purpose

Renders the invoices page. Displays a filterable table of invoices, total paid revenue, and supports create, view, edit, mark-paid, and delete operations.

---

## Key Features

### Invoice Table
Fetches `invoices_data` with optional `status` filter. Shows: invoice number, recipient, amount, currency, status badge (using `display_status` for overdue calculation), due date, actions.

### Status Badge
Uses `display_status` from server (computed by `normalizeRow()`) which returns `'overdue'` when due date is past and invoice is not paid/cancelled.

### Revenue Summary
Displays total paid amount (`paid_total`) from the API response in the header stat card.

---

## Invoice Forms

### Create Invoice
Modal form with: recipient name/email, line items (dynamic add/remove), subtotal, tax rate, tax amount, total, currency, status, due date, optional deal association.

### Edit Invoice
Loads existing values, same form as create.

### Mark Paid
Quick action button → calls `invoice_mark_paid`, refreshes table.

---

## API Routes Used

| Action | Route |
|--------|-------|
| Load list | `invoices_data` |
| Create | `invoice_create` |
| Update | `invoice_update` |
| Mark paid | `invoice_mark_paid` |
| Delete | `invoice_delete` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/InvoiceController.php` | Serves all invoice API routes |
| `frontend/js/app.js` | `window.api()`, `window.Modal`, `window.Toast` |
