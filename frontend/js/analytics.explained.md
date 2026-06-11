# analytics.js — Explained

**File:** `frontend/js/analytics.js`

---

## Purpose

Renders the analytics page. Fetches data from `analytics_report` API, renders Chart.js charts for follower growth, engagement, daily rollup, platform breakdown, posting frequency, and top posts. Supports period selector (7d/30d/90d/custom) and platform filter.

---

## State

- `currentPeriod`: `'7d'`, `'30d'` (default), `'90d'`, or `'custom'`
- `customStart` / `customEnd`: dates for custom period
- `charts`: map of Chart.js instances (destroyed and recreated on each load)
- `sparkCharts`: array of sparkline Chart instances

---

## Platform Colors

Each platform has a brand color used in charts:
- Instagram: `#E4405F`
- TikTok: `#000000`
- YouTube: `#FF0000`
- Facebook: `#1877F2`
- Twitter/X: `#1DA1F2`

---

## Charts Rendered

| Chart | Type | Data Source |
|-------|------|-------------|
| Follower growth | Line | `growth_trend` |
| Daily engagement rollup | Line | `daily_rollup_trend` |
| Engagement rate | Line | `engagement_trend` |
| Posting frequency | Bar | `posting_frequency` |
| Platform breakdown | Doughnut | `platform_breakdown` |
| Sparklines (4x) | Line | `sparklines` array |

All charts use a shared `chartDefaults()` function that reads CSS variables (`--color-border`, `--color-text-muted`) to theme-match the chart grid and tick labels.

---

## Period Selector

Period buttons (7d/30d/90d/custom) update `currentPeriod`, clear the platform filter, and reload data. Custom period shows date inputs and validates that end ≥ start.

---

## Platform Filter

A `<select>` filters all charts to a single platform. Triggers a data reload.

---

## Seed / Demo Data

"Generate demo data" button calls `analytics_seed` route. Only shown when `APP_ENV=development` or `APP_ALLOW_ANALYTICS_SEED=true` (controlled by PHP template variable `window.__ALLOW_SEED__`).

---

## Key Numbers Format

`compactNumber(n)`: formats large numbers as `1.2M`, `456K`, etc.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AnalyticsController.php` | Serves `analytics_report`, `analytics_seed` |
| `src/Support/AnalyticsReportHelper.php` | Builds the report payload |
| `frontend/js/app.js` | `window.api()` |
