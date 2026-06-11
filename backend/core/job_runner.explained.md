# job_runner.php — Explained

**File:** `backend/core/job_runner.php`

---

## Purpose

The job execution engine. Fetches pending jobs from the `job_queue` table, resolves handler classes from the DI container, executes them with retry logic, and logs all activity.

---

## Key Functions

### `job_runner_run(string $queue, int $maxJobs): void`

Main execution loop. Called by `scripts/cron.php`.

**Process for each job:**
1. SELECT pending jobs from queue WHERE `available_at <= NOW()`, ordered by ID ASC (FIFO)
2. Atomic status lock: UPDATE `status='running'` WHERE `status='pending'` — skips if already grabbed by another process (concurrency-safe)
3. Decode JSON payload
4. Resolve handler via `job_runner_resolve_callable()`
5. Execute `$handler($payload)`
6. On success: `status='completed'`
7. On exception:
   - If attempts < max_attempts (default 3): reschedule with exponential backoff
   - If exhausted: `status='failed'`

**Backoff schedule:**
| Attempt | Delay |
|---------|-------|
| 1 | 5 minutes (300s) |
| 2 | 15 minutes (900s) |
| 3 | 45 minutes (2700s) |

### `job_runner_resolve_callable(string $jobClass): ?callable`

Maps job class strings to OOP handler classes via a hardcoded class map:

| String | Handler Class |
|--------|--------------|
| `publish_post` | `PublishPostJob` |
| `App\\Jobs\\PublishPostJob` | `PublishPostJob` (legacy) |
| `fetch_analytics` | `FetchAnalyticsJob` |
| `cleanup_media` | `CleanupMediaJob` |
| `send_notification` | `SendNotificationJob` |

Resolves from `$GLOBALS['cz_container']`, verifies `instanceof JobHandlerInterface`.

Returns a closure wrapping `$handler->handle($payload)`.

### `job_runner_dispatch(...)`: int

Procedural job dispatch. Delegates to `JobQueueRepository::dispatch()` if container available, else falls back to direct `db_insert()`.

### `job_runner_log_line(string $level, string $message): void`
Appends `[timestamp] [LEVEL] message` to `backend/storage/logs/jobs-YYYY-MM-DD.log`. Uses `@file_put_contents()` with `FILE_APPEND`.

### Utility Functions

| Function | Purpose |
|----------|---------|
| `job_runner_cancel(int $jobId)` | Cancel a pending job |
| `job_runner_retry_failed(int $jobId)` | Reset failed job to pending |
| `job_runner_stats_by_status()` | Count jobs by status |
| `job_runner_list_failed(int $limit)` | List recent failed jobs |
| `job_runner_flush_finished()` | Delete all completed/failed jobs |
| `job_runner_cleanup_completed_older_than_days(int)` | Delete old completed jobs |

---

## Concurrency Safety

The atomic `UPDATE ... WHERE status='pending'` lock ensures two cron processes running simultaneously cannot grab the same job. If `UPDATE` affects 0 rows (job already grabbed), the process skips it with `continue`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `scripts/cron.php` | Calls `job_runner_run()` |
| `src/Jobs/JobHandlerInterface.php` | Contract for all job handlers |
| `src/Repositories/JobQueueRepository.php` | OOP dispatch alternative |
| `database/schema.sql` | `job_queue` table |
