<?php

declare(strict_types=1);

namespace CreatorzHive\Support;

use CreatorzHive\Repositories\AuditLogRepository;
use CreatorzHive\Repositories\DealRepository;
use CreatorzHive\Services\NotificationService;

final class DealWorkflowHelper
{
    /** @var AuditLogRepository */
    private $audit;

    /** @var NotificationService */
    private $notifications;

    /** @var DealRepository */
    private $deals;

    public function __construct(
        AuditLogRepository $audit,
        NotificationService $notifications,
        DealRepository $deals
    ) {
        $this->audit = $audit;
        $this->notifications = $notifications;
        $this->deals = $deals;
    }

    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function logAudit(
        int $userId,
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $this->audit->logCreate(
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ?? [],
            $newValues ?? []
        );
    }

    public function notifyDealCompleted(int $userId, int $dealId, string $brandName): void
    {
        $deal = $this->deals->findForUser($dealId, $userId);
        $title = $deal !== null
            ? (string) ($deal['title'] ?? $brandName)
            : $brandName;

        $this->notifications->dealCompleted($userId, $title, $dealId);
    }
}
