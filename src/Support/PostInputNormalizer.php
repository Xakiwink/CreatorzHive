<?php

declare(strict_types=1);

namespace CreatorzHive\Support;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\PostRepository;

final class PostInputNormalizer
{
    /** @var Connection */
    private $db;

    /** @var PostRepository */
    private $posts;

    public function __construct(Connection $db, PostRepository $posts)
    {
        $this->db = $db;
        $this->posts = $posts;
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    public function normalizeIdList($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : (preg_split('/\s*,\s*/', $raw) ?: []);
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param mixed $raw
     * @return list<string>
     */
    public function normalizePlatforms($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = preg_split('/\s*,\s*/', $raw) ?: [];
            }
        }
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $p) {
            $s = strtolower(trim((string) $p));
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param list<int> $tagIds
     */
    public function syncPostTags(int $postId, array $tagIds, int $userId): void
    {
        foreach ($tagIds as $tid) {
            $tag = $this->db->fetchOne(
                'SELECT id FROM tags WHERE id = :id AND user_id = :uid LIMIT 1',
                ['id' => $tid, 'uid' => $userId]
            );
            if ($tag === null) {
                http_json_error('Invalid tag', 422);
            }
        }
        $this->posts->syncTags($postId, $tagIds);
    }
}
