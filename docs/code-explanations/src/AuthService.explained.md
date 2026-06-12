# AuthService.php — Explained

**File:** `src/Services/AuthService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

Handles authentication-related business logic: password hashing/verification, email verification token generation, password reset token/OTP generation, and token validation. Does NOT handle sessions or HTTP concerns — those belong in `AuthController`.

---

## Class: AuthService

### Constructor
Receives `Connection $db` for all database operations.

### Methods

#### `generateVerificationToken(int $userId): string`
Creates a 64-character hex email verification token for a new user.

**Steps:**
1. `generateToken(64)` — `bin2hex(random_bytes(32))`
2. INSERT into `email_verifications` with expiry `NOW() + 24 hours`

**Returns:** The plaintext token (included in verification email URL)

#### `generatePasswordResetToken(int $userId): string`
Creates a password reset token valid for 1 hour.

**Returns:** 64-char hex token

#### `generatePasswordResetOtp(int $userId): string`
Creates a 6-digit OTP for password reset. Stored in format: `{otp}:{24-char-random}`.

**Steps:**
1. Generate `random_int(100000, 999999)` — secure random 6-digit OTP
2. Invalidate old unused reset tokens for this user
3. INSERT with expiry `NOW() + 10 minutes`

**Returns:** Just the 6-digit OTP (user enters in browser; random suffix stays in DB for lookup)

#### `validateResetToken(string $token): ?array`
Looks up a valid (unused, not-expired) reset token.

```sql
SELECT * FROM password_resets 
WHERE token = :token AND used_at IS NULL AND expires_at > NOW()
```

**Returns:** Full row array, or `null` if not found/expired/used

#### `validateResetOtp(int $userId, string $otp): ?array`
Validates a 6-digit OTP by searching for matching DB row.

```sql
SELECT * FROM password_resets 
WHERE user_id = :user_id AND token LIKE '{otp}:%' 
AND used_at IS NULL AND expires_at > NOW()
```

**Note:** Uses `LIKE` with OTP prefix to find the matching row. The `:` separator ensures the OTP is an exact prefix match.

#### `validateVerificationToken(string $token): ?array`
Looks up a valid email verification token.

#### `hashPassword(string $password): string`
Hashes password with bcrypt cost 12:
```php
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
```

#### `checkPassword(string $plain, string $hash): bool`
Verifies a password against a bcrypt hash via `password_verify()`.

#### `dummyPasswordCheck(string $plain): void`
Performs a fake password verification against a dummy hash. Called when user is not found to prevent timing attacks that would allow user enumeration.

**Security explanation:** Without this, an attacker could measure the response time difference between "user not found" (fast) and "wrong password" (slow bcrypt). With the dummy check, both take similar time.

#### `hasRecentPasswordResetRequest(int $userId): bool`
Throttles password reset requests. Returns `true` if a reset request was made in the last 60 seconds.

#### `markPasswordResetUsed(int $id): void`
Sets `used_at = NOW()` on a password_reset row, making it single-use.

#### `markEmailVerificationUsed(int $id): void`
Same for email_verifications.

---

## Security Implications

| Feature | Security Value |
|---------|---------------|
| bcrypt cost 12 | Resistant to GPU brute-force attacks |
| `dummyPasswordCheck()` | Prevents user enumeration via timing |
| 60-second throttle on reset | Prevents OTP brute-force |
| `random_bytes()` for tokens | Cryptographically secure randomness |
| `random_int()` for OTP | Cryptographically secure random integer |
| `expires_at` on all tokens | Tokens expire; stolen tokens have limited window |
| `used_at` marking | Single-use tokens (replay prevention) |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/AuthController.php` | Calls all methods; owns the HTTP layer |
| `src/Repositories/UserRepository.php` | Companion for user CRUD |
| `src/Services/AuthRateLimitService.php` | Companion for login rate limiting |
| `database/schema.sql` | Defines `email_verifications`, `password_resets` tables |
| `backend/storage/email-templates/` | Email templates used for tokens |
