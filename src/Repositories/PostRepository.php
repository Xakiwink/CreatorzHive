<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Helpers\PlatformHelper;

final class PostRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getRecentByUser(int $userId, int $limit = 5)
    {
        $params = $this->db->bindLimit(['uid' => $userId], $limit, 50);
                $sql = 'SELECT p.*, m.thumbnail_url AS cover_thumb, m.cdn_url AS cover_url
                    FROM posts p
                    LEFT JOIN media_files m ON m.id = p.cover_media_id
                    WHERE p.user_id = :uid AND p.is_deleted = 0
                    ORDER BY p.updated_at DESC
                    LIMIT :limit';
        
                $rows = $this->db->fetchAll($sql, $params);
        
                return array_map([$this, 'normalizeRow'], $rows);
    }

    public function countByStatus(int $userId)
    {
        $rows = $this->db->fetchAll(
                    'SELECT status, COUNT(*) AS c FROM posts WHERE user_id = :uid AND is_deleted = 0 GROUP BY status',
                    ['uid' => $userId]
                );
        
                $out = ['draft' => 0, 'scheduled' => 0, 'published' => 0, 'failed' => 0];
                foreach ($rows as $row) {
                    $out[$row['status']] = (int) $row['c'];
                }
        
                return $out;
    }

    public function getUpcoming(int $userId, int $limit = 5)
    {
        $params = $this->db->bindLimit(['uid' => $userId], $limit, 50);
                $sql = 'SELECT * FROM v_upcoming_posts WHERE user_id = :uid ORDER BY scheduled_at ASC LIMIT :limit';
                $rows = $this->db->fetchAll($sql, $params);
        
                return array_map([$this, 'normalizeRow'], $rows);
    }

    public function findById(int $id)
    {
        $row = $this->db->fetchOne('SELECT * FROM posts WHERE id = :id AND is_deleted = 0 LIMIT 1', ['id' => $id]);
                if ($row === null) {
                    return null;
                }
        
                return $this->normalizeRow($row);
    }

    public function findByIdForUser(int $id, int $userId)
    {
        $row = $this->db->fetchOne(
                    'SELECT * FROM posts WHERE id = :id AND user_id = :uid AND is_deleted = 0 LIMIT 1',
                    ['id' => $id, 'uid' => $userId]
                );
                if ($row === null) {
                    return null;
                }
        
                return $this->normalizeRow($row);
    }

    public function create(array $data)
    {
        $platforms = $data['platforms'] ?? [];
                if (is_array($platforms)) {
                    $platforms = json_encode($platforms);
                }
        
                $insert = [
                    'user_id' => $data['user_id'],
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'caption' => $data['caption'] ?? null,
                    'platforms' => $platforms,
                    'status' => $data['status'],
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'published_at' => $data['published_at'] ?? null,
                ];
                if (!empty($data['cover_media_id'])) {
                    $insert['cover_media_id'] = (int) $data['cover_media_id'];
                }
        
                $id = $this->db->insert('posts', $insert);
        
                return (int) $id;
    }

    public function save(int $id, array $data)
    {
        if (isset($data['platforms']) && is_array($data['platforms'])) {
                    $data['platforms'] = json_encode($data['platforms']);
                }
        
                return $this->db->update('posts', $data, 'id = :id', ['id' => $id]) > 0;
    }

    public function softDelete(int $id, int $userId)
    {
        return $this->db->update(
                    'posts',
                    ['is_deleted' => 1],
                    'id = :id AND user_id = :uid',
                    ['id' => $id, 'uid' => $userId]
                ) > 0;
    }

    public function getCalendarPosts(int $userId, string $month, string $year)
    {
        $y = (int) $year;
                $m = (int) $month;
                if ($y < 1970 || $y > 2100 || $m < 1 || $m > 12) {
                    return [];
                }
                $ym = sprintf('%04d-%02d', $y, $m);
        
                $sql = 'SELECT p.*, m.thumbnail_url AS cover_thumb, m.cdn_url AS cover_url,
                    DATE_FORMAT(
                        CASE
                            WHEN p.status = \'scheduled\' AND p.scheduled_at IS NOT NULL THEN p.scheduled_at
                            WHEN p.status = \'published\' AND p.published_at IS NOT NULL THEN p.published_at
                            ELSE p.created_at
                        END,
                        \'%Y-%m-%d\'
                    ) AS calendar_date
                    FROM posts p
                    LEFT JOIN media_files m ON m.id = p.cover_media_id
                    WHERE p.user_id = :uid AND p.is_deleted = 0
                    AND DATE_FORMAT(
                        CASE
                            WHEN p.status = \'scheduled\' AND p.scheduled_at IS NOT NULL THEN p.scheduled_at
                            WHEN p.status = \'published\' AND p.published_at IS NOT NULL THEN p.published_at
                            ELSE p.created_at
                        END,
                        \'%Y-%m\'
                    ) = :ym
                    ORDER BY calendar_date ASC, p.title ASC';
        
                $rows = $this->db->fetchAll($sql, ['uid' => $userId, 'ym' => $ym]);
        
                return array_map([$this, 'normalizeRow'], $rows);
    }

    public function getAllByUser(int $userId, array $filters = [])
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
                $perPage = min(50, max(1, (int) ($filters['per_page'] ?? 10)));
                $offset = ($page - 1) * $perPage;
        
                $sort = (string) ($filters['sort'] ?? 'date');
                $dir = $this->db->sortDirection((string) ($filters['dir'] ?? 'desc'));
                $orderBy = 'COALESCE(p.scheduled_at, p.published_at, p.updated_at) ' . $dir;
                if ($sort === 'title') {
                    $orderBy = 'p.title ' . $dir;
                } elseif ($sort === 'status') {
                    $orderBy = 'p.status ' . $dir . ', p.updated_at DESC';
                }
        
                $where = ['p.user_id = :uid', 'p.is_deleted = 0'];
                $params = ['uid' => $userId];
        
                $allowedStatuses = ['draft', 'scheduled', 'published', 'failed'];
                $statusFilter = isset($filters['status']) ? (string) $filters['status'] : '';
                if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
                    $where[] = 'p.status = :st';
                    $params['st'] = $statusFilter;
                }
                $platformFilter = PlatformHelper::normalize(
                    isset($filters['platform']) ? (string) $filters['platform'] : null
                );
                if ($platformFilter !== null) {
                    $where[] = 'JSON_CONTAINS(p.platforms, :plat, \'$\')';
                    $params['plat'] = json_encode($platformFilter);
                }
                if (!empty($filters['date_from'])) {
                    $where[] = 'DATE(COALESCE(p.scheduled_at, p.published_at, p.created_at)) >= :df';
                    $params['df'] = (string) $filters['date_from'];
                }
                if (!empty($filters['date_to'])) {
                    $where[] = 'DATE(COALESCE(p.scheduled_at, p.published_at, p.created_at)) <= :dt';
                    $params['dt'] = (string) $filters['date_to'];
                }
                if (!empty($filters['search'])) {
                    $term = trim((string) $filters['search']);
                    if ($term !== '') {
                        // FULLTEXT idx_posts_search (title, content); short terms fall back to LIKE (ft_min_word_len often 4).
                        if (mb_strlen($term) < 4) {
                            $where[] = '(p.title LIKE :q OR p.content LIKE :q2)';
                            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                            $params['q'] = $q;
                            $params['q2'] = $q;
                        } else {
                            $where[] = 'MATCH(p.title, p.content) AGAINST(:ft IN NATURAL LANGUAGE MODE)';
                            $params['ft'] = $term;
                        }
                    }
                }
        
                $sqlWhere = implode(' AND ', $where);
        
                $countRow = $this->db->fetchOne(
                    'SELECT COUNT(*) AS c FROM posts p WHERE ' . $sqlWhere,
                    $params
                );
                $total = (int) ($countRow['c'] ?? 0);
        
                $sql = 'SELECT p.*, m.thumbnail_url AS cover_thumb, m.cdn_url AS cover_url
                    FROM posts p
                    LEFT JOIN media_files m ON m.id = p.cover_media_id
                    WHERE ' . $sqlWhere . '
                    ORDER BY ' . $orderBy . '
                    LIMIT :limit OFFSET :offset';
                $params = $this->db->bindLimitOffset($params, $perPage, $offset, 50);
        
                $rows = $this->db->fetchAll($sql, $params);
                $posts = array_map([$this, 'normalizeRow'], $rows);
                $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 0;
        
                $posts = $this->attachTagsToPosts($posts);
        
                return [
                    'posts' => $posts,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => $totalPages,
                ];
    }

    public function attachTagsToPosts(array $posts)
    {
        if ($posts === []) {
                    return [];
                }
                $ids = [];
                foreach ($posts as $p) {
                    if (!empty($p['id'])) {
                        $ids[] = (int) $p['id'];
                    }
                }
                $ids = array_values(array_unique(array_filter($ids)));
                if ($ids === []) {
                    return $posts;
                }
        
                $inClause = $this->db->inIntPlaceholders($ids, 'post_id');
                $rows = $this->db->fetchAll(
                    'SELECT pt.post_id, t.id AS tag_id, t.name, t.color FROM post_tags pt
                    INNER JOIN tags t ON t.id = pt.tag_id
                    WHERE pt.post_id IN (' . $inClause['sql'] . ')
                    ORDER BY t.name ASC',
                    $inClause['params']
                );
        
                $byPost = [];
                foreach ($rows as $r) {
                    $pid = (int) $r['post_id'];
                    if (!isset($byPost[$pid])) {
                        $byPost[$pid] = [];
                    }
                    $byPost[$pid][] = [
                        'id' => (int) $r['tag_id'],
                        'name' => $r['name'],
                        'color' => $r['color'],
                    ];
                }
        
                foreach ($posts as $i => $p) {
                    $pid = (int) ($p['id'] ?? 0);
                    $posts[$i]['tags'] = $byPost[$pid] ?? [];
                }
        
                return $posts;
    }

    public function getWithMedia(int $id)
    {
        $post = $this->findById($id);
                if ($post === null) {
                    return null;
                }
                $media = $this->db->fetchAll(
                    'SELECT m.*, pm.sort_order FROM media_files m
                    INNER JOIN post_media pm ON pm.media_id = m.id
                    WHERE pm.post_id = :pid
                    ORDER BY pm.sort_order ASC, m.id ASC',
                    ['pid' => $id]
                );
        
                return array_merge($post, ['media' => $media]);
    }

    public function getWithTags(int $id)
    {
        $post = $this->findById($id);
                if ($post === null) {
                    return null;
                }
                $tags = $this->db->fetchAll(
                    'SELECT t.* FROM tags t
                    INNER JOIN post_tags pt ON pt.tag_id = t.id
                    WHERE pt.post_id = :pid
                    ORDER BY t.name ASC',
                    ['pid' => $id]
                );
        
                return array_merge($post, ['tags' => $tags]);
    }

    public function getFullForUser(int $id, int $userId)
    {
        $post = $this->findByIdForUser($id, $userId);
                if ($post === null) {
                    return null;
                }
        
                $media = $this->db->fetchAll(
                    'SELECT m.*, pm.sort_order FROM media_files m
                    INNER JOIN post_media pm ON pm.media_id = m.id
                    WHERE pm.post_id = :pid
                    ORDER BY pm.sort_order ASC, m.id ASC',
                    ['pid' => $id]
                );
                $tags = $this->db->fetchAll(
                    'SELECT t.* FROM tags t
                    INNER JOIN post_tags pt ON pt.tag_id = t.id
                    WHERE pt.post_id = :pid
                    ORDER BY t.name ASC',
                    ['pid' => $id]
                );
        
                return array_merge($post, ['media' => $media, 'tags' => $tags]);
    }

    public function attachMedia(int $postId, int $mediaId, int $sortOrder = 0)
    {
        $exists = $this->db->fetchOne(
                    'SELECT id FROM post_media WHERE post_id = :p AND media_id = :m LIMIT 1',
                    ['p' => $postId, 'm' => $mediaId]
                );
                if ($exists !== null) {
                    $this->db->query(
                        'UPDATE post_media SET sort_order = :so WHERE post_id = :p AND media_id = :m',
                        ['so' => $sortOrder, 'p' => $postId, 'm' => $mediaId]
                    );
        
                    return;
                }
                $this->db->insert('post_media', [
                    'post_id' => $postId,
                    'media_id' => $mediaId,
                    'sort_order' => $sortOrder,
                ]);
    }

    public function detachMedia(int $postId, int $mediaId)
    {
        $this->db->delete('post_media', 'post_id = :p AND media_id = :m', ['p' => $postId, 'm' => $mediaId]);
    }

    public function detachAllMedia(int $postId)
    {
        $this->db->delete('post_media', 'post_id = :p', ['p' => $postId]);
    }

    public function syncMedia(int $postId, array $mediaIdsOrdered)
    {
        $this->detachAllMedia($postId);
                foreach (array_values($mediaIdsOrdered) as $i => $mid) {
                    $mid = (int) $mid;
                    if ($mid > 0) {
                        $this->attachMedia($postId, $mid, $i);
                    }
                }
    }

    public function attachTag(int $postId, int $tagId)
    {
        $exists = $this->db->fetchOne(
                    'SELECT 1 FROM post_tags WHERE post_id = :p AND tag_id = :t LIMIT 1',
                    ['p' => $postId, 't' => $tagId]
                );
                if ($exists !== null) {
                    return;
                }
                $this->db->query(
                    'INSERT INTO post_tags (post_id, tag_id) VALUES (:p, :t)',
                    ['p' => $postId, 't' => $tagId]
                );
    }

    public function detachAllTags(int $postId)
    {
        $this->db->delete('post_tags', 'post_id = :p', ['p' => $postId]);
    }

    public function syncTags(int $postId, array $tagIds)
    {
        $this->detachAllTags($postId);
                foreach ($tagIds as $tid) {
                    $tid = (int) $tid;
                    if ($tid > 0) {
                        $this->attachTag($postId, $tid);
                    }
                }
    }

    public function publish(int $id)
    {
        return $this->db->update(
                    'posts',
                    ['status' => 'published', 'published_at' => now()],
                    'id = :id AND is_deleted = 0',
                    ['id' => $id]
                ) > 0;
    }

    public function markFailed(int $id, string $reason)
    {
        unset($reason);
        
                return $this->db->update(
                    'posts',
                    ['status' => 'failed'],
                    'id = :id AND is_deleted = 0',
                    ['id' => $id]
                ) > 0;
    }

    public function duplicate(int $id, int $userId)
    {
        $orig = $this->findByIdForUser($id, $userId);
                if ($orig === null) {
                    return 0;
                }
                $title = $orig['title'];
                if (strpos($title, 'Copy of ') !== 0) {
                    $title = 'Copy of ' . $title;
                }
        
                $newId = $this->create([
                    'user_id' => $userId,
                    'title' => $title,
                    'content' => (string) $orig['content'],
                    'caption' => $orig['caption'] ?? null,
                    'platforms' => $orig['platforms'],
                    'status' => 'draft',
                    'scheduled_at' => null,
                    'published_at' => null,
                    'cover_media_id' => !empty($orig['cover_media_id']) ? (int) $orig['cover_media_id'] : null,
                ]);
        
                if ($newId < 1) {
                    return 0;
                }
        
                $mediaRows = $this->db->fetchAll(
                    'SELECT media_id, sort_order FROM post_media WHERE post_id = :pid ORDER BY sort_order ASC',
                    ['pid' => $id]
                );
                foreach ($mediaRows as $row) {
                    $this->attachMedia($newId, (int) $row['media_id'], (int) $row['sort_order']);
                }
        
                $tagRows = $this->db->fetchAll(
                    'SELECT tag_id FROM post_tags WHERE post_id = :pid',
                    ['pid' => $id]
                );
                foreach ($tagRows as $row) {
                    $this->attachTag($newId, (int) $row['tag_id']);
                }
        
                return $newId;
    }

    public function normalizeRow(array $row)
    {
        if (isset($row['platforms']) && is_string($row['platforms'])) {
                    $decoded = json_decode($row['platforms'], true);
                    $row['platforms'] = is_array($decoded) ? $decoded : [];
                }
        
                return $row;
    }

}
