# cron.php — Explained

**File:** `scripts/cron.php`

---

## Purpose

The main scheduled task runner. Processes the `job_queue` table, dispatches periodic analytics fetches, and triggers nightly media cleanup. Designed to be invoked every minute via system cron.

---

## Cron Configuration

```cron
* * * * * php /path/to/creatorzhive/scripts/cron.php >> /tmp/creatorzhive-cron.log 2>&1
```

---

## Overlapping Instance Guard

Uses `flock(LOCK_EX | LOCK_NB)` on `backend/storage/cron.lock`. If another instance is already running, exits cleanly with code 0 (not an error — just skips). This prevents pile-up under slow jobs.

---

## `--queue=<name>` Flag

Run with `--queue=default|analytics|cleanup` to process only that queue (up to 50 jobs) and exit immediately — skips scheduler logic. Useful for manual testing or targeted processing.

---

## Normal Execution (no flag)

### Step 1 — Cleanup
Deletes completed jobs older than 30 days via `job_runner_cleanup_completed_older_than_days(30)`.

### Step 2 — Default Queue
Processes up to 10 jobs from the `default` queue (post publish jobs).

### Step 3 — Analytics Queue (hourly)
Reads `cron-state.json` from `backend/storage/`. If `last_analytics` timestamp is more than 3,600 seconds ago:
1. Queries all `social_accounts WHERE is_active = 1`
2. Dispatches a `fetch_analytics` job per account onto the `analytics` queue
3. Immediately runs the analytics queue (up to 50 jobs)
4. Updates `last_analytics` timestamp in state

### Step 4 — Cleanup Queue (daily at 2 AM)
Checks current hour (server timezone). If it is hour 2 and `last_cleanup_day` ≠ today:
1. Dispatches `cleanup_media` job onto the `cleanup` queue
2. Immediately runs cleanup queue
3. Updates `last_cleanup_day` in state

### Step 5 — Persist State
Writes updated state back to `cron-state.json` as pretty-printed JSON.

---

## State File

`backend/storage/cron-state.json`:
```json
{
    "last_analytics": 1749500000,
    "last_cleanup_day": "2026-06-10"
}
```

Initialized to empty `{}` if file does not exist or contains invalid JSON.

---

## Bootstrap

Uses `cli_boot_oop(true)` — loads full OOP + procedural compat layer. All `job_runner_*` and `db_fetch_all()` calls are procedural compat bridge functions.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/job_runner.php` | `job_runner_*` procedural functions |
| `src/Jobs/PublishPostJob.php` | Handles `default` queue jobs |
| `src/Jobs/FetchAnalyticsJob.php` | Handles `analytics` queue jobs |
| `src/Jobs/CleanupMediaJob.php` | Handles `cleanup` queue jobs |
| `backend/helpers/cli_bootstrap.php` | `cli_boot_oop()` bootstrap |
