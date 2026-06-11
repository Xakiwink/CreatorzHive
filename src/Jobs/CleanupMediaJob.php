<?php

declare(strict_types=1);

namespace CreatorzHive\Jobs;

use CreatorzHive\Core\Database\Connection;

final class CleanupMediaJob implements JobHandlerInterface
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function handle(array $payload): void
    {
        unset($payload);

        $orphans = $this->db->fetchAll(
            'SELECT id, user_id, file_path, thumbnail_url FROM media_files mf
            WHERE mf.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND NOT EXISTS (SELECT 1 FROM post_media pm WHERE pm.media_id = mf.id)
            AND NOT EXISTS (
                SELECT 1 FROM posts p WHERE p.cover_media_id = mf.id AND p.is_deleted = 0
            )'
        );

        $mediaDeleted = 0;
        foreach ($orphans as $row) {
            $id = (int) ($row['id'] ?? 0);
            $userId = (int) ($row['user_id'] ?? 0);
            if ($id < 1 || $userId < 1) {
                continue;
            }
            $abs = public_path((string) $row['file_path']);
            if (is_file($abs)) {
                @unlink($abs);
            }
            $thumb = (string) ($row['thumbnail_url'] ?? '');
            if ($thumb !== '' && strpos($thumb, 'uploads/') === 0) {
                $tp = public_path($thumb);
                if (is_file($tp)) {
                    @unlink($tp);
                }
            }
            $this->db->delete('media_files', 'id = :id AND user_id = :uid', ['id' => $id, 'uid' => $userId]);
            $mediaDeleted++;
        }

        $sessionsDeleted = $this->db->query(
            'DELETE FROM sessions WHERE last_active < DATE_SUB(NOW(), INTERVAL 30 DAY)'
        )->rowCount();

        $prDeleted = $this->db->query(
            'DELETE FROM password_resets WHERE used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        )->rowCount();

        $evDeleted = $this->db->query(
            'DELETE FROM email_verifications WHERE used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 7 DAY)'
        )->rowCount();

        job_runner_log_line(
            'INFO',
            sprintf(
                'CleanupMediaJob: media=%d, sessions=%d, password_resets=%d, email_verifications=%d',
                $mediaDeleted,
                $sessionsDeleted,
                $prDeleted,
                $evDeleted
            )
        );
    }
}
