<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class NotificationRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getByUser(int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0, ?string $category = null)
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = :uid';
            $params = ['uid' => $userId];
        
            if ($unreadOnly) {
                $sql .= ' AND is_read = 0';
            }
        
            if ($category === 'posts') {
                $sql .= ' AND type IN (\'post_published\', \'post_failed\')';
            } elseif ($category === 'deals') {
                $sql .= ' AND type IN (\'deal_updated\', \'deal_completed\')';
            } elseif ($category === 'invoices') {
                $sql .= ' AND type IN (\'invoice_paid\')';
            }
        
            $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
            $params = $this->db->bindLimitOffset($params, $limit, $offset, 100);
        
            return $this->db->fetchAll($sql, $params);
    }

    public function countForUser(int $userId, bool $unreadOnly = false, ?string $category = null)
    {
        $sql = 'SELECT COUNT(*) AS c FROM notifications WHERE user_id = :uid';
            $params = ['uid' => $userId];
            if ($unreadOnly) {
                $sql .= ' AND is_read = 0';
            }
            if ($category === 'posts') {
                $sql .= ' AND type IN (\'post_published\', \'post_failed\')';
            } elseif ($category === 'deals') {
                $sql .= ' AND type IN (\'deal_updated\', \'deal_completed\')';
            } elseif ($category === 'invoices') {
                $sql .= ' AND type IN (\'invoice_paid\')';
            }
            $row = $this->db->fetchOne($sql, $params);
        
            return (int) ($row['c'] ?? 0);
    }

    public function countUnread(int $userId)
    {
        $row = $this->db->fetchOne(
                'SELECT COUNT(*) AS c FROM notifications WHERE user_id = :uid AND is_read = 0',
                ['uid' => $userId]
            );
        
            return (int) ($row['c'] ?? 0);
    }

    public function markRead(int $id, int $userId)
    {
        $this->db->query(
                'UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, NOW()) WHERE id = :id AND user_id = :uid',
                ['id' => $id, 'uid' => $userId]
            );
        
            return true;
    }

    public function markAllRead(int $userId)
    {
        $this->db->query(
                'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :uid AND is_read = 0',
                ['uid' => $userId]
            );
        
            return true;
    }

    public function create(int $userId, string $type, string $title, string $body = '', string $actionUrl = '', ?string $icon = null)
    {
        $id = $this->db->insert('notifications', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body !== '' ? $body : null,
                'action_url' => $actionUrl !== '' ? $actionUrl : null,
                'icon' => $icon,
                'is_read' => 0,
            ]);
        
            return (int) $id;
    }

    public function delete(int $id, int $userId)
    {
        $this->db->query('DELETE FROM notifications WHERE id = :id AND user_id = :uid', ['id' => $id, 'uid' => $userId]);
        
            return true;
    }

    public function deleteRead(int $userId)
    {
        $this->db->query('DELETE FROM notifications WHERE user_id = :uid AND is_read = 1', ['uid' => $userId]);
        
            return true;
    }

}
