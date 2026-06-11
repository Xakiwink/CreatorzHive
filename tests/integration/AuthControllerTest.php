<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class AuthControllerTest extends IntegrationTestCase
{
    public function testRegisterWithValidDataReturnsSuccess(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $res = $this->dispatchRoute('POST', 'register', [
            'name' => 'Reg Tester',
            'username' => 'regtest' . $suffix,
            'email' => 'regtest' . $suffix . '@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'creator',
            'terms' => '1',
        ]);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);

        db_delete('users', 'email = :e', ['e' => 'regtest' . $suffix . '@example.com']);
    }

    public function testRegisterWithDuplicateEmailReturnsError(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $res = $this->dispatchRoute('POST', 'register', [
            'name' => 'Dup Test',
            'username' => 'uniqueuser' . bin2hex(random_bytes(3)),
            'email' => 'admin@creatorzhive.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'creator',
            'terms' => '1',
        ]);

        $this->assertSame(422, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testRegisterWithWeakPasswordReturnsError(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $res = $this->dispatchRoute('POST', 'register', [
            'name' => 'Weak Pass',
            'username' => 'weakuser' . bin2hex(random_bytes(3)),
            'email' => 'weak' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'creator',
            'terms' => '1',
        ]);

        $this->assertSame(422, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testLoginWithValidCredentialsReturnsSuccess(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $res = $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
    }

    public function testLoginWithWrongPasswordReturnsError(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $res = $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'WrongPassword999!',
        ]);

        $this->assertSame(401, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testRepeatedFailedLoginCreatesSecurityAlertCooldownKey(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        for ($i = 0; $i < 5; $i++) {
            $res = $this->dispatchRoute('POST', 'login', [
                'email' => 'david@creatorzhive.com',
                'password' => 'WrongPassword999!',
            ]);
            $this->assertSame(401, $res->httpStatus);
        }

        $user = user_find_by_email('david@creatorzhive.com');
        $this->assertNotNull($user);

        $alert = db_fetch('SELECT * FROM rate_limits WHERE `key` = :k LIMIT 1', [
            'k' => 'login_alert:user:' . (int) $user['id'],
        ]);
        $this->assertNotNull($alert);
    }

    public function testLoginWithUsernameReturnsSuccess(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $res = $this->dispatchRoute('POST', 'login', [
            'email' => 'davidmposo',
            'password' => 'Creator@1234',
        ]);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
    }

    public function testRegisterWithAdminRoleIsRejected(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'admin-role-' . $suffix . '@example.com';
        $res = $this->dispatchRoute('POST', 'register', [
            'name' => 'Admin Role Test',
            'username' => 'adminrole' . $suffix,
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
            'terms' => '1',
        ]);

        $this->assertSame(422, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testRegisterWithInvalidUsernameFormatIsRejected(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'invalid-username-' . $suffix . '@example.com';
        $res = $this->dispatchRoute('POST', 'register', [
            'name' => 'Invalid Username Test',
            'username' => 'bad username',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'creator',
            'terms' => '1',
        ]);

        $this->assertSame(422, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testLoginWithInactiveAccountReturnsError(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'inactive' . $suffix . '@example.com';
        $uid = user_create([
            'name' => 'Inactive',
            'username' => 'inactive' . $suffix,
            'email' => $email,
            'password' => auth_service_hash_password('Password123!'),
            'role' => 'creator',
        ]);
        db_update('users', ['is_active' => 0], 'id = :id', ['id' => $uid]);

        $res = $this->dispatchRoute('POST', 'login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $this->assertSame(403, $res->httpStatus);

        db_delete('users', 'id = :id', ['id' => $uid]);
    }

    public function testLoginWithUnverifiedEmailReturnsError(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'unverified' . $suffix . '@example.com';
        $uid = user_create([
            'name' => 'Unverified',
            'username' => 'unverified' . $suffix,
            'email' => $email,
            'password' => auth_service_hash_password('Password123!'),
            'role' => 'creator',
        ]);

        $res = $this->dispatchRoute('POST', 'login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $this->assertSame(403, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
        $this->assertStringContainsString('verify your email', strtolower((string) ($res->payload['message'] ?? '')));

        db_delete('users', 'id = :id', ['id' => $uid]);
    }

    public function testLogoutClearsSession(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $login = $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
        $this->assertTrue($login->payload['success'] ?? false);
        $this->assertNotNull(session_get_user());

        $logout = $this->dispatchRoute('POST', 'logout', []);
        $this->assertTrue($logout->payload['success'] ?? false);
        $this->assertNull(session_get_user());
    }

    public function testSessionFingerprintMismatchRequiresReauthentication(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 fingerprint-a';

        $login = $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
        $this->assertSame(200, $login->httpStatus);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 fingerprint-b';
        $protected = $this->dispatchRoute('GET', 'dashboard_data', []);

        $this->assertSame(401, $protected->httpStatus);
        $this->assertFalse($protected->payload['success'] ?? true);
        $this->assertStringContainsString('session validation failed', strtolower((string) ($protected->payload['message'] ?? '')));
    }

    public function testForgotPasswordOtpFlowResetsPassword(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'otp-reset-' . $suffix . '@example.com';
        $initialPassword = 'Password123!';
        $newPassword = 'NewPassword123!';

        $uid = user_create([
            'name' => 'OTP Reset',
            'username' => 'otpreset' . $suffix,
            'email' => $email,
            'password' => auth_service_hash_password($initialPassword),
            'role' => 'creator',
        ]);

        $forgot = $this->dispatchRoute('POST', 'forgot-password', ['email' => $email]);
        $this->assertSame(200, $forgot->httpStatus);
        $this->assertTrue($forgot->payload['success'] ?? false);

        $row = db_fetch(
            'SELECT token FROM password_resets WHERE user_id = :uid AND used_at IS NULL ORDER BY id DESC LIMIT 1',
            ['uid' => $uid]
        );
        $this->assertNotNull($row);
        $parts = explode(':', (string) ($row['token'] ?? ''), 2);
        $otp = $parts[0] ?? '';

        $reset = $this->dispatchRoute('POST', 'reset-password', [
            'email' => $email,
            'otp' => $otp,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $this->assertSame(200, $reset->httpStatus);
        $this->assertTrue($reset->payload['success'] ?? false);

        db_update('users', ['email_verified' => 1], 'id = :id', ['id' => $uid]);

        $login = $this->dispatchRoute('POST', 'login', [
            'email' => $email,
            'password' => $newPassword,
        ]);
        $this->assertSame(200, $login->httpStatus);
        $this->assertTrue($login->payload['success'] ?? false);

        db_delete('password_resets', 'user_id = :uid', ['uid' => $uid]);
        db_delete('users', 'id = :id', ['id' => $uid]);
    }

    public function testForgotPasswordOtpCooldownReturnsRateLimit(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'otp-cooldown-' . $suffix . '@example.com';
        $uid = user_create([
            'name' => 'OTP Cooldown',
            'username' => 'otpcooldown' . $suffix,
            'email' => $email,
            'password' => auth_service_hash_password('Password123!'),
            'role' => 'creator',
        ]);

        $first = $this->dispatchRoute('POST', 'forgot-password', ['email' => $email]);
        $this->assertSame(200, $first->httpStatus);

        $second = $this->dispatchRoute('POST', 'forgot-password', ['email' => $email]);
        $this->assertSame(429, $second->httpStatus);
        $this->assertFalse($second->payload['success'] ?? true);

        db_delete('password_resets', 'user_id = :uid', ['uid' => $uid]);
        db_delete('rate_limits', '`key` = :k', ['k' => 'ip:' . $_SERVER['REMOTE_ADDR'] . ':forgot_password']);
        db_delete('users', 'id = :id', ['id' => $uid]);
    }

    public function testResetPasswordWithWrongOtpReturnsRemainingAttemptsFeedback(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $suffix = bin2hex(random_bytes(4));
        $email = 'otp-wrong-' . $suffix . '@example.com';
        $uid = user_create([
            'name' => 'OTP Wrong',
            'username' => 'otpwrong' . $suffix,
            'email' => $email,
            'password' => auth_service_hash_password('Password123!'),
            'role' => 'creator',
        ]);

        $this->dispatchRoute('POST', 'forgot-password', ['email' => $email]);
        db_delete('rate_limits', '`key` = :k', ['k' => 'ip:' . $_SERVER['REMOTE_ADDR'] . ':forgot_password']);

        $res = $this->dispatchRoute('POST', 'reset-password', [
            'email' => $email,
            'otp' => '000000',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $this->assertSame(400, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
        $this->assertStringContainsString('9 attempts remaining', (string) ($res->payload['message'] ?? ''));
        $this->assertStringContainsString(
            '9 attempts remaining',
            (string) (($res->payload['errors']['otp'][0] ?? ''))
        );

        db_delete('password_resets', 'user_id = :uid', ['uid' => $uid]);
        db_delete('rate_limits', '`key` = :k', ['k' => 'ip:' . $_SERVER['REMOTE_ADDR'] . ':reset_verify']);
        db_delete('users', 'id = :id', ['id' => $uid]);
    }
}
