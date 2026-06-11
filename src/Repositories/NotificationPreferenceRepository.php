<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class NotificationPreferenceRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function preferenceGetByUserId(int $userId)
    {
        return $this->db->fetchOne(
                'SELECT * FROM notification_preferences WHERE user_id = :uid LIMIT 1',
                ['uid' => $userId]
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultPreferences(int $userId): array
    {
        return [
            'user_id' => $userId,
            'email_post_published' => 1,
            'email_post_failed' => 1,
            'email_deal_updated' => 1,
            'email_invoice_paid' => 1,
            'email_weekly_summary' => 1,
            'push_post_published' => 1,
            'push_deal_updated' => 1,
        ];
    }

    public function preferenceUpsert(int $userId, array $data)
    {
        $existing = $this->preferenceGetByUserId($userId);
            $defaults = [
                'email_post_published' => 1,
                'email_post_failed' => 1,
                'email_deal_updated' => 1,
                'email_invoice_paid' => 1,
                'email_weekly_summary' => 1,
                'push_post_published' => 1,
                'push_deal_updated' => 1,
            ];
            if ($existing === null) {
                $row = array_merge(['user_id' => $userId], $defaults);
                foreach ($data as $k => $v) {
                    if (array_key_exists($k, $defaults)) {
                        $row[$k] = (int) (bool) $v;
                    }
                }
                $this->db->insert('notification_preferences', $row);
        
                return;
            }
        
            $update = [];
            foreach (array_keys($defaults) as $k) {
                if (array_key_exists($k, $data)) {
                    $update[$k] = (int) (bool) $data[$k];
                }
            }
            if ($update !== []) {
                $this->db->update('notification_preferences', $update, 'user_id = :uid', ['uid' => $userId]);
            }
    }

}
