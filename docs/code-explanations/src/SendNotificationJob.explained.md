# SendNotificationJob.php — Explained

**File:** `src/Jobs/SendNotificationJob.php`
**Namespace:** `CreatorzHive\Jobs`
**Implements:** `JobHandlerInterface`

---

## Purpose

Asynchronous email notification sender. Checks user's notification preferences before sending. Renders an HTML email template and calls `mailer_send()`. Runs on the `default` queue.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$users` | `UserRepository` | Fetch user email and name |
| `$preferences` | `NotificationPreferenceRepository` | Check notification opt-in flags |

---

## Method: `handle(array $payload): void`

**Payload:**
```json
{
  "user_id": 42,
  "type": "post_published",
  "data": {
    "subject": "Your post was published",
    "template": "post-published",
    "body": "..."
  }
}
```

**Flow:**

1. Validate `user_id` and `type`
2. Map notification type to preference key via `preferenceKeyForType()`
3. If preference key found: check user's opt-in — skip silently if disabled
4. Look up user email and name
5. Merge data with defaults `{ subject: 'CreatorzHive', body: '...' }`
6. Render HTML via `mailer_render_template_by_name()` using `data.template` + `.html`
7. Send with `mailer_send($email, $subject, $body)`

---

## Type-to-Preference Mapping

| Notification Type | Preference Key |
|-------------------|---------------|
| `post_published` | `email_post_published` |
| `post_failed` | `email_post_failed` |
| `deal_updated` | `email_deal_updated` |
| `invoice_paid` | `email_invoice_paid` |
| `weekly_summary` | `email_weekly_summary` |
| (others) | null → always sent |

---

## Notes

- Template name is stripped of `.html` suffix if present (for compatibility with both `'post-published'` and `'post-published.html'` values)
- Unlike `NotificationService::maybeEmail()` (synchronous), this runs asynchronously via cron job queue — useful for high-frequency events

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/mailer.php` | `mailer_send()`, `mailer_render_template_by_name()` |
| `src/Services/NotificationService.php` | May dispatch this job, or call mailer directly |
| `scripts/cron.php` | Executes queued jobs |
