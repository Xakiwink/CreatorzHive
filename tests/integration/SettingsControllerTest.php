<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class SettingsControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testProfileDataAsAuthenticatedUser(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'profile_data', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertSame('david@creatorzhive.com', $res->payload['data']['user']['email'] ?? '');
    }

    public function testIntegrationsDataLoadsAccounts(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'integrations_data', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertIsArray($res->payload['data']['accounts'] ?? null);
    }

    public function testNotificationPrefsLoadsDefaults(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'notification_prefs', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertArrayHasKey('email_post_published', $res->payload['data'] ?? []);
    }
}
