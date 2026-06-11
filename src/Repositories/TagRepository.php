<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class TagRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getByUser(int $userId)
    {
        return $this->db->fetchAll(
                'SELECT * FROM tags WHERE user_id = :uid ORDER BY name ASC',
                ['uid' => $userId]
            );
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetchOne('SELECT * FROM tags WHERE id = :id LIMIT 1', ['id' => $id]);

        return $row === null ? null : $row;
    }

    public function findByName(int $userId, string $name)
    {
        $row = $this->db->fetchOne(
                'SELECT * FROM tags WHERE user_id = :uid AND name = :name LIMIT 1',
                ['uid' => $userId, 'name' => $name]
            );
        
            return $row === null ? null : $row;
    }

    public function create(int $userId, string $name, string $color)
    {
        $id = $this->db->insert('tags', [
                'user_id' => $userId,
                'name' => $name,
                'color' => $color,
            ]);
        
            return (int) $id;
    }

    public function delete(int $id, int $userId)
    {
        return $this->db->delete('tags', 'id = :id AND user_id = :uid', ['id' => $id, 'uid' => $userId]) > 0;
    }

}
