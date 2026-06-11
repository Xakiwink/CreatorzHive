# PublishPostJob.php — Explained

**File:** `src/Jobs/PublishPostJob.php`
**Namespace:** `CreatorzHive\Jobs`
**Implements:** `JobHandlerInterface`

---

## Purpose

The most critical background job. Takes a scheduled post from the database and publishes it to all targeted social media platforms simultaneously. Called by `cron.php` every minute via the job queue.

---

## Class: PublishPostJob

### Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$posts` | `PostRepository` | Fetch post data, update status |
| `$accounts` | `SocialAccountRepository` | Get connected platform accounts |
| `$socialApi` | `SocialApiService` | Make platform API publish calls |
| `$notifications` | `NotificationService` | Create success/failure notifications |
| `$analytics` | `AnalyticsRepository` | Update post counters |
| `$db` | `Connection` | Write platform_post_results |

### Method: `handle(array $payload): void`

**Called by:** `cron.php` → `job_runner_run()` → job handler dispatch

**Payload structure:**
```php
[
    'post_id' => 42,
    'scheduled_at' => '2026-06-15 14:00:00'
]
```

**Execution Flow:**

1. **Fetch post** — `PostRepository::findById($payload['post_id'])`
   - Returns null if post not found or soft-deleted → exits

2. **Check status** — Only processes posts with `status = 'scheduled'`
   - Prevents re-publishing already published posts

3. **Decode platforms** — `json_decode($post['platforms'])` → e.g., `['instagram', 'tiktok']`

4. **Fetch accounts** — `SocialAccountRepository::findByUserAndPlatforms($userId, $platforms)`
   - Returns only active connected accounts for the user's targeted platforms

5. **Publish to each platform** — `SocialApiService::publish($account, $post)` per account
   - Wraps in try/catch for individual platform failures
   - On success: records `platform_post_results` row with `status='success'`
   - On failure: records `platform_post_results` row with `status='failed'`, `error_message`

6. **Update post status**
   - All succeed → `posts.status = 'published'`, `posts.published_at = NOW()`
   - Any failures → `posts.status = 'failed'` (or partial success tracking)

7. **Update analytics** — `AnalyticsRepository::incrementPublished($userId)`

8. **Create notification** — `NotificationService::create('post_published', userId)` or `'post_failed'`

---

## Error Handling

- Each platform publish is independent. One failure doesn't block others.
- Platform errors are stored in `platform_post_results.error_message`
- The job itself succeeds even if some platforms fail (status written to job_queue as 'completed')
- Failed platform results are visible in the post detail view

---

## Database Interactions

| Action | Table | SQL Operation |
|--------|-------|---------------|
| Get post | posts | SELECT WHERE id + is_deleted=0 |
| Get accounts | social_accounts | SELECT WHERE user_id + platform IN (...) + is_active=1 |
| Write results | platform_post_results | INSERT per platform |
| Update post | posts | UPDATE status, published_at |
| Update analytics | analytics | UPDATE published_posts++, scheduled_posts-- |
| Create notification | notifications | INSERT |

---

## Related Files

| File | Relationship |
|------|-------------|
| `scripts/cron.php` | Dispatches and runs this job |
| `src/Controllers/PostController.php` | Dispatches the job when creating scheduled posts |
| `src/Repositories/JobQueueRepository.php` | job_queue persistence |
| `src/Services/SocialApiService.php` | Performs actual platform API calls |
| `src/Services/NotificationService.php` | Creates user notifications |
| `database/schema.sql` | platform_post_results table |
