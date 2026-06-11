<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

/**
 * Dashboard read queries (SRP: dashboard persistence).
 */
final class DashboardRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return array<string, mixed>
     */
    public function findCreatorSummary(int $userId): array
    {
        $summary = $this->db->fetchOne(
            'SELECT * FROM v_creator_summary WHERE user_id = :uid LIMIT 1',
            ['uid' => $userId]
        );

        if ($summary !== null) {
            return $summary;
        }

        return [
            'user_id' => $userId,
            'total_posts' => 0,
            'published_posts' => 0,
            'scheduled_posts' => 0,
            'total_followers' => 0,
            'avg_engagement_rate' => 0,
            'total_revenue' => 0,
            'active_deals' => 0,
            'unread_notifications' => 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findActiveSocialAccounts(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT platform, username, is_active FROM social_accounts WHERE user_id = :uid AND is_active = 1',
            ['uid' => $userId]
        );
    }
}
