<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class AuditLogRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function logCreate(
    ?int $userId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    array $oldValues = [],
    array $newValues = []
)
    {
        $this->db->insert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_values' => $oldValues === [] ? null : json_encode($oldValues),
                'new_values' => $newValues === [] ? null : json_encode($newValues),
                'ip_address' => request_ip(),
                'user_agent' => substr(request_user_agent(), 0, 500),
            ]);
    }

    public function logListRecent(int $limit = 100)
    {
        $params = $this->db->bindLimit([], $limit, 500);
        
            return $this->db->fetchAll(
                'SELECT al.id, al.user_id, al.action, al.entity_type, al.entity_id, al.ip_address, al.user_agent, al.created_at, u.email AS actor_email
                 FROM audit_logs al
                 LEFT JOIN users u ON u.id = al.user_id
                 ORDER BY al.id DESC
                 LIMIT :limit',
                $params
            );
    }

}
