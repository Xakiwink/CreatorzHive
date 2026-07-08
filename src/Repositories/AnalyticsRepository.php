<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Helpers\PlatformHelper;

final class AnalyticsRepository
{
    private const TOP_POSTS_SORT_METRICS = [
        'top' => 'engagement_rate',
        'worst' => 'engagement_rate',
        'most_commented' => 'comments',
        'highest_reach' => 'reach',
    ];

    private const INSIGHTS_SUPPORTED_PLATFORMS = ['instagram', 'youtube'];

    /** @var Connection */
    private $db;

    /** @var PostRepository */
    private $posts;

    public function __construct(Connection $db, PostRepository $posts)
    {
        $this->db = $db;
        $this->posts = $posts;
    }

    public function sqlWithPlatformFilter(string $sql, array $params, ?string $platform, bool $allPlatformsWhenEmpty = true)
    {
        $platform = PlatformHelper::normalize($platform);
            if ($platform !== null) {
                $sql .= ' AND platform = :plat';
                $params['plat'] = $platform;
            } elseif ($allPlatformsWhenEmpty) {
                $sql .= ' AND platform IS NOT NULL AND platform != \'\'';
            }
        
            return [$sql, $params];
    }

    public function getByUser(int $userId)
    {
        return $this->db->fetchOne('SELECT * FROM analytics WHERE user_id = :uid LIMIT 1', ['uid' => $userId]);
    }

    public function upsert(int $userId, array $data)
    {
        $defaults = [
                    'total_posts' => 0,
                    'published_posts' => 0,
                    'draft_posts' => 0,
                    'scheduled_posts' => 0,
                    'failed_posts' => 0,
                    'total_followers' => 0,
                    'total_impressions' => 0,
                    'total_engagements' => 0,
                    'total_reach' => 0,
                    'avg_engagement_rate' => 0,
                    'total_revenue' => 0,
                ];
                $row = array_merge($defaults, $data);
                $row['user_id'] = $userId;
        
                $sql = 'INSERT INTO analytics (
                    user_id, total_posts, published_posts, draft_posts, scheduled_posts, failed_posts,
                    total_followers, total_impressions, total_engagements, total_reach, avg_engagement_rate, total_revenue
                ) VALUES (
                    :user_id, :total_posts, :published_posts, :draft_posts, :scheduled_posts, :failed_posts,
                    :total_followers, :total_impressions, :total_engagements, :total_reach, :avg_engagement_rate, :total_revenue
                ) ON DUPLICATE KEY UPDATE
                    total_posts = VALUES(total_posts),
                    published_posts = VALUES(published_posts),
                    draft_posts = VALUES(draft_posts),
                    scheduled_posts = VALUES(scheduled_posts),
                    failed_posts = VALUES(failed_posts),
                    total_followers = VALUES(total_followers),
                    total_impressions = VALUES(total_impressions),
                    total_engagements = VALUES(total_engagements),
                    total_reach = VALUES(total_reach),
                    avg_engagement_rate = VALUES(avg_engagement_rate),
                    total_revenue = VALUES(total_revenue)';
        
                $this->db->query($sql, $row);
    }

    public function recalculate(int $userId)
    {
        $breakdown = $this->posts->countByStatus($userId);
                $total = ($breakdown['draft'] ?? 0)
                    + ($breakdown['scheduled'] ?? 0)
                    + ($breakdown['published'] ?? 0)
                    + ($breakdown['failed'] ?? 0);
        
                $existing = $this->getByUser($userId);
                $preserve = [
                    'total_followers' => 0,
                    'total_impressions' => 0,
                    'total_engagements' => 0,
                    'total_reach' => 0,
                    'avg_engagement_rate' => 0,
                    'total_revenue' => 0,
                ];
                if ($existing !== null) {
                    $preserve = [
                        'total_followers' => (int) ($existing['total_followers'] ?? 0),
                        'total_impressions' => (int) ($existing['total_impressions'] ?? 0),
                        'total_engagements' => (int) ($existing['total_engagements'] ?? 0),
                        'total_reach' => (int) ($existing['total_reach'] ?? 0),
                        'avg_engagement_rate' => (float) ($existing['avg_engagement_rate'] ?? 0),
                        'total_revenue' => (float) ($existing['total_revenue'] ?? 0),
                    ];
                }
        
                $this->upsert($userId, array_merge($preserve, [
                    'total_posts' => $total,
                    'published_posts' => (int) ($breakdown['published'] ?? 0),
                    'draft_posts' => (int) ($breakdown['draft'] ?? 0),
                    'scheduled_posts' => (int) ($breakdown['scheduled'] ?? 0),
                    'failed_posts' => (int) ($breakdown['failed'] ?? 0),
                ]));
    }

    public function getSnapshots(int $userId, string $period, string $startDate, string $endDate, ?string $platform = null)
    {
        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
                    $period = 'daily';
                }
        
                $sql = 'SELECT * FROM analytics_snapshots
                    WHERE user_id = :uid AND period = :per
                    AND snapshot_date >= :start AND snapshot_date <= :end';
                $params = [
                    'uid' => $userId,
                    'per' => $period,
                    'start' => $startDate,
                    'end' => $endDate,
                ];
        
                [$sql, $params] = analytics_sql_with_platform_filter($sql, $params, $platform);
        
                $sql .= ' ORDER BY snapshot_date ASC, platform ASC';
        
                return $this->db->fetchAll($sql, $params);
    }

    public function sumFollowersOnDate(int $userId, string $date, ?string $platform)
    {
        $sql = 'SELECT COALESCE(SUM(followers), 0) AS s FROM analytics_snapshots
                    WHERE user_id = :uid AND period = \'daily\' AND snapshot_date = :dt';
                $params = ['uid' => $userId, 'dt' => $date];
                if ($platform !== null && $platform !== '') {
                    $sql .= ' AND platform = :plat';
                    $params['plat'] = $platform;
                } else {
                    $sql .= ' AND platform IS NOT NULL AND platform != \'\'';
                }
                $row = $this->db->fetchOne($sql, $params);
        
                return (int) ($row['s'] ?? 0);
    }

    public function sumMetricsInRange(int $userId, string $startDate, string $endDate, ?string $platform)
    {
        $sql = 'SELECT
                        COALESCE(SUM(impressions), 0) AS impressions,
                        COALESCE(SUM(reach), 0) AS reach,
                        COALESCE(SUM(likes + comments + shares), 0) AS engagements,
                        COALESCE(AVG(engagement_rate), 0) AS avg_engagement_rate
                    FROM analytics_snapshots
                    WHERE user_id = :uid AND period = \'daily\'
                    AND snapshot_date >= :start AND snapshot_date <= :end';
                $params = ['uid' => $userId, 'start' => $startDate, 'end' => $endDate];
                [$sql, $params] = analytics_sql_with_platform_filter($sql, $params, $platform);
                $row = $this->db->fetchOne($sql, $params);
        
                return [
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'reach' => (int) ($row['reach'] ?? 0),
                    'engagements' => (int) ($row['engagements'] ?? 0),
                    'avg_engagement_rate' => round((float) ($row['avg_engagement_rate'] ?? 0), 2),
                ];
    }

    public function hasSnapshotData(int $userId)
    {
        $row = $this->db->fetchOne(
                    'SELECT 1 AS x FROM analytics_snapshots WHERE user_id = :uid LIMIT 1',
                    ['uid' => $userId]
                );
        
                return $row !== null;
    }

    public function getGrowthTrend(int $userId, string $startDate, string $endDate, ?string $platform)
    {
        $sql = 'SELECT snapshot_date AS date, SUM(followers) AS followers
                    FROM analytics_snapshots
                    WHERE user_id = :uid AND period = \'daily\'
                    AND snapshot_date >= :start AND snapshot_date <= :end';
                $params = ['uid' => $userId, 'start' => $startDate, 'end' => $endDate];
                [$sql, $params] = analytics_sql_with_platform_filter($sql, $params, $platform);
                $sql .= ' GROUP BY snapshot_date ORDER BY snapshot_date ASC';
        
                return $this->db->fetchAll($sql, $params);
    }

    public function getDailyRollupTrend(int $userId, string $startDate, string $endDate, ?string $platform)
    {
        $sql = 'SELECT snapshot_date AS date,
                        COALESCE(SUM(impressions), 0) AS impressions,
                        COALESCE(SUM(reach), 0) AS reach,
                        COALESCE(SUM(likes + comments + shares), 0) AS engagements,
                        COALESCE(AVG(engagement_rate), 0) AS rate
                    FROM analytics_snapshots
                    WHERE user_id = :uid AND period = \'daily\'
                    AND snapshot_date >= :start AND snapshot_date <= :end';
                $params = ['uid' => $userId, 'start' => $startDate, 'end' => $endDate];
                [$sql, $params] = analytics_sql_with_platform_filter($sql, $params, $platform);
                $sql .= ' GROUP BY snapshot_date ORDER BY snapshot_date ASC';
        
                $rows = $this->db->fetchAll($sql, $params);
                foreach ($rows as &$r) {
                    $r['impressions'] = (int) ($r['impressions'] ?? 0);
                    $r['reach'] = (int) ($r['reach'] ?? 0);
                    $r['engagements'] = (int) ($r['engagements'] ?? 0);
                    $r['rate'] = round((float) ($r['rate'] ?? 0), 2);
                }
        
                return $rows;
    }

    public function getEngagementTrend(int $userId, string $startDate, string $endDate, ?string $platform)
    {
        $sql = 'SELECT snapshot_date AS date,
                        AVG(engagement_rate) AS rate,
                        SUM(likes) AS likes,
                        SUM(comments) AS comments
                    FROM analytics_snapshots
                    WHERE user_id = :uid AND period = \'daily\'
                    AND snapshot_date >= :start AND snapshot_date <= :end';
                $params = ['uid' => $userId, 'start' => $startDate, 'end' => $endDate];
                [$sql, $params] = analytics_sql_with_platform_filter($sql, $params, $platform);
                $sql .= ' GROUP BY snapshot_date ORDER BY snapshot_date ASC';
        
                $rows = $this->db->fetchAll($sql, $params);
                foreach ($rows as &$r) {
                    $r['rate'] = round((float) ($r['rate'] ?? 0), 2);
                    $r['likes'] = (int) ($r['likes'] ?? 0);
                    $r['comments'] = (int) ($r['comments'] ?? 0);
                }
        
                return $rows;
    }

    public function getPostingFrequency(int $userId, string $granularity, string $startDate, string $endDate)
    {
        $granularity = $granularity === 'monthly' ? 'monthly' : 'weekly';
                if ($granularity === 'monthly') {
                    $sql = 'SELECT DATE_FORMAT(published_at, \'%Y-%m\') AS bucket, COUNT(*) AS cnt
                        FROM posts
                        WHERE user_id = :uid AND is_deleted = 0 AND status = \'published\'
                        AND DATE(published_at) >= :start AND DATE(published_at) <= :end
                        GROUP BY bucket ORDER BY bucket ASC';
                } else {
                    $sql = 'SELECT YEARWEEK(published_at, 3) AS bucket, COUNT(*) AS cnt
                        FROM posts
                        WHERE user_id = :uid AND is_deleted = 0 AND status = \'published\'
                        AND DATE(published_at) >= :start AND DATE(published_at) <= :end
                        GROUP BY bucket ORDER BY bucket ASC';
                }
        
                return $this->db->fetchAll($sql, [
                    'uid' => $userId,
                    'start' => $startDate,
                    'end' => $endDate,
                ]);
    }

    public function getPlatformBreakdown(int $userId, string $startDate, string $endDate, ?string $platform)
    {
        $sql = 'SELECT s.platform, s.followers, s.impressions, s.reach, s.engagement_rate
                    FROM analytics_snapshots s
                    INNER JOIN (
                        SELECT platform, MAX(snapshot_date) AS md
                        FROM analytics_snapshots
                        WHERE user_id = :uid AND period = \'daily\'
                        AND platform IS NOT NULL AND platform != \'\'
                        AND snapshot_date >= :start AND snapshot_date <= :end';
                $params = ['uid' => $userId, 'start' => $startDate, 'end' => $endDate];
                [$sql, $params] = analytics_sql_with_platform_filter($sql, $params, $platform, false);
                $sql .= ' GROUP BY platform
                    ) t ON t.platform = s.platform AND t.md = s.snapshot_date
                    WHERE s.user_id = :uid_outer AND s.period = \'daily\'';
                $params['uid_outer'] = $userId;
        
                return $this->db->fetchAll($sql, $params);
    }

    /**
     * Real per-post performance, joined through platform_post_results/post_performance
     * (populated by FetchPostPerformanceJob for Instagram/YouTube -- the only platforms
     * with a granted read-back scope). Never fabricates data: a post is 'pending' until
     * the job has run, or 'unavailable' for platforms with no insights scope (TikTok,
     * Twitter) or a failed fetch.
     *
     * @return list<array<string, mixed>>
     */
    public function getTopPosts(int $userId, int $limit = 5, ?string $platform = null, string $sort = 'top')
    {
        $limit = max(1, min(20, $limit));
        $metric = self::TOP_POSTS_SORT_METRICS[$sort] ?? 'engagement_rate';
        $direction = $sort === 'worst' ? 'ASC' : 'DESC';

        $sql = 'SELECT p.id, p.title, p.published_at, m.thumbnail_url AS cover_thumb,
                    ppr.platform, ppr.platform_url,
                    pp.available, pp.likes, pp.comments, pp.shares, pp.saves, pp.reach, pp.engagement_rate
                FROM posts p
                INNER JOIN platform_post_results ppr ON ppr.post_id = p.id AND ppr.status = \'success\'
                LEFT JOIN post_performance pp ON pp.platform_post_result_id = ppr.id
                LEFT JOIN media_files m ON m.id = p.cover_media_id
                WHERE p.user_id = :uid AND p.is_deleted = 0 AND p.status = \'published\'';
        $params = ['uid' => $userId];

        $platform = PlatformHelper::normalize($platform);
        if ($platform !== null) {
            $sql .= ' AND ppr.platform = :plat';
            $params['plat'] = $platform;
        }

        $sql .= ' ORDER BY COALESCE(pp.available, 0) DESC, pp.' . $metric . ' ' . $direction . ', p.published_at DESC LIMIT :limit';
        $params = $this->db->bindLimit($params, $limit, 20);

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$r) {
            $plat = (string) $r['platform'];
            $hasPerfRow = $r['available'] !== null;

            if (!in_array($plat, self::INSIGHTS_SUPPORTED_PLATFORMS, true)) {
                $state = 'unavailable';
            } elseif (!$hasPerfRow) {
                $state = 'pending';
            } elseif ((int) $r['available'] === 1) {
                $state = 'ranked';
            } else {
                $state = 'unavailable';
            }

            $r['state'] = $state;
            $r['likes'] = (int) ($r['likes'] ?? 0);
            $r['comments'] = (int) ($r['comments'] ?? 0);
            $r['shares'] = (int) ($r['shares'] ?? 0);
            $r['saves'] = (int) ($r['saves'] ?? 0);
            $r['reach'] = (int) ($r['reach'] ?? 0);
            $r['engagement_rate'] = round((float) ($r['engagement_rate'] ?? 0), 2);
            unset($r['available']);
            if (!empty($r['cover_thumb']) && strpos((string) $r['cover_thumb'], 'http') !== 0) {
                $r['cover_thumb'] = upload_url(ltrim((string) $r['cover_thumb'], '/'));
            }
        }
        unset($r);

        return $rows;
    }

    public function countPublishedInRange(int $userId, string $startDate, string $endDate)
    {
        $row = $this->db->fetchOne(
                    'SELECT COUNT(*) AS c FROM posts
                    WHERE user_id = :uid AND is_deleted = 0 AND status = \'published\'
                    AND DATE(published_at) >= :start AND DATE(published_at) <= :end',
                    ['uid' => $userId, 'start' => $startDate, 'end' => $endDate]
                );
        
                return (int) ($row['c'] ?? 0);
    }

    public function firstPlatformSlug($platforms)
    {
        if (is_string($platforms)) {
                    $decoded = json_decode($platforms, true);
                    $platforms = is_array($decoded) ? $decoded : [];
                }
                if (!is_array($platforms) || $platforms === []) {
                    return 'instagram';
                }
        
                return (string) $platforms[0];
    }

}
