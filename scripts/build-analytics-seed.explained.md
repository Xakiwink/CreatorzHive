# build-analytics-seed.php — Explained

**File:** `scripts/build-analytics-seed.php`

---

## Purpose

Code generator. Outputs SQL for 90 days of `analytics_snapshots` rows (3 platforms × 90 days = 270 rows) plus one `analytics` totals row. Redirect to create `database/seeds/analytics.sql`.

---

## Usage

```bash
php scripts/build-analytics-seed.php > database/seeds/analytics.sql
```

---

## Generated Data

All data is for `david@creatorzhive.com`.

### Platforms and Follower Ranges

| Platform | Range |
|----------|-------|
| Instagram | 7,000 – 7,500 |
| TikTok | 3,200 – 3,800 |
| YouTube | 1,800 – 2,200 |

### Noise Function

Uses `crc32($platform . $date) % 37` as deterministic per-cell noise (range ±18). This means the same seed file is produced on every run (unlike `build-posts-seed.php` which uses `rand()`).

### Weekend Boost

`impressions` and engagement metrics are boosted on Saturday/Sunday: +800 impressions vs +200 on weekdays; likes multiplied by 1.15.

### Derived Metrics Per Row

- `impressions = followers × 12 + weekend_bonus`
- `reach = impressions × 0.85`
- `likes = followers × 0.02 × weekend_factor`
- `comments = likes × 0.12`
- `shares = likes × 0.08`
- `saves = likes × 0.10`
- `engagement_rate` = 2–6% (deterministic via `crc32`)

### Upsert

Uses `ON DUPLICATE KEY UPDATE` — safe to re-run.

### Analytics Totals Row

Hard-coded aggregate: 25 posts, 10 published, 13,500 total followers, 2.5M impressions, 4.25% avg engagement rate.

---

## Related Files

| File | Relationship |
|------|-------------|
| `database/seeds/analytics.sql` | Output target |
| `scripts/seed.php` | Consumes `analytics.sql` |
| `src/Services/AnalyticsService.php` | `generateDemoData()` uses the same sine-based approach at runtime |
