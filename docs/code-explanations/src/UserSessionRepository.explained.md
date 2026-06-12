# UserSessionRepository.php — Explained

**File:** `src/Repositories/UserSessionRepository.php`

---

## Purpose

Read and write the `sessions` table. Tracks active PHP sessions per user with IP address and user agent. Supports session listing and revocation for the "active sessions" feature in security settings.

---

## Methods

### `sessionTouch(int $userId): void`
Upserts the current session into the `sessions` table on each request:
- Uses `session_id()` as the primary key
- INSERT ... ON DUPLICATE KEY UPDATE `last_active`, `ip_address`, `user_agent`
- Does nothing if no active session (`session_id() === ''`)
- Truncates user agent to 65,000 characters to prevent DB overflow

`payload` is always stored as `'{}'` — the actual PHP session data is stored on disk by PHP's session handler, not here.

### `sessionDestroyById(string $sessionId): void`
Deletes a single session row by ID. No ownership check — caller must validate authorization.

### `sessionListForUser(int $userId): array`
Returns all session rows for a user: `id`, `ip_address`, `user_agent`, `last_active`, `created_at`. Ordered by `last_active DESC`.

### `sessionRevoke(string $sessionId, int $userId): bool`
Deletes a session row scoped by both session ID and user ID (prevents revoking other users' sessions).

### `sessionRevokeOthers(int $userId, string $currentSessionId): void`
Deletes all sessions for the user except the current one. Used by "log out all other devices" feature.

---

## Notes

- The compat bridge uses the prefix `user_session_*` but the method names inside use `session*` (e.g., `sessionTouch` exposed as `user_session_touch`).
- `touch()` in the compat bridge maps to `sessionTouch()` in this class.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/SettingsController.php` | Lists and revokes sessions |
| `src/Middleware/AuthMiddleware.php` | Calls `touch()` (via compat) on each authenticated request |
| `src/Jobs/CleanupMediaJob.php` | Deletes sessions older than 30 days |
| `backend/compat/models.php` | `user_session_*` global function wrappers |
