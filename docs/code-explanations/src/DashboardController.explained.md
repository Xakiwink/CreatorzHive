# DashboardController.php — Explained

**File:** `src/Controllers/DashboardController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Thin controller for the main dashboard page. Renders the HTML shell and serves the dashboard API payload. All data assembly is delegated to `DashboardService`.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$dashboard` | `DashboardService` | Builds the full dashboard data payload |

---

## Methods

### `index()` — GET dashboard (page)
Renders `dashboard/index` template (SPA shell).

### `data()` — GET api/dashboard_data
Uses `requireAuth()` to get session user, then delegates entirely to `DashboardService::buildPayload()`.

Returns a comprehensive object with stats, recent posts, active deals, recent notifications, and quick metrics — all fetched in a single service call.

---

## Design

This controller deliberately has no business logic — it's a thin dispatch layer. The `DashboardService` owns all the aggregation complexity. This makes the controller easy to test and the service reusable.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/DashboardService.php` | All dashboard data logic |
| `frontend/js/dashboard.js` | Fetches `api/dashboard_data` |
| `frontend/pages/dashboard/index.php` | The HTML template |
