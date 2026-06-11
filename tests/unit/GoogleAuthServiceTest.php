<?php

declare(strict_types=1);

namespace Tests\Unit;

use CreatorzHive\Services\GoogleAuthService;
use PHPUnit\Framework\TestCase;

final class GoogleAuthServiceTest extends TestCase
{
    /** @var array<string, string|false> */
    private $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_AUTH_REDIRECT_URI', 'APP_URL'] as $key) {
            $this->envBackup[$key] = getenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv($key . '=' . $value);
            }
        }
        parent::tearDown();
    }

    public function testIsConfiguredWhenClientCredentialsPresent(): void
    {
        putenv('GOOGLE_CLIENT_ID=test-client-id');
        putenv('GOOGLE_CLIENT_SECRET=test-client-secret');

        $service = new GoogleAuthService();
        $this->assertTrue($service->isConfigured());
    }

    public function testIsNotConfiguredWhenCredentialsMissing(): void
    {
        putenv('GOOGLE_CLIENT_ID');
        putenv('GOOGLE_CLIENT_SECRET');

        $service = new GoogleAuthService();
        $this->assertFalse($service->isConfigured());
    }

    public function testAuthorizeUrlContainsOAuthParams(): void
    {
        putenv('GOOGLE_CLIENT_ID=my-app-id');
        putenv('GOOGLE_CLIENT_SECRET=my-secret');
        putenv('APP_URL=http://example.test');
        putenv('GOOGLE_AUTH_REDIRECT_URI=http://example.test/callback');

        $service = new GoogleAuthService();
        $url = $service->authorizeUrl('state-token-123');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $this->assertStringContainsString('client_id=my-app-id', $url);
        $this->assertStringContainsString('state=state-token-123', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('scope=openid', $url);
    }

    public function testRedirectUriUsesOverrideWhenSet(): void
    {
        putenv('GOOGLE_CLIENT_ID=id');
        putenv('GOOGLE_CLIENT_SECRET=secret');
        putenv('GOOGLE_AUTH_REDIRECT_URI=https://app.example/oauth/google');

        $service = new GoogleAuthService();
        $this->assertSame('https://app.example/oauth/google', $service->redirectUri());
    }
}
