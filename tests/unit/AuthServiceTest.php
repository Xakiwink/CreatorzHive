<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testHashPasswordReturnsBcryptHash(): void
    {
        $hash = auth_service_hash_password('secret123');
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertGreaterThan(50, strlen($hash));
    }

    public function testCheckPasswordReturnsTrueForCorrectPassword(): void
    {
        $hash = auth_service_hash_password('MyPass2024!');
        $this->assertTrue(auth_service_check_password('MyPass2024!', $hash));
    }

    public function testCheckPasswordReturnsFalseForWrongPassword(): void
    {
        $hash = auth_service_hash_password('correct');
        $this->assertFalse(auth_service_check_password('wrong', $hash));
    }

    public function testGenerateVerificationTokenIsUnique(): void
    {
        try {
            db_fetch('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }

        $u = user_create([
            'name' => 'Token Tester',
            'username' => 'toktest' . bin2hex(random_bytes(3)),
            'email' => 'tok' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => auth_service_hash_password('Password123!'),
            'role' => 'creator',
        ]);

        $a = auth_service_generate_verification_token((int) $u);
        $b = auth_service_generate_verification_token((int) $u);
        $this->assertNotSame($a, $b);
        $this->assertGreaterThan(30, strlen($a));
    }

    public function testGeneratePasswordResetTokenIsUnique(): void
    {
        try {
            db_fetch('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }

        $u = user_create([
            'name' => 'Reset Tester',
            'username' => 'rstest' . bin2hex(random_bytes(3)),
            'email' => 'rst' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => auth_service_hash_password('Password123!'),
            'role' => 'creator',
        ]);

        $a = auth_service_generate_password_reset_token((int) $u);
        $b = auth_service_generate_password_reset_token((int) $u);
        $this->assertNotSame($a, $b);
    }
}
