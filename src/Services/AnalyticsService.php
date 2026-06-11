<?php

declare(strict_types=1);

namespace CreatorzHive\Services;



use function analytics_get_by_user;
use function analytics_service_aggregate_totals;
use function analytics_service_calculate_engagement_rate;
use function analytics_upsert;
use CreatorzHive\Core\Database\Connection;

final class AnalyticsService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function syncFromPlatform(int $socialAccountId)
    {
        unset($socialAccountId);
    }

    public function calculateEngagementRate(int $likes, int $comments, int $shares, int $followers)
    {
        if ($followers <= 1) {
                return 0.0;
            }
        
            return round((($likes + $comments + $shares) / $followers) * 100, 2);
    }

    public function generateDemoData(int $userId)
    {
        $accounts = $this->db->fetchAll(
                'SELECT id, platform FROM social_accounts WHERE user_id = :uid AND is_active = 1',
                ['uid' => $userId]
            );
        
            $platforms = [];
            foreach ($accounts as $a) {
                $platforms[] = (string) $a['platform'];
            }
            if ($platforms === []) {
                $platforms = ['instagram', 'tiktok'];
            }
        
            $this->db->query(
                'DELETE FROM analytics_snapshots WHERE user_id = :uid AND period = :period',
                ['uid' => $userId, 'period' => 'daily']
            );
        
            $cumulative = [];
            foreach ($platforms as $plat) {
                $cumulative[$plat] = 5000 + (crc32($plat) % 4000);
            }
        
            $rng = function (int $seed): float {
                $x = sin((float) $seed) * 10000;
        
                return $x - floor($x);
            };
        
            for ($d = 89; $d >= 0; $d--) {
                $ts = strtotime('-' . $d . ' days');
                $date = date('Y-m-d', $ts);
                $dow = (int) date('w', $ts);
                $isWeekend = $dow === 0 || $dow === 6;
                $dayIndex = 89 - $d;
        
                foreach ($platforms as $pi => $plat) {
                    $noise = (int) (5 + $rng($dayIndex * 100 + $pi * 17 + strlen($plat)) * 12);
                    $cumulative[$plat] += $noise;
                    $followers = (int) $cumulative[$plat];
        
                    $impressions = (int) (400 + $rng($dayIndex * 3 + 1) * 400 + ($isWeekend ? 120 : 0));
                    if ($rng($dayIndex + 5) > 0.92) {
                        $impressions += (int) (500 + $rng($dayIndex) * 800);
                    }
        
                    $reach = (int) ($impressions * (0.65 + $rng($dayIndex + 2) * 0.15));
                    $likes = (int) ($impressions * (0.02 + $rng($dayIndex + 3) * 0.03 + ($isWeekend ? 0.01 : 0)));
                    $comments = (int) ($likes * (0.08 + $rng($dayIndex + 4) * 0.12));
                    $shares = (int) ($likes * (0.02 + $rng($dayIndex + 6) * 0.05));
                    $saves = (int) ($likes * (0.05 + $rng($dayIndex + 7) * 0.08));
                    $rate = analytics_service_calculate_engagement_rate($likes, $comments, $shares, max(1, $followers));
                    if ($isWeekend) {
                        $rate = min(6.5, $rate + 0.4 + $rng($dayIndex) * 0.6);
                    }
                    $rate = max(2.0, min(6.0, $rate));
        
                    $this->db->query(
                        'INSERT INTO analytics_snapshots (
                                user_id, social_account_id, platform, snapshot_date, period,
                                followers, impressions, reach, likes, comments, shares, saves,
                                link_clicks, profile_visits, engagement_rate
                            ) VALUES (
                                :uid, NULL, :plat, :dt, \'daily\',
                                :fol, :imp, :reach, :likes, :comments, :shares, :saves,
                                :clicks, :pvis, :erate
                            ) ON DUPLICATE KEY UPDATE
                                followers = VALUES(followers),
                                impressions = VALUES(impressions),
                                reach = VALUES(reach),
                                likes = VALUES(likes),
                                comments = VALUES(comments),
                                shares = VALUES(shares),
                                saves = VALUES(saves),
                                link_clicks = VALUES(link_clicks),
                                profile_visits = VALUES(profile_visits),
                                engagement_rate = VALUES(engagement_rate)',
                        [
                            'uid' => $userId,
                            'plat' => $plat,
                            'dt' => $date,
                            'fol' => $followers,
                            'imp' => $impressions,
                            'reach' => $reach,
                            'likes' => $likes,
                            'comments' => $comments,
                            'shares' => $shares,
                            'saves' => $saves,
                            'clicks' => (int) ($likes * 0.03),
                            'pvis' => (int) ($reach * 0.01),
                            'erate' => $rate,
                        ]
                    );
                }
            }
        
            analytics_service_aggregate_totals($userId);
    }

    public function aggregateTotals(int $userId)
    {
        $followerSum = $this->db->fetchOne(
                'SELECT COALESCE(SUM(s.followers), 0) AS total FROM analytics_snapshots s
                    INNER JOIN (
                        SELECT platform, MAX(snapshot_date) AS md
                        FROM analytics_snapshots
                        WHERE user_id = :uid AND period = \'daily\' AND platform IS NOT NULL AND platform != \'\'
                        GROUP BY platform
                    ) t ON t.platform = s.platform AND t.md = s.snapshot_date
                    WHERE s.user_id = :uid AND s.period = \'daily\'',
                ['uid' => $userId]
            );
        
            $roll = $this->db->fetchOne(
                'SELECT
                        COALESCE(SUM(impressions), 0) AS total_impressions,
                        COALESCE(SUM(reach), 0) AS total_reach,
                        COALESCE(SUM(likes + comments + shares), 0) AS total_engagements,
                        COALESCE(AVG(engagement_rate), 0) AS avg_engagement_rate
                    FROM analytics_snapshots
                    WHERE user_id = :uid AND period = \'daily\'
                    AND platform IS NOT NULL
                    AND snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)',
                ['uid' => $userId]
            );
        
            $existing = analytics_get_by_user($userId);
            $postPreserve = [
                'total_posts' => (int) ($existing['total_posts'] ?? 0),
                'published_posts' => (int) ($existing['published_posts'] ?? 0),
                'draft_posts' => (int) ($existing['draft_posts'] ?? 0),
                'scheduled_posts' => (int) ($existing['scheduled_posts'] ?? 0),
                'failed_posts' => (int) ($existing['failed_posts'] ?? 0),
                'total_revenue' => (float) ($existing['total_revenue'] ?? 0),
            ];
        
            analytics_upsert($userId, array_merge($postPreserve, [
                'total_followers' => (int) ($followerSum['total'] ?? 0),
                'total_impressions' => (int) ($roll['total_impressions'] ?? 0),
                'total_reach' => (int) ($roll['total_reach'] ?? 0),
                'total_engagements' => (int) ($roll['total_engagements'] ?? 0),
                'avg_engagement_rate' => round((float) ($roll['avg_engagement_rate'] ?? 0), 2),
            ]));
    }

}
