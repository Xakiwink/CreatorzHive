# SocialAccountRepository.php — Explained

**File:** `src/Repositories/SocialAccountRepository.php`
**Namespace:** `CreatorzHive\Repositories`

---

## Purpose

All database operations for `social_accounts`. This is the most security-sensitive repository because it handles platform OAuth tokens — all tokens are AES-256-CBC encrypted at rest via `TokenCrypto`.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$db` | `Connection` | Query execution |
| `$crypto` | `TokenCrypto` | Encrypt tokens on write, decrypt on read |

---

## Token Encryption Contract

- **Write**: All `access_token` and `refresh_token` values are encrypted via `encryptDb()` before saving (prefixed with `czenc1:`)
- **Read**: `accountDecryptRow()` is called on every fetch — `decryptDb()` handles both encrypted (new) and plaintext (legacy) values transparently
- **Public listings** (`listSummaryForUser`, `findSummaryForUserPlatform`): Deliberately omit `access_token` and `refresh_token` columns — safe for frontend consumption

---

## Methods

### `accountDecryptRow(array $row): array`
Decrypts `access_token` and `refresh_token` fields in a fetched row. Called by all fetch methods that return full rows.

### `accountEncryptToken(string $plaintext): string`
Wraps `TokenCrypto::encryptDb()`.

### `accountFetch(int $userId, string $platform, bool $activeOnly): ?array`
Fetches a single account for a user+platform. Normalizes platform name via `PlatformHelper`. Decrypts tokens.

### `accountFetchById(int $accountId, int $userId): ?array`
Fetch by ID with ownership check. Decrypts tokens. Used by `FetchAnalyticsJob`.

### `accountUpsert(int $userId, array $data): void`
INSERT ... ON DUPLICATE KEY UPDATE. The unique key is `(user_id, platform)` — one account per platform per user.

Encrypts both tokens before writing. Sets `is_active=1` and updates `connected_at`/`updated_at`.

### `listSummaryForUser(int $userId): array`
Returns all accounts for a user **without tokens** — safe for settings/integrations page. Includes: id, platform, username, display_name, follower_count, token_expires_at, is_active.

### `findSummaryForUserPlatform(int $userId, string $platform): ?array`
Like `listSummaryForUser` but for a single platform. Used by `SettingsController::connectPlatform()` to return the newly connected account.

### `deactivate(int $userId, string $platform): void`
Sets `is_active=0` for disconnect. Token data preserved (not deleted) in case user reconnects.

### `accountMigratePlaintextTokens(): array`
One-time migration utility. Scans all rows, checks if token already encrypted via `isEncryptedDb()`, encrypts plaintext ones. Returns `{ scanned, encrypted, skipped }`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Security/TokenCrypto.php` | AES-256 encryption |
| `src/Helpers/PlatformHelper.php` | Platform name normalization |
| `src/Services/SocialApiService.php` | Calls `accountFetch()` before publishing |
| `src/Jobs/FetchAnalyticsJob.php` | Uses `accountFetchById()` |
| `database/schema.sql` | `social_accounts` table, UNIQUE(user_id, platform) |
