<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\DealRepository;
use CreatorzHive\Repositories\InvoiceRepository;
use CreatorzHive\Services\NotificationService;
use function request_all;
use function request_get;
use function request_post;
use function session_get_user;
use function validator_validate;

final class InvoiceController extends AbstractController
{
    /** @var InvoiceRepository */
    private $invoices;

    /** @var DealRepository */
    private $deals;

    /** @var NotificationService */
    private $notifications;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        InvoiceRepository $invoices,
        DealRepository $deals,
        NotificationService $notifications
    ) {
        parent::__construct($views, $json, $db);
        $this->invoices = $invoices;
        $this->deals = $deals;
        $this->notifications = $notifications;
    }

    public function index(): void
    {
        $this->views->render('monetization/invoices');
    }

    public function list(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $filters = [
            'status' => (string) request_get('status', ''),
            'page' => (int) request_get('page', 1),
            'per_page' => (int) request_get('per_page', 20),
        ];

        $result = $this->invoices->getByUser((int) $user['id'], $filters);
        $paidSum = $this->invoices->sumPaidTotal((int) $user['id']);

        $this->json->success([
            'invoices' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'paid_total' => $paidSum,
            'currency' => 'TZS',
        ], 'Invoices loaded');
    }

    public function show(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $id = (int) request_get('id', 0);
        if ($id < 1) {
            $this->json->error('Invalid invoice', 422);

            return;
        }

        $inv = $this->invoices->findForUser($id, (int) $user['id']);
        if ($inv === null) {
            $this->json->error('Invoice not found', 404);

            return;
        }

        $this->json->success($inv, 'Invoice loaded');
    }

    public function store(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $data = request_all();
        $userId = (int) $user['id'];
        $dealId = isset($data['deal_id']) ? (int) $data['deal_id'] : 0;

        if ($dealId > 0) {
            $deal = $this->deals->findForUser($dealId, $userId);
            if ($deal === null) {
                $this->json->error('Deal not found', 404);

                return;
            }
            $data['user_id'] = $userId;
            $data['deal_id'] = $dealId;
            $data['recipient_name'] = (string) ($deal['brand_name'] ?? '');
            $data['recipient_email'] = (string) ($deal['brand_email'] ?? '');
            if ($data['recipient_email'] === '') {
                $data['recipient_email'] = (string) ($user['email'] ?? 'creator@example.com');
            }
            $data['currency'] = $deal['currency'] ?? 'TZS';
            $data['total'] = (float) ($deal['amount'] ?? 0);
            $data['subtotal'] = (float) ($deal['amount'] ?? 0);
            $data['tax_rate'] = 0;
            $data['tax_amount'] = 0;
            $data['line_items'] = [
                ['description' => (string) ($deal['title'] ?? 'Campaign'), 'qty' => 1, 'unit_price' => (float) ($deal['amount'] ?? 0)],
            ];
            $data['status'] = $data['status'] ?? 'draft';
            $data['due_date'] = $data['due_date'] ?? $deal['deadline_at'];
        } else {
            $v = validator_validate($data, [
                'recipient_name' => ['required'],
                'recipient_email' => ['required', 'email'],
                'total' => ['required', 'numeric'],
                'currency' => ['in:TZS,USD,EUR'],
                'status' => ['in:draft,sent,paid,overdue,cancelled'],
            ]);
            if (!$v['valid']) {
                $this->json->error('Validation failed', 422, $v['errors']);

                return;
            }
            $data['user_id'] = $userId;
            $data['deal_id'] = null;
            $data['subtotal'] = (float) ($data['subtotal'] ?? $data['total']);
            $data['tax_rate'] = (float) ($data['tax_rate'] ?? 0);
            $data['tax_amount'] = (float) ($data['tax_amount'] ?? 0);
            if (!isset($data['line_items'])) {
                $data['line_items'] = [
                    ['description' => 'Services', 'qty' => 1, 'unit_price' => (float) $data['total']],
                ];
            }
        }

        $id = $this->invoices->create($data);
        $this->json->success(['id' => $id], 'Invoice created');
    }

    public function update(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $data = request_all();
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            $this->json->error('Invalid invoice', 422);

            return;
        }

        $existing = $this->invoices->findForUser($id, (int) $user['id']);
        if ($existing === null) {
            $this->json->error('Invoice not found', 404);

            return;
        }

        $v = validator_validate($data, [
            'recipient_name' => [],
            'recipient_email' => ['email'],
            'status' => ['in:draft,sent,paid,overdue,cancelled'],
            'total' => ['numeric'],
        ]);
        if (!$v['valid']) {
            $this->json->error('Validation failed', 422, $v['errors']);

            return;
        }

        $update = [];
        foreach (['recipient_name', 'recipient_email', 'due_date', 'status', 'notes', 'tax_rate', 'tax_amount', 'subtotal', 'total'] as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = $data[$k];
            }
        }
        if (array_key_exists('line_items', $data) && is_array($data['line_items'])) {
            $update['line_items'] = $data['line_items'];
        }
        if ($update !== []) {
            $this->invoices->update($id, $update);
        }

        $this->json->success(['id' => $id], 'Invoice updated');
    }

    public function markPaid(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);

            return;
        }

        $id = (int) request_post('id', 0);
        if ($id < 1) {
            $this->json->error('Invalid invoice', 422);

            return;
        }

        $existing = $this->invoices->findForUser($id, (int) $user['id']);
        if ($existing === null) {
            $this->json->error('Invoice not found', 404);

            return;
        }

        $this->invoices->markPaid($id);

        $invNo = (string) ($existing['invoice_number'] ?? ('#' . $id));
        $amtRaw = $existing['total'] ?? $existing['amount'] ?? '';
        $amount = $amtRaw !== '' && $amtRaw !== null ? (string) $amtRaw : '—';

        $this->notifications->invoicePaid((int) $user['id'], $invNo, $amount);

        $this->json->success(['id' => $id], 'Invoice marked paid');
    }
}
