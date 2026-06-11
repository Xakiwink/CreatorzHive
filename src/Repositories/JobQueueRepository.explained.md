# JobQueueRepository.php — Explained

**File:** `src/Repositories/JobQueueRepository.php`
**Namespace:** `CreatorzHive\Repositories`

---

## Purpose

Provides the application-layer API for the `job_queue` table. Used to enqueue, dispatch, and cancel jobs. The actual job execution is handled by `scripts/cron.php` and `backend/core/job_runner.php`.

---

## Job Queue Architecture

Jobs are stored in the `job_queue` MySQL table. `scripts/cron.php` polls the table every minute. Jobs have:
- `queue`: `default`, `analytics`, or `cleanup`
- `job_class`: string identifier (e.g., `publish_post`)
- `payload`: JSON data for the job handler
- `status`: `pending` → `running` → `completed` / `failed`
- `available_at`: job won't run before this datetime (supports delays)

---

## Methods

### `queuePublishJobType(): string`
Returns `'publish_post'` — the canonical job class identifier for publish jobs.

### `queueLegacyPublishClass(): string`
Returns `'App\\Jobs\\PublishPostJob'` — the old class name used before the OOP migration. Kept for cancellation compatibility so old pending jobs from before the migration can still be cancelled.

### `queueEnqueuePublishPost(int $postId): void`
Creates a `pending` publish job on the `default` queue. Payload: `{ post_id: N }`. `available_at = NOW()` (run as soon as cron picks it up).

### `queueCancelPendingPublishForPost(int $postId): void`
Cancels all pending publish jobs for a specific post. Updates `status='failed'`, `error_message='cancelled'`, `failed_at=NOW()`.

Checks **both** current (`publish_post`) and legacy (`App\\Jobs\\PublishPostJob`) job class names via a loop. Uses `JSON_EXTRACT` on the payload to match `post_id`.

### `dispatch(string $jobClass, array $payload, string $queue, int $delaySeconds): int`
Generic job dispatch. Supports optional delay: `available_at = NOW() + delaySeconds`. Returns the new job ID.

---

## Usage Examples

```php
// Queue a publish job
$this->jobQueue->queueEnqueuePublishPost($postId);

// Cancel before editing
$this->jobQueue->queueCancelPendingPublishForPost($postId);

// Dispatch analytics fetch
$this->jobQueue->dispatch('fetch_analytics', ['user_id' => $userId, 'social_account_id' => $accountId]);

// Dispatch cleanup with 1-hour delay
$this->jobQueue->dispatch('cleanup_media', [], 'cleanup', 3600);
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/PostController.php` | Enqueues/cancels publish jobs |
| `scripts/cron.php` | Polls and runs jobs |
| `backend/core/job_runner.php` | Job execution engine |
| `src/Jobs/PublishPostJob.php` | The job class dispatched here |
| `database/schema.sql` | `job_queue` table definition |
