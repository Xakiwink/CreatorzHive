# deals.js — Explained

**File:** `frontend/js/deals.js`

---

## Purpose

Renders the deals page as a Kanban board. Supports drag-and-drop status changes, deal create/edit/delete, deal detail drawer, activity log, linked posts, and revenue summary.

---

## Kanban Board

### Columns
Six columns in order: Lead → Negotiation → Contract → Active → Completed → Cancelled

### Drag and Drop
Uses HTML5 drag-and-drop API. Dragging a deal card between columns calls `deal_update_status` API to persist the status change. Highlights drop targets while dragging.

### `lastKanban`
Caches the last loaded Kanban data for re-renders and status change optimistic UI.

---

## Deal Card Features

- Status badge + overdue indicator (deadline in past + not completed/cancelled)
- Due-soon warning (≤7 days to deadline)
- Amount + currency display
- Deal type label
- Click → opens detail drawer

---

## Deal Drawer

Sliding side panel with:
- Deal details (brand, title, description, amount, deadline, deliverables)
- Status change buttons
- Edit / delete actions
- Activity log tab (from `audit_logs`)
- Linked posts tab

---

## Forms

### Create / Edit Deal Modal
Full form with: brand name/email, title, description, amount, currency, deal type, status, deadline, deliverables, notes.

### Status Change
Quick status change via column drop or status buttons in drawer. Calls `deal_update_status`.

---

## Revenue Summary (`#revSummaryMount`)
Displays earned revenue (completed deals), pipeline revenue (active deals), total/completed/active deal counts.

---

## API Routes Used

| Action | Route |
|--------|-------|
| Load Kanban | `deals_kanban` |
| Load revenue summary | `deals_revenue_summary` |
| Create deal | `deal_create` |
| Update deal | `deal_update` |
| Update status | `deal_update_status` |
| Delete deal | `deal_delete` |
| Load activity | `deal_activity` |
| Load linked posts | `deal_linked_posts` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/DealController.php` | Serves all deal API routes |
| `frontend/js/app.js` | `window.api()`, `window.Modal`, `window.Toast` |
