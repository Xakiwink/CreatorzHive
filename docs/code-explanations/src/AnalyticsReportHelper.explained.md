# AnalyticsReportHelper.php — Explained

**File:** `src/Support/AnalyticsReportHelper.php`
**Namespace:** `CreatorzHive\Support`

---

## Purpose

Calculation utilities for the analytics page. Handles period range resolution, percent-change math, sparkline data generation, and posting frequency formatting.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | Sparkline query against `posts` table |

---

## Methods

### `resolvePeriodRange(string $period, string $startIn, string $endIn): array`

Converts period shortcode to `[start_date, end_date]` strings:

| Period | Range |
|--------|-------|
| `7d` | Last 7 days (today - 6 days) |
| `30d` (default) | Last 30 days (today - 29 days) |
| `90d` | Last 90 days (today - 89 days) |
| `custom` | Use `$startIn` and `$endIn` directly |

End date is always today (server time).

### `percentChange(float $current, float $previous): float`

Standard period-over-period change:
- If `$previous == 0`: returns 100.0 if current > 0, else 0.0
- Otherwise: `(current - previous) / previous * 100`, rounded to 2 decimals

### `postsPublishedSparkline(int $userId, string $rangeEnd, int $days): array`

Queries `posts` for published post counts per day within the last N days. Returns an array of N integers in chronological order (oldest → newest).

Days with no published posts return 0. Fills gaps by iterating all dates.

### `formatPostingFrequency(array $rows): array`

Reformats raw SQL result rows `[{ bucket, cnt }]` into `[{ week, count }]` for the frontend chart.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AnalyticsController.php` | Primary consumer |
| `src/Repositories/AnalyticsRepository.php` | Provides trend/frequency data that this helper post-processes |
