<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class UserPreferencesRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function preferencesGetByUserId(int $userId)
    {
        return $this->db->fetchOne(
                'SELECT * FROM user_preferences WHERE user_id = :uid LIMIT 1',
                ['uid' => $userId]
            );
    }

    public function preferencesUpsert(int $userId, array $data)
    {
        $existing = $this->preferencesGetByUserId($userId);
            if ($existing === null) {
                $data['user_id'] = $userId;
                $this->db->insert('user_preferences', array_merge([
                    'theme' => 'system',
                    'language' => 'en',
                    'default_currency' => 'TZS',
                    'date_format' => 'Y-m-d',
                    'time_format' => '24h',
                    'week_starts_on' => 1,
                    'sidebar_collapsed' => 0,
                ], $data));
        
                return;
            }
        
            $allowed = ['theme', 'language', 'default_currency', 'date_format', 'time_format', 'week_starts_on', 'sidebar_collapsed'];
            $update = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $data)) {
                    $update[$k] = $data[$k];
                }
            }
            if ($update !== []) {
                $this->db->update('user_preferences', $update, 'user_id = :uid', ['uid' => $userId]);
            }
    }

}
