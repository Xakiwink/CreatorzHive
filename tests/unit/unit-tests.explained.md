# Unit Tests — Explained

**Files:**
- `tests/unit/AuthServiceTest.php`
- `tests/unit/GoogleAuthServiceTest.php`
- `tests/unit/SocialAccountTokenTest.php`
- `tests/unit/ValidatorTest.php`
- `tests/unit/SchedulerServiceTest.php`
- `tests/unit/PlatformApiSecretsTest.php`
- `tests/unit/MetaOAuthTest.php`

---

## Overview

Unit tests for isolated components. Most extend `PHPUnit\Framework\TestCase` directly (not `IntegrationTestCase`). Some require a live database (`markTestSkipped` if unavailable).

---

## Test Files

### `AuthServiceTest`
Tests `AuthService` password hashing and token generation:
- `hashPassword()` returns a bcrypt `$2y$` hash
- `checkPassword()` correctly verifies correct and incorrect passwords
- `generateVerificationToken()` returns unique tokens each call (requires DB)
- `generatePasswordResetToken()` returns unique tokens each call (requires DB)

### `GoogleAuthServiceTest`
Tests `GoogleAuthService` configuration and OAuth URL generation. Backs up and restores `GOOGLE_*` env vars in `setUp/tearDown` to avoid polluting other tests. Tests:
- `isConfigured()` returns true when `GOOGLE_CLIENT_ID` + `GOOGLE_CLIENT_SECRET` are set
- `authorizeUrl()` returns a URL containing `accounts.google.com/o/oauth2/auth`
- Redirect URI is configurable via `GOOGLE_AUTH_REDIRECT_URI`

### `SocialAccountTokenTest`
Tests `TokenCrypto` encryption round-trips:
- `token_crypto_encrypt_db()` produces `czenc1:`-prefixed output
- `token_crypto_decrypt_db()` recovers the original plaintext
- Legacy plaintext tokens (no prefix) are returned as-is (not decrypted, just passed through)
- `token_crypto_is_encrypted_db()` returns false for legacy plaintext

### `ValidatorTest`
Tests `validator_validate()` rule engine:
- `required` rejects empty strings
- `email` rejects malformed addresses
- `min:N` rejects strings shorter than N chars
- `max:N` rejects strings longer than N chars
- Valid inputs pass all rules

### `SchedulerServiceTest`
Tests `job_runner_dispatch()` and related queue functions (requires DB):
- `job_runner_dispatch()` inserts a row into `job_queue`
- Exponential backoff calculations for retry logic
- `job_runner_cancel()` removes a pending job

### `PlatformApiSecretsTest`
Tests `PlatformApiSecretsService`:
- `resolve()` returns empty string when both UI storage and env var are missing
- Env var value is returned when no UI override exists
- UI-stored value takes priority over env var
- `clear_{fieldKey} = 1` removes a stored value

### `MetaOAuthTest`
Tests `MetaOAuthService` OAuth URL and token handling:
- Authorization URL contains correct `client_id` and `redirect_uri`
- State parameter is included in the URL

---

## Notes

- Tests that need a database call `$this->markTestSkipped()` when DB is unavailable — this makes the test suite runnable without a database
- `SocialAccountTokenTest` sets `$_ENV['APP_SECRET']` in `setUp()` to use a deterministic key

---

## Related Files

| File | Relationship |
|------|-------------|
| `tests/bootstrap.php` | Loads the full app before tests run |
| `tests/Support/IntegrationTestCase.php` | Used by integration tests (not unit tests) |
