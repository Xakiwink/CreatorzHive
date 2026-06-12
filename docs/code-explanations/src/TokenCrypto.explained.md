# TokenCrypto.php — Explained

**File:** `src/Core/Security/TokenCrypto.php`
**Namespace:** `CreatorzHive\Core\Security`

---

## Purpose

Provides **AES-256-CBC encryption** for sensitive tokens (OAuth access tokens, refresh tokens) stored in the database. This ensures that if the database is breached, tokens cannot be used without also knowing the `APP_SECRET` environment variable.

---

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `DB_PREFIX` | `'czenc1:'` | Version prefix prepended to all DB-stored encrypted values. Identifies the format and allows future format changes. |

---

## Class: TokenCrypto

### Methods

#### `encryptionKey(): string`
Derives the 32-byte AES key from `APP_SECRET` environment variable.

**Logic:**
1. Read `APP_SECRET` from env
2. If empty, fall back to hardcoded dev key (INSECURE — see warning)
3. Apply `hash('sha256', $raw, true)` → 32-byte binary key

**WARNING:** The fallback dev key is hardcoded and publicly known. **MUST SET `APP_SECRET` in production.**

#### `pack(string $plaintext): string`
Encrypts a string using AES-256-CBC.

**Steps:**
1. Generate 16 random bytes IV via `random_bytes(16)` (cryptographically secure)
2. Encrypt with `openssl_encrypt(text, 'AES-256-CBC', key, OPENSSL_RAW_DATA, iv)`
3. Prepend IV to ciphertext: `$iv . $cipher`
4. Base64-encode the result

**Returns:** Base64-encoded `IV || ciphertext`, or `''` on failure

#### `unpack(string $packed): string`
Decrypts a value produced by `pack()`.

**Steps:**
1. Base64-decode
2. Extract first 16 bytes as IV
3. Remaining bytes are ciphertext
4. Decrypt with `openssl_decrypt(cipher, 'AES-256-CBC', key, OPENSSL_RAW_DATA, iv)`

**Returns:** Original plaintext, or `''` on failure

#### `encryptDb(string $plaintext): string`
Encrypts and prepends the `czenc1:` DB prefix.

**Returns:** `czenc1:{base64-encoded-encrypted-value}`

#### `decryptDb(string $stored): string`
Decrypts a DB-stored value. Handles both:
- Encrypted values with `czenc1:` prefix → strips prefix, decrypts
- Legacy plaintext values (no prefix) → returns as-is (backward compatibility)

#### `isEncryptedDb(string $stored): bool`
Returns `true` if the stored value starts with `czenc1:`.

---

## Usage Pattern

```php
// Encrypt before storing to DB:
$encrypted = $crypto->encryptDb($accessToken);
// Stored: "czenc1:BASE64_DATA..."

// Decrypt when reading from DB:
$plainToken = $crypto->decryptDb($storedValue);
// Returns original access token
```

---

## Security Implications

| Risk | Level | Notes |
|------|-------|-------|
| APP_SECRET not set in production | CRITICAL | Tokens encrypted with public dev key |
| Silent failure on OpenSSL error | MEDIUM | `pack()` returns `''` instead of throwing |
| IV reuse | LOW | `random_bytes(16)` prevents this |
| AES-256-CBC (no MAC) | LOW | No authentication tag — encrypted values could be tampered. Consider AES-256-GCM for new versions. |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/SocialAccountRepository.php` | Uses `encryptDb()` on write, `decryptDb()` on read |
| `src/Services/PlatformApiSecretsService.php` | Encrypts admin API credentials |
| `src/Providers/AppServiceProvider.php` | Instantiates and injects TokenCrypto |
| `.env` | Source of `APP_SECRET` |
