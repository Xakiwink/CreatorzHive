# CleanupMediaJob.php — Explained

**File:** `src/Jobs/CleanupMediaJob.php`
**Namespace:** `CreatorzHive\Jobs`
**Implements:** `JobHandlerInterface`

---

## Purpose

Daily housekeeping job that runs at 2 AM via `scripts/cron.php`. Deletes orphaned media files (uploaded but never attached to a post), expired sessions, used password reset tokens, and used email verification tokens.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | All cleanup queries |

---

## Method: `handle(array $payload): void`

Payload is ignored (`unset($payload)`).

**Cleanup operations:**

### 1. Orphaned Media Files
Finds `media_files` rows that are:
- Older than 30 days
- NOT referenced in `post_media` (not attached to any post)
- NOT used as `cover_media_id` in any non-deleted post

For each orphan:
- Deletes physical file via `unlink(public_path($row['file_path']))`
- Deletes thumbnail if path starts with `uploads/`
- Deletes the `media_files` DB record

### 2. Expired Sessions
`DELETE FROM sessions WHERE last_active < NOW() - 30 days`

### 3. Used Password Reset Tokens
`DELETE FROM password_resets WHERE used_at < NOW() - 24 hours`

### 4. Used Email Verification Tokens
`DELETE FROM email_verifications WHERE used_at < NOW() - 7 days`

All deletion counts are logged via `job_runner_log_line('INFO', ...)`.

---

## Schedule

Runs daily on the `cleanup` queue. `scripts/cron.php` dispatches it when:
- Queue is `cleanup`
- Current hour is 2 (between 02:00 and 02:59)
- Not already run today (state tracked in `cron-state.json`)

---

## Notes

- Uses `@unlink()` (suppressed errors) — safe if file already gone
- The 30-day grace period for media prevents deletion of files uploaded but not yet attached (e.g., draft in progress)

---

## Related Files

| File | Relationship |
|------|-------------|
| `scripts/cron.php` | Dispatches this job daily at 2 AM |
| `backend/core/job_runner.php` | `job_runner_log_line()` |
| `database/schema.sql` | `media_files`, `sessions`, `password_resets`, `email_verifications` tables |
