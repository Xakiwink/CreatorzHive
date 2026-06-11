<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class UserSessionRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function sessionTouch(int $userId)
    {
        $sid = session_id();
            if ($sid === '') {
                return;
            }
        
            $ua = request_user_agent();
            if (strlen($ua) > 65000) {
                $ua = substr($ua, 0, 65000);
            }
        
            $this->db->query(
                'INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_active)
                    VALUES (:id, :uid, :ip, :ua, :payload, NOW())
                    ON DUPLICATE KEY UPDATE
                        last_active = NOW(),
                        ip_address = VALUES(ip_address),
                        user_agent = VALUES(user_agent)',
                [
                    'id' => $sid,
                    'uid' => $userId,
                    'ip' => request_ip(),
                    'ua' => $ua,
                    'payload' => '{}',
                ]
            );
    }

    public function sessionDestroyById(string $sessionId)
    {
        if ($sessionId === '') {
                return;
            }
            $this->db->query('DELETE FROM sessions WHERE id = :id', ['id' => $sessionId]);
    }

    public function sessionListForUser(int $userId)
    {
        return $this->db->fetchAll(
                'SELECT id, ip_address, user_agent, last_active, created_at
                    FROM sessions WHERE user_id = :uid ORDER BY last_active DESC',
                ['uid' => $userId]
            );
    }

    public function sessionRevoke(string $sessionId, int $userId)
    {
        $this->db->query(
                'DELETE FROM sessions WHERE id = :id AND user_id = :uid',
                ['id' => $sessionId, 'uid' => $userId]
            );
        
            return true;
    }

    public function sessionRevokeOthers(int $userId, string $currentSessionId)
    {
        $this->db->query(
                'DELETE FROM sessions WHERE user_id = :uid AND id != :cid',
                ['uid' => $userId, 'cid' => $currentSessionId]
            );
    }

}
