# dashboard.js — Explained

**File:** `frontend/js/dashboard.js`

---

## Purpose

Loads and renders the dashboard page. Calls `api('dashboard_data')` to fetch all dashboard data in one request, then renders stat cards, recent posts table, upcoming posts list, platform connection status, and a post-status donut chart using Chart.js.

---

## Key Behaviors

### Skeleton Loading
Shows placeholder skeleton elements in all four sections while data is loading, providing a visual layout before content arrives.

### Stat Cards (`#statGrid`)
Renders 6 stat cards from the dashboard payload: total posts, published posts, scheduled posts, followers, deals, revenue.

### Recent Posts (`#recentPostsMount`)
Renders a table of the 5 most recent posts with status badge, platform icons, date, and quick-action links (view/edit).

### Upcoming Posts (`#upcomingMount`)
Lists next scheduled posts with thumbnail, title, platform, and scheduled time.

### Platform Status (`#platformStatusMount`)
Shows each connected platform with `username`, `is_active` indicator, and follower count.

### Post Status Chart (`#postStatusChart`)
Donut chart (Chart.js) showing post count breakdown by status (draft/scheduled/published/failed). Chart re-renders on theme change (light/dark) by listening for `data-theme` attribute mutations via `MutationObserver`.

---

## Data Source

Single API call: `GET dashboard_data` → all data in one response.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/DashboardController.php` | Serves `dashboard_data` |
| `src/Services/DashboardService.php` | Builds the payload |
| `frontend/js/app.js` | `window.api()` used here |
