# JobHandlerInterface.php — Explained

**File:** `src/Jobs/JobHandlerInterface.php`
**Namespace:** `CreatorzHive\Jobs`

---

## Purpose

Contract for all background job classes. Enforces a single `handle(array $payload)` method. The job runner uses this interface to dispatch jobs by class name.

---

## Interface

```php
interface JobHandlerInterface
{
    public function handle(array $payload): void;
}
```

---

## Implementations

| Class | Queue | Trigger |
|-------|-------|---------|
| `PublishPostJob` | `default` | Post scheduled for future publish |
| `FetchAnalyticsJob` | `analytics` | Platform connected, or hourly cron |
| `SendNotificationJob` | `default` | Async email notification |
| `CleanupMediaJob` | `cleanup` | Daily at 2 AM |

---

## How the Job Runner Uses It

`backend/core/job_runner.php` resolves the `job_class` string from `job_queue.job_class` to a DI container binding. The container returns an object implementing this interface, and `handle($payload)` is called.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/job_runner.php` | Resolves and calls `handle()` |
| `src/Providers/AppServiceProvider.php` | Registers all job classes in DI container |
