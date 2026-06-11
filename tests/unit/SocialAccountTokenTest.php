<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SocialAccountTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_SECRET'] = 'unit-test-secret-key';
    }

    public function testDbEncryptDecryptRoundTrip(): void
    {
        $plain = 'ya29.mock-access-token-value';
        $stored = token_crypto_encrypt_db($plain);
        $this->assertStringStartsWith('czenc1:', $stored);
        $this->assertNotSame($plain, $stored);
        $this->assertSame($plain, token_crypto_decrypt_db($stored));
    }

    public function testLegacyPlaintextStillReadable(): void
    {
        $legacy = 'mock_plain_token_abc';
        $this->assertSame($legacy, token_crypto_decrypt_db($legacy));
        $this->assertFalse(token_crypto_is_encrypted_db($legacy));
    }

    public function testDecryptRowDecryptsBothTokenColumns(): void
    {
        $row = social_account_decrypt_row([
            'id' => 1,
            'access_token' => token_crypto_encrypt_db('access-1'),
            'refresh_token' => token_crypto_encrypt_db('refresh-1'),
        ]);
        $this->assertSame('access-1', $row['access_token']);
        $this->assertSame('refresh-1', $row['refresh_token']);
    }
}
