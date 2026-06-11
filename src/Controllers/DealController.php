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
use CreatorzHive\Support\DealWorkflowHelper;
use function request_all;
use function request_get;
use function request_post;
use function session_get_user;
use function upload_url;
use function validator_validate;

final class DealController extends AbstractController
{
    /** @var DealRepository */
    private $deals;

    /** @var InvoiceRepository */
    private $invoices;

    /** @var DealWorkflowHelper */
    private $workflow;

    /** @var NotificationService */
    private $notifications;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        DealRepository $deals,
        InvoiceRepository $invoices,
        DealWorkflowHelper $workflow,
        NotificationService $notifications
    ) {
        parent::__construct($views, $json, $db);
        $this->deals = $deals;
        $this->invoices = $invoices;
        $this->workflow = $workflow;
        $this->notifications = $notifications;
    }

    public function index(): void
    {
        $this->views->render('monetization/deals');
    }

    public function data(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $userId = (int) $user['id'];
        $this->json->success([
            'kanban' => $this->deals->getKanban($userId),
            'revenue_summary' => $this->deals->getRevenueSummary($userId),
        ], 'Deals loaded');
    }

    public function show(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $id = (int) request_get('id', 0);
        if ($id < 1) {
            $this->json->error('Invalid deal', 422);
        }

        $userId = (int) $user['id'];
        $deal = $this->deals->findForUser($id, $userId);
        if ($deal === null) {
            $this->json->error('Deal not found', 404);
        }

        $logo = (string) ($deal['brand_logo_url'] ?? '');
        if ($logo !== '' && strpos($logo, 'http') !== 0) {
            $deal['brand_logo_url'] = upload_url(ltrim($logo, '/'));
        }

        $this->json->success([
            'deal' => $deal,
            'linked_posts' => $this->deals->getLinkedPosts($id, $userId),
            'invoices' => $this->invoices->getByDeal($id),
            'activity' => $this->deals->getActivityForDeal($id, $userId),
        ], 'Deal loaded');
    }

    public function store(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $data = request_all();
        $v = validator_validate($data, [
            'brand_name' => ['required'],
            'title' => ['required'],
            'amount' => ['required', 'numeric'],
            'currency' => ['in:TZS,USD,EUR'],
            'status' => ['in:lead,negotiation,contract,active,completed,cancelled'],
            'deal_type' => ['in:sponsored_post,affiliate,ambassador,gifted,other'],
            'brand_email' => ['email'],
            'deadline_at' => [],
        ]);
        if (!$v['valid']) {
            $this->json->error('Validation failed', 422, $v['errors']);
        }

        $userId = (int) $user['id'];
        $data['user_id'] = $userId;
        $id = $this->deals->create($data);
        $this->workflow->logAudit($userId, 'deal.created', 'deal', $id, null, ['id' => $id, 'title' => $data['title'] ?? '']);

        $this->json->success(['id' => $id], 'Deal created successfully');
    }

    public function update(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $data = request_all();
        $id = (int) ($data['id'] ?? 0);
        if ($id < 1) {
            $this->json->error('Invalid deal', 422);
        }

        $userId = (int) $user['id'];
        $existing = $this->deals->findForUser($id, $userId);
        if ($existing === null) {
            $this->json->error('Deal not found', 404);
        }

        $v = validator_validate($data, [
            'brand_name' => [],
            'title' => [],
            'amount' => ['numeric'],
            'currency' => ['in:TZS,USD,EUR'],
            'status' => ['in:lead,negotiation,contract,active,completed,cancelled'],
            'deal_type' => ['in:sponsored_post,affiliate,ambassador,gifted,other'],
            'brand_email' => ['email'],
        ]);
        if (!$v['valid']) {
            $this->json->error('Validation failed', 422, $v['errors']);
        }

        $update = [];
        $keys = [
            'brand_name', 'brand_email', 'brand_logo_url', 'title', 'description',
            'amount', 'currency', 'status', 'deal_type', 'deliverables', 'deadline_at', 'notes',
        ];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) {
                $update[$k] = $data[$k];
            }
        }
        if (isset($update['status']) && $update['status'] === 'completed') {
            $update['completed_at'] = $existing['completed_at'] ?? date('Y-m-d');
        }
        if (isset($update['status']) && $update['status'] === 'contract') {
            $update['contracted_at'] = $existing['contracted_at'] ?? date('Y-m-d');
        }

        if ($update !== []) {
            $this->deals->update($id, $update);
        }

        $fresh = $this->deals->findForUser($id, $userId);
        $this->workflow->logAudit($userId, 'deal.updated', 'deal', $id, $existing, $fresh ?? $update);

        if (isset($update['status']) && (string) $update['status'] === 'completed' && (string) $existing['status'] !== 'completed') {
            $this->notifications->dealCompleted(
                $userId,
                (string) ($fresh['title'] ?? $existing['title'] ?? ''),
                $id
            );
        } elseif (isset($update['status']) && (string) $update['status'] !== (string) $existing['status']) {
            $this->notifications->dealStatusChanged(
                $userId,
                (string) ($fresh['title'] ?? $existing['title'] ?? ''),
                (string) $update['status']
            );
        }

        $this->json->success(['id' => $id], 'Deal updated successfully');
    }

    public function updateStatus(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $dealId = (int) request_post('deal_id', 0);
        $newStatus = (string) request_post('new_status', '');
        if ($dealId < 1 || $newStatus === '') {
            $this->json->error('deal_id and new_status are required', 422);
        }

        $userId = (int) $user['id'];
        $existing = $this->deals->findForUser($dealId, $userId);
        if ($existing === null) {
            $this->json->error('Deal not found', 404);
        }

        $oldStatus = (string) $existing['status'];
        if ($oldStatus === $newStatus) {
            $this->json->success(['id' => $dealId, 'status' => $newStatus], 'No change');
        }

        $this->deals->updateStatus($dealId, $newStatus, $userId);
        $this->workflow->logAudit($userId, 'deal.status_changed', 'deal', $dealId, ['status' => $oldStatus], ['status' => $newStatus]);

        if ($newStatus === 'completed') {
            $this->workflow->notifyDealCompleted($userId, $dealId, (string) ($existing['brand_name'] ?? ''));
        }

        $this->json->success(['id' => $dealId, 'status' => $newStatus], 'Status updated');
    }

    public function destroy(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $dealId = (int) request_post('deal_id', 0);
        if ($dealId < 1) {
            $dealId = (int) request_post('id', 0);
        }
        if ($dealId < 1) {
            $this->json->error('Invalid deal', 422);
        }

        $userId = (int) $user['id'];
        $existing = $this->deals->findForUser($dealId, $userId);
        if ($existing === null) {
            $this->json->error('Deal not found', 404);
        }

        $this->deals->softDelete($dealId, $userId);
        $this->workflow->logAudit($userId, 'deal.deleted', 'deal', $dealId, $existing, null);

        $this->json->success(null, 'Deal deleted');
    }
}
