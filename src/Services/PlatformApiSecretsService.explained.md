# PlatformApiSecretsService.php — Explained

**File:** `src/Services/PlatformApiSecretsService.php`

---

## Purpose

Manages admin-configured platform API credentials. Credentials can be stored either in environment variables (`.env`) or via the admin UI (encrypted JSON file at `backend/storage/platform-api-secrets.json`). UI-stored values take priority over env vars.

---

## Storage

**File:** `backend/storage/platform-api-secrets.json`

Format:
```json
{
    "v": 1,
    "fields": {
        "instagram_access_token": "<encrypted>",
        "facebook_page_id": "123456789"
    }
}
```

Permissions: `chmod 0600` — readable only by the web server process. Test path override via `$GLOBALS['_cz_platform_secrets_test_path']`.

---

## Credential Groups

Five groups with defined fields:

| Group | Fields |
|-------|--------|
| `meta` | `meta_app_id`, `meta_app_secret`, `instagram_access_token`, `instagram_business_id`, `facebook_access_token`, `facebook_page_id` |
| `tiktok` | `tiktok_access_token`, `tiktok_privacy_level` |
| `youtube` | `youtube_access_token`, `youtube_channel_id`, `youtube_privacy_status`, `google_client_id`, `google_client_secret` |
| `twitter` | `twitter_bearer_token` |

Each field definition has: `label`, `env` (env var name), `secret` (bool), optional `platform`, `help`.

---

## Resolution Priority

### `resolve(string $fieldKey): string`
1. UI-stored value (decrypted from JSON file)
2. Env var (from `.env`)
3. Empty string if neither

### `resolveEnv(string $envKey): string`
Given an env var name (e.g. `INSTAGRAM_ACCESS_TOKEN`), finds the corresponding field key and calls `resolve()`. Falls back to direct `env($envKey)` if no field definition matches.

---

## Encryption

All secret fields stored in JSON are encrypted using `TokenCrypto::pack()` (AES-256-CBC). Non-secret fields (IDs, channel IDs) are stored in plaintext.

### `encrypt(string $plaintext): string`
Calls `TokenCrypto::pack()`.

### `decrypt(string $encoded): string`
Calls `TokenCrypto::unpack()`.

---

## Admin UI Methods

### `fieldStatus(string $fieldKey): array`
Returns the UI-facing status of a field:
```php
[
    'key' => 'instagram_access_token',
    'label' => 'Instagram access token',
    'is_secret' => true,
    'configured' => true,
    'source' => 'ui',          // 'ui', 'env', or 'none'
    'preview' => '••••••••1234',  // masked value
]
```

### `groupPublic(string $groupKey): array`
Returns all field statuses for a group — used by the admin credentials page.

### `mask(string $value): string`
Shows last 4 characters only: `••••••••1234`. Values ≤4 chars: all bullets.

### `applyGroupUpdate(string $groupKey, array $payload): array`
Saves or clears credentials from an admin form submission:
- `credential_{fieldKey}` key → encrypt and store
- `clear_{fieldKey} = 1` → remove from store
- Writes JSON file only if something changed
- Returns `['saved' => [...], 'cleared' => [...]]`

Max value length: 4096 bytes for secrets, 512 bytes for non-secrets.

---

## Platform Token Helpers

### `primaryTokenField(string $platform): ?string`
Maps platform slug to its primary token field key (e.g. `'instagram'` → `'instagram_access_token'`).

### `platformTokenConfigured(string $platform): bool`
Returns `true` if any secret field for the platform has a configured value (UI or env).

### `tokenSourceForPlatform(string $platform): string`
Returns `'ui'`, `'env'`, or `'none'`.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Security/TokenCrypto.php` | Used for field encryption/decryption |
| `src/Controllers/AdminUserController.php` | Calls `applyGroupUpdate()`, `allGroupsPublic()` |
| `src/Services/AdminService.php` | Calls `platformTokenConfigured()` for integration status |
| `src/Services/GoogleAuthService.php` | Calls `resolve()` for Google credentials |
| `backend/compat/services.php` | `platform_api_secrets_*` global function wrappers |
