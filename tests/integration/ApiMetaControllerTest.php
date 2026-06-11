<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class ApiMetaControllerTest extends IntegrationTestCase
{
    public function testPingReturnsPongWithoutAuth(): void
    {
        $res = $this->dispatchRoute('GET', 'ping', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertSame('pong', $res->payload['message'] ?? '');
        $this->assertTrue($res->payload['data']['ok'] ?? false);
        $this->assertNotEmpty($res->payload['data']['app'] ?? '');
    }

    public function testApiMeRequiresAuth(): void
    {
        $res = $this->dispatchRoute('GET', 'api_me', []);

        $this->assertSame(401, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testApiMeReturnsSessionPayloadWhenAuthenticated(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);

        $res = $this->dispatchRoute('GET', 'api_me', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertSame('david@creatorzhive.com', $res->payload['data']['user']['email'] ?? '');
        $this->assertNotEmpty($res->payload['data']['csrf_token'] ?? '');
    }

    public function testApiCatalogExcludesAdminRoutesForCreator(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);

        $res = $this->dispatchRoute('GET', 'api_catalog', []);

        $this->assertSame(200, $res->httpStatus);
        $routes = $res->payload['data']['routes'] ?? [];
        $names = array_map(static function (array $r): string {
            return (string) ($r['route'] ?? '');
        }, $routes);

        $this->assertContains('posts', $names);
        $this->assertNotContains('admin_users', $names);
    }

    public function testApiCatalogIncludesAdminRoutesForAdmin(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'admin@creatorzhive.com',
            'password' => 'Admin@1234',
        ]);

        $res = $this->dispatchRoute('GET', 'api_catalog', []);

        $this->assertSame(200, $res->httpStatus);
        $routes = $res->payload['data']['routes'] ?? [];
        $names = array_map(static function (array $r): string {
            return (string) ($r['route'] ?? '');
        }, $routes);

        $this->assertContains('admin_users', $names);
    }
}
