# NotificationService.php — Explained

**File:** `src/Services/NotificationService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

Centralized notification dispatch. Checks user preferences before creating in-app notifications or sending emails. Called by controllers and jobs whenever a significant event occurs.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | Not used directly (legacy injection) |
| `$users` | `UserRepository` | Look up user email address |
| `$preferences` | `NotificationPreferenceRepository` | Check user's notification toggles |
| `$notifications` | `NotificationRepository` | Write in-app notification rows |

---

## Public Business Methods

Each corresponds to a user-visible event:

| Method | Event | In-App | Email Pref |
|--------|-------|--------|------------|
| `postPublished($userId, $title, $postId)` | Post went live | ✅ | `email_post_published` |
| `postFailed($userId, $title, $reason)` | Post publish failed | ✅ | `email_post_failed` |
| `dealStatusChanged($userId, $title, $newStatus)` | Deal moved to new stage | ✅ | `email_deal_updated` |
| `dealCompleted($userId, $title, $dealId)` | Deal marked completed | ✅ | `email_deal_updated` |
| `invoicePaid($userId, $invoiceNumber, $amount)` | Invoice marked paid | ✅ | `email_invoice_paid` |
| `welcomeUser($userId, $name)` | New user registered | ✅ | — |

---

## Infrastructure Methods

### `prefs(int $userId): array`
Fetches user notification preferences. Returns defaults (all enabled) if no preference record exists.

### `userEmail(int $userId): string`
Returns user's email address for sending emails.

### `maybeEmail(int $userId, string $prefKey, string $subject, string $html): void`
Checks preference for `$prefKey`. If enabled, looks up email, calls `mailer_send()`. If pref disabled or email empty, skips silently.

### `allowInApp(int $userId, string $type): bool`
Maps notification type to preference key:
- `post_published` → `push_post_published`
- `deal_updated`/`deal_completed` → `push_deal_updated`
- All others → `true` (always allowed)

### `createInApp(int $userId, string $type, string $title, string $body, string $actionUrl, ?string $icon): void`
Guards with `allowInApp()`. If allowed, calls `notification_create()` (procedural bridge). If not, silently skips.

---

## Known Issues

- `createInApp()` calls `notification_service_allow_in_app()` (procedural compat wrapper for its own `allowInApp()` method) — circular/redundant. Should call `$this->allowInApp()` directly.
- `mailer_send()` called without `use function` import — relies on global namespace fallthrough.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/NotificationRepository.php` | Writes in-app notifications |
| `src/Repositories/NotificationPreferenceRepository.php` | Reads user notification prefs |
| `backend/core/mailer.php` | `mailer_send()` function |
| `backend/compat/services.php` | `notification_create()` procedural bridge |
