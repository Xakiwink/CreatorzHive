# FetchAnalyticsJob.php — Explained

**File:** `src/Jobs/FetchAnalyticsJob.php`
**Namespace:** `CreatorzHive\Jobs`
**Implements:** `JobHandlerInterface`

---

## Purpose

Fetches today's social media metrics from a single platform account and writes them to `analytics_snapshots`. Also triggers a re-aggregation of the `analytics` summary table. Runs on the `analytics` queue hourly.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$accounts` | `SocialAccountRepository` | Fetch account with decrypted token |
| `$socialApi` | `SocialApiService` | Call platform API for metrics |
| `$analytics` | `AnalyticsService` | Aggregate totals after snapshot write |
| `$db` | `Connection` | Write to `analytics_snapshots` |

---

## Method: `handle(array $payload): void`

**Payload:**
```json
{ "user_id": 42, "social_account_id": 7 }
```

**Flow:**

1. Validate `user_id` and `social_account_id`
2. Fetch account (with decrypted token) via `SocialAccountRepository::accountFetchById()`
3. Get today's date: `date('Y-m-d')`
4. Call `SocialApiService::getAnalytics($account, $today)` — fetches live data or returns seeded mock
5. Write snapshot to `analytics_snapshots` with INSERT ... ON DUPLICATE KEY UPDATE (idempotent — safe to re-run for same date)
6. Call `AnalyticsService::aggregateTotals($userId)` to update the `analytics` summary table

**Metrics written:**
`followers`, `impressions`, `reach`, `likes`, `comments`, `shares`, `saves`, `link_clicks` (derived as likes×0.03), `profile_visits` (derived as reach×0.01), `engagement_rate`

---

## Notes

- `link_clicks` and `profile_visits` are estimated (the real APIs don't always return these) — see the 0.03 and 0.01 multipliers in the code
- If `SOCIAL_API_MOCK_FALLBACK=true`, `SocialApiService::getAnalytics()` returns fake data seeded by `crc32(platform + date)` — deterministic for testing

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/SocialApiService.php` | `getAnalytics()` platform calls |
| `src/Services/AnalyticsService.php` | `aggregateTotals()` |
| `src/Repositories/SocialAccountRepository.php` | Token decryption |
| `scripts/cron.php` | Dispatches this job hourly |
| `database/schema.sql` | `analytics_snapshots` table |
