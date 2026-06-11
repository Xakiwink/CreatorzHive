<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class AnalyticsControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testAnalyticsDataReturnsPayload(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'analytics_data', ['period' => '30d']);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertArrayHasKey('summary', $res->payload['data'] ?? []);
        $this->assertArrayHasKey('sparklines', $res->payload['data'] ?? []);
        $this->assertArrayHasKey('posting_frequency', $res->payload['data'] ?? []);
    }
}
