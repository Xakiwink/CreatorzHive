# UserRepository.php — Explained

**File:** `src/Repositories/UserRepository.php`
**Namespace:** `CreatorzHive\Repositories`

---

## Purpose

All database operations for the `users` table. Provides lookups by ID, email, username, and Google ID; creation flows for regular and OAuth users; and utility methods for profile updates.

---

## Methods

### Lookups

| Method | Query |
|--------|-------|
| `findById(int $id)` | `SELECT * FROM users WHERE id = ? LIMIT 1` |
| `findByEmail(string $email)` | `SELECT * FROM users WHERE email = ? LIMIT 1` |
| `findByUsername(string $username)` | `SELECT * FROM users WHERE username = ? LIMIT 1` |
| `findByGoogleId(string $googleId)` | `SELECT * FROM users WHERE google_id = ? LIMIT 1` — returns null if googleId empty |

### `suggestAvailableUsername(string $email, string $name): string`
Auto-generates a unique username for OAuth registrations:
1. Try email local-part (before `@`), strip non-alphanumeric/dot/dash/underscore chars
2. Fall back to cleaned name if email part is < 3 chars
3. Fall back to `'user'` if still too short
4. Append incrementing number suffix while taken: `johndoe`, `johndoe1`, `johndoe2`, ...

### `create(array $data): int`
Inserts a new user with `email_verified=0`, `is_active=1`. Returns new user ID. **Triggers `trg_after_user_insert`** which auto-creates `analytics`, `notification_preferences`, and `user_preferences` rows.

### `createOAuthUser(array $data): int`
Like `create()` but also accepts `google_id`, `email_verified`, and `avatar_url` from Google profile data. Used by `GoogleAuthController`.

### `update(int $id, array $data): bool`
Generic partial update. Accepts any subset of user columns.

### `linkGoogleId(int $userId, string $googleId): void`
Sets `google_id` for an existing user. Called when a returning user signs in via Google with a matching email but no existing google_id link.

### `updateLastLogin(int $id): void`
Sets `last_login_at = NOW()`.

### `verifyEmail(int $id): void`
Sets `email_verified = 1`.

### `updatePassword(int $id, string $hashedPassword): void`
Updates `password` column with pre-hashed value.

### `isEmailTaken(string $email): bool`
Helper: `findByEmail() !== null`.

### `isUsernameTaken(string $username): bool`
Helper: `findByUsername() !== null`.

### `listAll(int $limit, int $offset): array`
Admin user list — excludes `password` column. Max 500 records per call.

### `countAll(): int`
Total user count.

---

## Security Notes

- All lookups return full row including hashed password — callers must unset `password` before storing in session or returning to frontend
- `findByGoogleId()` guards against empty string to prevent matching rows where `google_id IS NULL` in some MySQL configs

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AuthController.php` | Primary consumer |
| `src/Controllers/GoogleAuthController.php` | `createOAuthUser()`, `linkGoogleId()` |
| `src/Controllers/AdminUserController.php` | `listAll()`, admin create/update |
| `src/Services/AuthService.php` | Calls `hashPassword()` before passing to `create()` |
