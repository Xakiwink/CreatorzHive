<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class InvoiceControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    public function testInvoicesListAsAuthenticatedUser(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $res = $this->dispatchRoute('GET', 'invoices_data', []);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $this->assertIsArray($res->payload['data']['invoices'] ?? null);
    }

    public function testCreateStandaloneInvoice(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $suffix = bin2hex(random_bytes(3));
        $res = $this->dispatchRoute('POST', 'create_invoice', [
            'recipient_name' => 'Client ' . $suffix,
            'recipient_email' => 'client' . $suffix . '@example.com',
            'total' => '2500',
            'currency' => 'TZS',
            'status' => 'draft',
        ]);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $id = (int) ($res->payload['data']['id'] ?? 0);
        $this->assertGreaterThan(0, $id);

        db_delete('invoices', 'id = :id', ['id' => $id]);
    }
}
