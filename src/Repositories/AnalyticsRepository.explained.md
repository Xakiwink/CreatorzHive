# AnalyticsRepository.php — Explained

**File:** `src/Repositories/AnalyticsRepository.php`

---

## Purpose

All database queries for the `analytics` and `analytics_snapshots` tables. Handles aggregate totals, time-series trend data, platform breakdowns, and posting frequency. Injected with `PostRepository` to recalculate post counts.

---

## Tables

| Table | Description |
|-------|-------------|
| `analytics` | One row per user — aggregate totals (followers, impressions, engagements, etc.) |
| `analytics_snapshots` | Daily/weekly/monthly per-platform metrics snapshots |

---

## Platform Filter Helper

### `sqlWithPlatformFilter(string $sql, array $params, ?string $platform, bool $allPlatformsWhenEmpty = true): array`
Appends a platform WHERE clause to an in-progress SQL string:
- If platform is a valid slug: `AND platform = :plat`
- If `null` and `$allPlatformsWhenEmpty = true`: `AND platform IS NOT NULL AND platform != ''`
- If `null` and `$allPlatformsWhenEmpty = false`: no clause appended

Returns `[$sql, $params]`. Used by most trend/summary queries in this repository.

---

## Aggregate Analytics (`analytics` table)

### `getByUser(int $userId): ?array`
Fetches the single aggregate row for a user.

### `upsert(int $userId, array $data): void`
INSERT ... ON DUPLICATE KEY UPDATE — idempotent. Merges `$data` with defaults (all metric fields default to 0).

### `recalculate(int $userId): void`
Rebuilds the `analytics` row from live data:
1. Calls `PostRepository::countByStatus()` for post counts
2. Preserves existing social metrics (followers, impressions, etc.) from the current row
3. Calls `upsert()` with fresh counts merged with preserved social metrics

---

## Snapshot Queries (`analytics_snapshots` table)

### `getSnapshots(int $userId, string $period, string $startDate, string $endDate, ?string $platform): array`
Returns raw snapshot rows for a date range and period (`daily`/`weekly`/`monthly`).

### `sumFollowersOnDate(int $userId, string $date, ?string $platform): int`
Sums followers across platforms on a specific date (for period-over-period comparison).

### `sumMetricsInRange(int $userId, string $startDate, string $endDate, ?string $platform): array`
Aggregates `impressions`, `reach`, `engagements` (likes+comments+shares), `avg_engagement_rate` over a date range.

### `hasSnapshotData(int $userId): bool`
Quick check — returns `true` if the user has any snapshot rows.

---

## Trend Queries

### `getGrowthTrend(...)`: `[{date, followers}]`
Daily follower count grouped by date. Used for follower growth charts.

### `getDailyRollupTrend(...)`: `[{date, impressions, reach, engagements, rate}]`
Daily aggregated engagement metrics. Used for engagement trend charts.

### `getEngagementTrend(...)`: `[{date, rate, likes, comments}]`
Daily engagement rate + likes/comments breakdown.

### `getPostingFrequency(int $userId, string $granularity, ...)`: `[{bucket, cnt}]`
Posts published over time:
- `granularity = 'monthly'`: bucket = `YYYY-MM`
- `granularity = 'weekly'`: bucket = `YEARWEEK(published_at, 3)` (ISO week)

---

## Platform Breakdown

### `getPlatformBreakdown(int $userId, string $startDate, string $endDate, ?string $platform): array`
Returns the most-recent snapshot row per platform within the date range. Uses a subquery with `MAX(snapshot_date)` per platform and joins back to get full row data.

---

## Top Posts

### `getTopPosts(int $userId, int $limit, ?string $platform): array`
Returns published posts ordered by `engagement_rate DESC`. The engagement metrics (`likes`, `comments`, `reach`, `engagement_rate`) are **synthetic, deterministic values derived from the post ID using modular arithmetic** — not real API data. This is a known placeholder.

### `firstPlatformSlug($platforms): string`
Decodes JSON `platforms` column and returns the first entry. Defaults to `'instagram'`.

---

## Count Query

### `countPublishedInRange(int $userId, string $startDate, string $endDate): int`
Counts published posts in a date range. Used by `AnalyticsReportHelper` for sparklines.

---

## Notes

- `getTopPosts()` uses post-ID-based arithmetic for engagement metrics — not real analytics data.
- `getPlatformBreakdown()` uses a self-join pattern to avoid selecting duplicate rows when multiple snapshots exist for the same platform.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/AnalyticsService.php` | Calls `upsert()`, `recalculate()` |
| `src/Controllers/AnalyticsController.php` | Calls all trend/summary queries |
| `src/Repositories/PostRepository.php` | Injected; used by `recalculate()` |
| `backend/compat/models.php` | `analytics_*` global function wrappers |
