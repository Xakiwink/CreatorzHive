<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class TagControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testListAndCreateTag(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $list = $this->dispatchRoute('GET', 'tags', []);
        $this->assertSame(200, $list->httpStatus);
        $this->assertTrue($list->payload['success'] ?? false);

        $suffix = bin2hex(random_bytes(3));
        $name = 'tag-' . $suffix;
        $create = $this->dispatchRoute('POST', 'create_tag', [
            'name' => $name,
            'color' => '#AABBCC',
        ]);

        $this->assertSame(200, $create->httpStatus);
        $this->assertTrue($create->payload['success'] ?? false);
        $id = (int) ($create->payload['data']['id'] ?? 0);
        $this->assertGreaterThan(0, $id);

        db_delete('tags', 'id = :id', ['id' => $id]);
    }
}
