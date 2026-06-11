<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PlatformApiSecretsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_SECRET'] = 'test-secret-key-for-phpunit';
        $GLOBALS['_cz_platform_secrets_test_path'] = sys_get_temp_dir()
            . '/cz-test-secrets-' . uniqid('', true) . '.json';
    }

    protected function tearDown(): void
    {
        $path = $GLOBALS['_cz_platform_secrets_test_path'] ?? null;
        if (is_string($path) && is_file($path)) {
            @unlink($path);
        }
        unset($GLOBALS['_cz_platform_secrets_test_path']);
        parent::tearDown();
    }

    public function testEncryptRoundTrip(): void
    {
        $plain = 'meta-token-abc123';
        $enc = platform_api_secrets_encrypt($plain);
        $this->assertNotSame('', $enc);
        $this->assertNotSame($plain, $enc);
        $this->assertSame($plain, platform_api_secrets_decrypt($enc));
    }

    public function testUiValueTakesPrecedenceOverEnv(): void
    {
        platform_api_secrets_save_store([
            'v' => 1,
            'fields' => ['instagram_access_token' => platform_api_secrets_encrypt('from-ui')],
        ]);

        $_ENV['INSTAGRAM_ACCESS_TOKEN'] = 'from-env';
        putenv('INSTAGRAM_ACCESS_TOKEN=from-env');

        $this->assertSame('from-ui', platform_api_secrets_resolve('instagram_access_token'));
        $this->assertSame('ui', platform_api_secrets_source_for_field('instagram_access_token'));
    }

    public function testMaskShowsLastFour(): void
    {
        $this->assertSame('••••••••1234', platform_api_secrets_mask('abcdefghij1234'));
    }
}
