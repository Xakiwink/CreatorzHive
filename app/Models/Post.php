<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Post
{
    public static function forUser(int $userId, int $limit = 20): array
    {
        $rows = DB::fetchAll(
            'SELECT * FROM posts WHERE user_id = :uid AND is_deleted = 0 ORDER BY created_at DESC LIMIT :lim',
            ['uid' => $userId, 'lim' => $limit]
        );
        return array_map([self::class, 'normalize'], $rows);
    }

    public static function counts(int $userId): array
    {
        $rows = DB::fetchAll(
            'SELECT status, COUNT(*) AS n FROM posts WHERE user_id = :uid AND is_deleted = 0 GROUP BY status',
            ['uid' => $userId]
        );
        $out = ['total' => 0, 'draft' => 0, 'scheduled' => 0, 'published' => 0, 'failed' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
            $out['total'] += (int) $r['n'];
        }
        return $out;
    }

    public static function create(int $userId, string $title, string $caption, array $platforms, ?string $scheduledAt): int
    {
        $status = ($scheduledAt !== null) ? 'scheduled' : 'draft';
        return DB::insert('posts', [
            'user_id'      => $userId,
            'title'        => $title,
            'caption'      => $caption,
            'platforms'    => json_encode($platforms),
            'status'       => $status,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public static function pendingDue(): array
    {
        $rows = DB::fetchAll(
            "SELECT * FROM posts WHERE status = 'scheduled' AND scheduled_at <= NOW() AND is_deleted = 0 LIMIT 10"
        );
        return array_map([self::class, 'normalize'], $rows);
    }

    public static function markPublished(int $id): void
    {
        DB::update('posts', ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    }

    public static function markFailed(int $id, string $reason): void
    {
        DB::update('posts', ['status' => 'failed'], 'id = :id', ['id' => $id]);
    }

    private static function normalize(array $row): array
    {
        if (isset($row['platforms']) && is_string($row['platforms'])) {
            $row['platforms'] = json_decode($row['platforms'], true) ?: [];
        }
        return $row;
    }
}
