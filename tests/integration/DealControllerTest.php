<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class DealControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testDealsDataAsAuthenticatedUser(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'deals_data', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertIsArray($res->payload['data']['kanban'] ?? null);
        $this->assertIsArray($res->payload['data']['revenue_summary'] ?? null);
    }

    public function testCreateAndDeleteDeal(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $suffix = bin2hex(random_bytes(3));
        $res = $this->dispatchRoute('POST', 'create_deal', [
            'brand_name' => 'Test Brand ' . $suffix,
            'title' => 'Integration Deal ' . $suffix,
            'amount' => '1500',
            'currency' => 'TZS',
            'status' => 'lead',
            'deal_type' => 'sponsored_post',
        ]);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $id = (int) (($res->payload['data']['id'] ?? 0));
        $this->assertGreaterThan(0, $id);

        $del = $this->dispatchRoute('POST', 'delete_deal', ['deal_id' => $id]);
        $this->assertSame(200, $del->httpStatus);
        $this->assertTrue($del->payload['success'] ?? false);
    }
}
