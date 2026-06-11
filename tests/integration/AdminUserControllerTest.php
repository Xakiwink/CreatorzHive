<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class AdminUserControllerTest extends IntegrationTestCase
{
    public function testAdminCanListUsers(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $login = $this->dispatchRoute('POST', 'login', [
            'email' => 'admin@creatorzhive.com',
            'password' => 'Admin@1234',
        ]);
        $this->assertSame(200, $login->httpStatus);

        $res = $this->dispatchRoute('GET', 'admin_users', []);
        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertIsArray($res->payload['data']['users'] ?? null);
    }

    public function testNonAdminCannotListUsers(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $login = $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
        $this->assertSame(200, $login->httpStatus);

        $res = $this->dispatchRoute('GET', 'admin_users', []);
        $this->assertSame(403, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }

    public function testAdminCannotAccessContentRouteApi(): void
    {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();

        $login = $this->dispatchRoute('POST', 'login', [
            'email' => 'admin@creatorzhive.com',
            'password' => 'Admin@1234',
        ]);
        $this->assertSame(200, $login->httpStatus);

        $res = $this->dispatchRoute('GET', 'posts', []);
        $this->assertSame(403, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
        $this->assertStringContainsString('not allowed', strtolower((string) ($res->payload['message'] ?? '')));
    }
}
