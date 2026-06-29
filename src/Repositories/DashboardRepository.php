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
            'SELECT
                u.id AS user_id, u.name, u.username, u.avatar_url, u.role,
                COALESCE(a.total_posts, 0)         AS total_posts,
                COALESCE(a.published_posts, 0)     AS published_posts,
                COALESCE(a.total_followers, 0)     AS total_followers,
                COALESCE(a.avg_engagement_rate, 0) AS avg_engagement_rate,
                COALESCE(a.total_revenue, 0)       AS total_revenue,
                (SELECT COUNT(*) FROM deals d
                    WHERE d.user_id = u.id
                      AND d.status NOT IN (\'cancelled\',\'completed\')
                      AND d.is_deleted = 0)        AS active_deals,
                (SELECT COUNT(*) FROM posts p
                    WHERE p.user_id = u.id
                      AND p.status = \'scheduled\'
                      AND p.is_deleted = 0)        AS scheduled_posts,
                (SELECT COUNT(*) FROM notifications n
                    WHERE n.user_id = u.id
                      AND n.is_read = 0)           AS unread_notifications
            FROM users u
            LEFT JOIN analytics a ON a.user_id = u.id
            WHERE u.is_active = 1 AND u.id = :uid
            LIMIT 1',
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
