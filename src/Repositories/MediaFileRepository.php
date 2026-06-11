<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class MediaFileRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function fileGetByUser(int $userId, ?string $type = null)
    {
        $sql = 'SELECT * FROM media_files WHERE user_id = :uid';
            $params = ['uid' => $userId];
        
            if ($type === 'image') {
                $sql .= ' AND mime_type LIKE :mime';
                $params['mime'] = 'image/%';
            } elseif ($type === 'video') {
                $sql .= ' AND mime_type LIKE :mime';
                $params['mime'] = 'video/%';
            }
        
            $sql .= ' ORDER BY created_at DESC';
        
            return $this->db->fetchAll($sql, $params);
    }

    public function fileFindById(int $id)
    {
        $row = $this->db->fetchOne('SELECT * FROM media_files WHERE id = :id LIMIT 1', ['id' => $id]);
        
            return $row === null ? null : $row;
    }

    public function fileFindByIdForUser(int $id, int $userId)
    {
        return $this->findByIdForUser($id, $userId);
    }

    public function findByIdForUser(int $id, int $userId)
    {
        $row = $this->db->fetchOne(
                'SELECT * FROM media_files WHERE id = :id AND user_id = :uid LIMIT 1',
                ['id' => $id, 'uid' => $userId]
            );

        return $row === null ? null : $row;
    }

    public function fileCreate(array $data)
    {
        $id = $this->db->insert('media_files', [
                'user_id' => $data['user_id'],
                'file_name' => $data['file_name'],
                'original_name' => $data['original_name'],
                'file_path' => $data['file_path'],
                'cdn_url' => $data['cdn_url'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'mime_type' => $data['mime_type'],
                'file_size' => $data['file_size'],
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'duration' => $data['duration'] ?? null,
                'alt_text' => $data['alt_text'] ?? null,
            ]);
        
            return (int) $id;
    }

    public function fileDelete(int $id, int $userId)
    {
        $row = $this->findByIdForUser($id, $userId);
            if ($row === null) {
                return false;
            }
        
            $abs = public_path($row['file_path']);
            if (is_file($abs)) {
                @unlink($abs);
            }
            $thumb = (string) ($row['thumbnail_url'] ?? '');
            if ($thumb !== '' && $thumb !== $row['file_path'] && strpos($thumb, 'uploads/') === 0) {
                $tp = public_path($thumb);
                if (is_file($tp)) {
                    @unlink($tp);
                }
            }
        
            $this->db->delete('media_files', 'id = :id AND user_id = :uid', ['id' => $id, 'uid' => $userId]);
        
            return true;
    }

}
