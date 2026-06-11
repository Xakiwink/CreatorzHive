# AnalyticsController.php — Explained

**File:** `src/Controllers/AnalyticsController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Serves the analytics page and its data API. Computes period-over-period comparison metrics, sparklines, trend charts, platform breakdowns, and top posts. Also exposes a dev-only data seeding endpoint.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$analytics` | `AnalyticsRepository` | Raw metric queries against `analytics_snapshots` |
| `$reports` | `AnalyticsReportHelper` | Period resolution, percent-change calculations, sparkline slicing |
| `$analyticsService` | `AnalyticsService` | Demo data generation |

---

## Methods

### `index()` — GET analytics (page)
Renders `analytics/index` template.

### `data()` — GET api/analytics_data

Query params: `period` (7d, 30d, 90d, custom), `start_date`, `end_date`, `platform` (filter by platform).

**Computation flow:**

1. Resolve current date range via `AnalyticsReportHelper::resolvePeriodRange()`
2. Compute previous period (same duration, immediately before current)
3. Query per-platform follower count at range endpoints
4. Sum engagement metrics (impressions, reach, engagements, avg_engagement_rate) for current and previous period
5. Count published posts in both periods
6. Calculate % change for each metric via `percentChange()`
7. Build follower trend, daily rollup, engagement trend, posting frequency, platform breakdown, top 5 posts
8. Slice last 14 data points for sparklines

**Response structure:**
```json
{
  "has_data": bool,
  "range": { "start", "end", "period", "platform" },
  "summary": { "total_followers", "follower_growth", "total_impressions", ... },
  "sparklines": { "followers", "impressions", "reach", "engagements", ... },
  "follower_trend": [...],
  "engagement_trend": [...],
  "posting_frequency": [...],
  "platform_breakdown": [...],
  "top_posts": [...]
}
```

### `seed()` — POST api/seed_analytics

Dev/testing only. Guarded by: `APP_ENV === 'development'` OR `APP_ALLOW_ANALYTICS_SEED=1` in `.env`. Calls `AnalyticsService::generateDemoData()` to create 90 days of fake snapshot data.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/AnalyticsRepository.php` | Snapshot queries |
| `src/Support/AnalyticsReportHelper.php` | Period math, formatting |
| `src/Services/AnalyticsService.php` | Demo data generation |
| `frontend/js/analytics.js` | Renders charts from this data |
