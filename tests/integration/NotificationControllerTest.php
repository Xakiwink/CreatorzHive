<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class NotificationControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testNotificationsDataReturnsList(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'notifications_data', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertIsArray($res->payload['data']['notifications'] ?? null);
        $this->assertGreaterThanOrEqual(0, (int) ($res->payload['data']['unread_count'] ?? -1));
    }

    public function testUnreadCountEndpoint(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'notifications_count', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertArrayHasKey('unread_count', $res->payload['data'] ?? []);
    }

    public function testMarkReadAndDeleteNotification(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $list = $this->dispatchRoute('GET', 'notifications_data', ['tab' => 'unread']);
        $items = $list->payload['data']['notifications'] ?? [];
        if ($items === []) {
            $this->markTestSkipped('No unread notifications in seed data.');

            return;
        }

        $id = (int) ($items[0]['id'] ?? 0);
        $this->assertGreaterThan(0, $id);

        $read = $this->dispatchRoute('POST', 'mark_read', ['id' => $id]);
        $this->assertSame(200, $read->httpStatus);
        $this->assertTrue($read->payload['success'] ?? false);

        $del = $this->dispatchRoute('POST', 'delete_notification', ['id' => $id]);
        $this->assertSame(200, $del->httpStatus);
        $this->assertTrue($del->payload['success'] ?? false);
    }

    public function testMarkAllReadRequiresAuth(): void
    {
        $res = $this->dispatchRoute('POST', 'mark_all_read', []);

        $this->assertSame(401, $res->httpStatus);
        $this->assertFalse($res->payload['success'] ?? true);
    }
}
