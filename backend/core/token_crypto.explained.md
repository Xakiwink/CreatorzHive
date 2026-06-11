# token_crypto.php — Explained

**File:** `backend/core/token_crypto.php`

---

## Purpose

Procedural bridge to the OOP `TokenCrypto` class. Provides global functions for AES-256-CBC encryption/decryption of OAuth tokens stored in the database. Resolves the `TokenCrypto` instance from the DI container when available, or creates a fallback singleton.

---

## Constant

| Constant | Value | Description |
|----------|-------|-------------|
| `TOKEN_CRYPTO_DB_PREFIX` | `'czenc1:'` | Prefix added to all encrypted values stored in MySQL TEXT columns |

---

## Resolution: `token_crypto_resolve(): TokenCrypto`

1. Tries to get `TokenCrypto` from the DI container via `Application::instance()`
2. Falls back to a static singleton `new TokenCrypto()` (direct instantiation)

The fallback ensures the function works in CLI scripts and tests that don't boot the full container.

---

## Functions

| Function | Description |
|----------|-------------|
| `token_crypto_encryption_key(): string` | Returns the current AES encryption key |
| `token_crypto_pack(string $plaintext): string` | Encrypts plaintext (generic, without DB prefix) |
| `token_crypto_unpack(string $packed): string` | Decrypts a packed value |
| `token_crypto_encrypt_db(string $plaintext): string` | Encrypts and prepends `czenc1:` prefix for DB storage |
| `token_crypto_decrypt_db(string $stored): string` | Decrypts a `czenc1:` prefixed value from DB |
| `token_crypto_is_encrypted_db(string $stored): bool` | Returns `true` if value starts with `czenc1:` |

---

## Usage

These functions are called by `SocialAccountRepository` when reading/writing tokens:
- **Write path**: `token_crypto_encrypt_db($plainToken)` → store in DB
- **Read path**: `token_crypto_is_encrypted_db($stored)` → if true, `token_crypto_decrypt_db($stored)`

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Security/TokenCrypto.php` | OOP class that implements the actual AES encryption |
| `src/Repositories/SocialAccountRepository.php` | Primary caller — all token reads/writes go through these functions |
| `backend/compat/models.php` | Exposes `social_account_encrypt_token()` / `social_account_decrypt_row()` as compat wrappers |
