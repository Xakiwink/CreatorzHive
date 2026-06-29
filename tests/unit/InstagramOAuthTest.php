<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InstagramOAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['APP_URL']            = 'http://localhost/creatorzhive';
        $_ENV['INSTAGRAM_APP_ID']   = '';
        $_ENV['INSTAGRAM_APP_SECRET'] = '';
    }

    public function testRedirectUriUsesAppUrlAndRoute(): void
    {
        $uri = instagram_oauth_redirect_uri();
        $this->assertStringContainsString('instagram-callback', $uri);
        $this->assertStringContainsString('creatorzhive', $uri);
    }

    public function testIsConfiguredRequiresAppIdAndSecret(): void
    {
        $path = sys_get_temp_dir() . '/cz-ig-oauth-' . uniqid('', true) . '.json';
        $GLOBALS['_cz_platform_secrets_test_path'] = $path;
        $_ENV['APP_SECRET'] = 'test';

        $this->assertFalse(instagram_oauth_is_configured());

        platform_api_secrets_save_store([
            'v'      => 1,
            'fields' => [
                'instagram_app_id'     => platform_api_secrets_encrypt('app123'),
                'instagram_app_secret' => platform_api_secrets_encrypt('secret456'),
            ],
        ]);

        $this->assertTrue(instagram_oauth_is_configured());

        @unlink($path);
        unset($GLOBALS['_cz_platform_secrets_test_path']);
    }

    public function testAuthorizeUrlUsesBusinessLoginDialog(): void
    {
        $path = sys_get_temp_dir() . '/cz-ig-oauth-' . uniqid('', true) . '.json';
        $GLOBALS['_cz_platform_secrets_test_path'] = $path;
        $_ENV['APP_SECRET'] = 'test';

        platform_api_secrets_save_store([
            'v'      => 1,
            'fields' => ['instagram_app_id' => platform_api_secrets_encrypt('app123')],
        ]);

        $url = instagram_oauth_authorize_url('state-xyz');

        $this->assertStringContainsString('graph.facebook.com/v25.0/dialog/oauth', $url);
        $this->assertStringContainsString('client_id=app123', $url);
        $this->assertStringContainsString('instagram_business_basic', $url);
        $this->assertStringContainsString('state=state-xyz', $url);
        $this->assertStringContainsString('instagram-callback', $url);
        $this->assertStringNotContainsString('api.instagram.com', $url);

        @unlink($path);
        unset($GLOBALS['_cz_platform_secrets_test_path']);
    }

    public function testAuthorizeUrlContainsAllRequiredScopes(): void
    {
        $path = sys_get_temp_dir() . '/cz-ig-oauth-' . uniqid('', true) . '.json';
        $GLOBALS['_cz_platform_secrets_test_path'] = $path;
        $_ENV['APP_SECRET'] = 'test';

        platform_api_secrets_save_store([
            'v'      => 1,
            'fields' => ['instagram_app_id' => platform_api_secrets_encrypt('app123')],
        ]);

        $url = instagram_oauth_authorize_url('state-abc');

        $this->assertStringContainsString('instagram_business_basic', $url);
        $this->assertStringContainsString('instagram_business_content_publish', $url);
        $this->assertStringContainsString('instagram_business_manage_insights', $url);

        @unlink($path);
        unset($GLOBALS['_cz_platform_secrets_test_path']);
    }

    public function testAuthorizeUrlDoesNotContainBasicDisplayEndpoint(): void
    {
        $path = sys_get_temp_dir() . '/cz-ig-oauth-' . uniqid('', true) . '.json';
        $GLOBALS['_cz_platform_secrets_test_path'] = $path;
        $_ENV['APP_SECRET'] = 'test';

        platform_api_secrets_save_store([
            'v'      => 1,
            'fields' => ['instagram_app_id' => platform_api_secrets_encrypt('app123')],
        ]);

        $url = instagram_oauth_authorize_url('state-def');

        $this->assertStringNotContainsString('api.instagram.com/oauth/authorize', $url);

        @unlink($path);
        unset($GLOBALS['_cz_platform_secrets_test_path']);
    }
}
