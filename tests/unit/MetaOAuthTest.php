<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MetaOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_URL'] = 'http://localhost/creatorzhive';
        $_ENV['META_APP_ID'] = '';
        $_ENV['META_APP_SECRET'] = '';
    }

    public function testRedirectUriUsesAppUrlAndRoute(): void
    {
        $uri = meta_oauth_redirect_uri();
        $this->assertStringContainsString('oauth-callback', $uri);
        $this->assertStringContainsString('creatorzhive', $uri);
    }

    public function testIsConfiguredRequiresAppIdAndSecret(): void
    {
        $path = sys_get_temp_dir() . '/cz-meta-oauth-' . uniqid('', true) . '.json';
        $GLOBALS['_cz_platform_secrets_test_path'] = $path;
        $_ENV['APP_SECRET'] = 'test';

        $this->assertFalse(meta_oauth_is_configured());

        platform_api_secrets_save_store([
            'v' => 1,
            'fields' => [
                'meta_app_id' => platform_api_secrets_encrypt('123'),
                'meta_app_secret' => platform_api_secrets_encrypt('secret'),
            ],
        ]);

        $this->assertTrue(meta_oauth_is_configured());

        @unlink($path);
        unset($GLOBALS['_cz_platform_secrets_test_path']);
    }

    public function testAuthorizeUrlContainsClientAndScope(): void
    {
        $path = sys_get_temp_dir() . '/cz-meta-oauth-' . uniqid('', true) . '.json';
        $GLOBALS['_cz_platform_secrets_test_path'] = $path;
        $_ENV['APP_SECRET'] = 'test';

        platform_api_secrets_save_store([
            'v' => 1,
            'fields' => ['meta_app_id' => platform_api_secrets_encrypt('app123')],
        ]);

        $url = meta_oauth_authorize_url('instagram', 'state-xyz');
        $this->assertStringContainsString('facebook.com', $url);
        $this->assertStringContainsString('client_id=app123', $url);
        $this->assertStringContainsString('instagram_basic', $url);
        $this->assertStringContainsString('state=state-xyz', $url);

        @unlink($path);
        unset($GLOBALS['_cz_platform_secrets_test_path']);
    }
}
