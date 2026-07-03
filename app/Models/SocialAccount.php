<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class SocialAccount
{
    public static function forUser(int $userId): array
    {
        return DB::fetchAll(
            'SELECT * FROM social_accounts WHERE user_id = :uid ORDER BY platform ASC',
            ['uid' => $userId]
        );
    }

    public static function find(int $userId, string $platform): ?array
    {
        return DB::fetch(
            'SELECT * FROM social_accounts WHERE user_id = :uid AND platform = :p LIMIT 1',
            ['uid' => $userId, 'p' => $platform]
        );
    }

    public static function upsert(int $userId, string $platform, array $data): void
    {
        $existing = self::find($userId, $platform);
        $data['user_id']  = $userId;
        $data['platform'] = $platform;

        if ($existing !== null) {
            DB::update('social_accounts', $data, 'id = :id', ['id' => $existing['id']]);
        } else {
            DB::insert('social_accounts', $data);
        }
    }
}
