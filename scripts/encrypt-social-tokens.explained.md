# encrypt-social-tokens.php — Explained

**File:** `scripts/encrypt-social-tokens.php`

---

## Purpose

One-time migration script. Scans all rows in `social_accounts` and encrypts any plaintext `access_token` / `refresh_token` values using AES-256-CBC (the `czenc1:` prefix scheme). Safe to re-run — already-encrypted tokens are skipped.

---

## Usage

```bash
php scripts/encrypt-social-tokens.php
```

---

## Output

```
Done. scanned=12 encrypted=8 already_encrypted_or_empty=4
```

- **scanned**: total rows inspected
- **encrypted**: tokens that were plaintext and are now encrypted
- **already_encrypted_or_empty**: tokens already starting with `czenc1:` or that were NULL/empty

---

## Warning

If `APP_SECRET` is empty, prints a warning to stderr but continues using an insecure dev key. Run only when `APP_SECRET` matches the value that was (or will be) used at runtime, otherwise tokens will be encrypted with the wrong key.

---

## Implementation

Delegates entirely to `social_account_migrate_plaintext_tokens()` — a compat bridge function that calls `SocialAccountRepository::migratePlaintextTokens()`.

---

## When to Use

Run once after:
- Deploying the OOP migration to a server that had tokens stored in plaintext
- Importing a database dump from a pre-encryption version of the app

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/SocialAccountRepository.php` | `migratePlaintextTokens()` implementation |
| `src/Core/Security/TokenCrypto.php` | Encryption logic, `czenc1:` prefix |
| `backend/compat/models.php` | `social_account_migrate_plaintext_tokens()` bridge |
