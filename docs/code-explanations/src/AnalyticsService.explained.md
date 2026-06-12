# AnalyticsService.php — Explained

**File:** `src/Services/AnalyticsService.php`

---

## Purpose

Business logic for analytics data. Provides engagement rate calculation, demo data generation, and aggregate totals recalculation. Acts as orchestrator over the `analytics` and `analytics_snapshots` tables.

---

## Methods

### `syncFromPlatform(int $socialAccountId): void`
**Stub — not implemented.** The body only calls `unset($socialAccountId)`. Actual platform sync is done by `FetchAnalyticsJob`, which calls `SocialApiService::getAnalytics()` directly.

### `calculateEngagementRate(int $likes, int $comments, int $shares, int $followers): float`
Formula: `((likes + comments + shares) / followers) × 100`, rounded to 2 decimal places. Returns `0.0` if `$followers <= 1`.

### `generateDemoData(int $userId): void`
Seeds 90 days of fake analytics data for development/demo:
1. Gets the user's active platform list from `social_accounts` (defaults to instagram + tiktok if none)
2. Deletes existing daily snapshots for this user
3. Generates synthetic daily data using a deterministic sine-based RNG seeded by `crc32($platform)`:
   - Followers: cumulative growth with small daily noise
   - Impressions, reach, likes, comments, shares, saves: derived from follower count with weekend boost
   - `link_clicks = likes × 0.03`, `profile_visits = reach × 0.01` (estimates)
   - Engagement rate: clamped to `[2.0%, 6.5%]`
4. Upserts each snapshot via INSERT ... ON DUPLICATE KEY UPDATE
5. Calls `aggregateTotals()` to refresh the `analytics` row

The demo data is deterministic (same user + platform always produces same data) but appears realistic with natural variation.

### `aggregateTotals(int $userId): void`
Recalculates and upserts the `analytics` row for a user:
1. Queries most-recent snapshot per platform for current total followers (sub-join pattern)
2. Queries last 90 days of snapshots for impressions, reach, engagements, avg_engagement_rate
3. Preserves existing post counts and revenue (those are managed by `AnalyticsRepository::recalculate()`)
4. Calls `analytics_upsert()` with fresh social metrics

---

## Notes

- `syncFromPlatform()` is a no-op stub — the platform sync path is: `FetchAnalyticsJob → SocialApiService::getAnalytics() → AnalyticsRepository::upsert()`
- `generateDemoData()` uses `analytics_service_calculate_engagement_rate()` and `analytics_service_aggregate_totals()` (compat bridge self-calls) instead of direct method calls — an inconsistency in the codebase.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Jobs/FetchAnalyticsJob.php` | Calls `aggregateTotals()` after snapshot insert |
| `src/Controllers/AnalyticsController.php` | Calls `generateDemoData()` via compat bridge |
| `src/Repositories/AnalyticsRepository.php` | Underlying data layer |
| `backend/compat/services.php` | `analytics_service_*` global function wrappers |
