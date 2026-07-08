<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\AnalyticsRepository;

/**
 * Composite growth/engagement/consistency scoring and per-platform health,
 * derived entirely from data already stored in analytics_snapshots/analytics.
 * Scores are cached in creator_scores and regenerated at most once per hour
 * per user (cheap internal SQL, not an external API call).
 */
final class CreatorScoreService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const ENGAGEMENT_TARGET_RATE = 8.0;
    private const CONSISTENCY_TARGET_PER_WEEK = 3.0;

    /** @var Connection */
    private $db;

    /** @var AnalyticsRepository */
    private $analytics;

    public function __construct(Connection $db, AnalyticsRepository $analytics)
    {
        $this->db = $db;
        $this->analytics = $analytics;
    }

    public function getScores(int $userId): array
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM creator_scores WHERE user_id = :uid LIMIT 1',
            ['uid' => $userId]
        );

        $stale = $row === null
            || (time() - strtotime((string) $row['computed_at'])) > self::CACHE_TTL_SECONDS;

        if (!$stale) {
            return [
                'available' => true,
                'growth_score' => (float) $row['growth_score'],
                'engagement_score' => (float) $row['engagement_score'],
                'consistency_score' => (float) $row['consistency_score'],
                'creator_score' => (float) $row['creator_score'],
                'computed_at' => (string) $row['computed_at'],
            ];
        }

        $computed = $this->compute($userId);
        if ($computed === null) {
            return [
                'available' => false,
                'growth_score' => null,
                'engagement_score' => null,
                'consistency_score' => null,
                'creator_score' => null,
                'computed_at' => null,
            ];
        }

        $this->store($userId, $computed);

        return array_merge(['available' => true, 'computed_at' => date('Y-m-d H:i:s')], $computed);
    }

    /**
     * @return list<array{platform: string, status: string, growth_pct: ?float}>
     */
    public function computePlatformHealth(int $userId): array
    {
        $platforms = $this->db->fetchAll(
            'SELECT DISTINCT platform FROM analytics_snapshots
             WHERE user_id = :uid AND period = \'daily\' AND platform IS NOT NULL AND platform != \'\'',
            ['uid' => $userId]
        );

        $out = [];
        foreach ($platforms as $p) {
            $platform = (string) $p['platform'];
            $status = $this->platformStatus($userId, $platform);
            $out[] = array_merge(['platform' => $platform], $status);
        }

        return $out;
    }

    /**
     * @return array{growth_score: float, engagement_score: float, consistency_score: float, creator_score: float}|null
     */
    private function compute(int $userId): ?array
    {
        if (!$this->analytics->hasSnapshotData($userId)) {
            return null;
        }

        $growthPct = $this->followerGrowthPct($userId);
        $engagementRate = $this->currentEngagementRate($userId);
        $consistency = $this->postingConsistency($userId);

        $growthScore = $this->clamp(50.0 + ($growthPct ?? 0.0) * 2, 0.0, 100.0);
        $engagementScore = $this->clamp(($engagementRate / self::ENGAGEMENT_TARGET_RATE) * 100, 0.0, 100.0);
        $consistencyScore = $this->clamp(($consistency / self::CONSISTENCY_TARGET_PER_WEEK) * 100, 0.0, 100.0);

        $creatorScore = round(0.4 * $growthScore + 0.35 * $engagementScore + 0.25 * $consistencyScore, 2);

        return [
            'growth_score' => round($growthScore, 2),
            'engagement_score' => round($engagementScore, 2),
            'consistency_score' => round($consistencyScore, 2),
            'creator_score' => $creatorScore,
        ];
    }

    private function followerGrowthPct(int $userId): ?float
    {
        $today = date('Y-m-d');
        $current = $this->followersAsOf($userId, $today);
        if ($current === null) {
            return null;
        }

        $prior = $this->followersAsOf($userId, date('Y-m-d', strtotime($current['date'] . ' -30 day')));
        if ($prior === null || $prior['value'] <= 0) {
            return null;
        }

        return round((($current['value'] - $prior['value']) / $prior['value']) * 100, 2);
    }

    /**
     * @return array{date: string, value: int}|null
     */
    private function followersAsOf(int $userId, string $asOfDate): ?array
    {
        $dateRow = $this->db->fetchOne(
            'SELECT MAX(snapshot_date) AS d FROM analytics_snapshots
             WHERE user_id = :uid AND period = \'daily\' AND snapshot_date <= :asof
             AND platform IS NOT NULL AND platform != \'\'',
            ['uid' => $userId, 'asof' => $asOfDate]
        );
        $date = $dateRow['d'] ?? null;
        if ($date === null) {
            return null;
        }

        $row = $this->db->fetchOne(
            'SELECT COALESCE(SUM(followers), 0) AS f FROM analytics_snapshots
             WHERE user_id = :uid AND period = \'daily\' AND snapshot_date = :dt
             AND platform IS NOT NULL AND platform != \'\'',
            ['uid' => $userId, 'dt' => $date]
        );

        return ['date' => (string) $date, 'value' => (int) ($row['f'] ?? 0)];
    }

    private function currentEngagementRate(int $userId): float
    {
        $row = $this->analytics->getByUser($userId);

        return $row !== null ? (float) ($row['avg_engagement_rate'] ?? 0) : 0.0;
    }

    private function postingConsistency(int $userId): float
    {
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-27 days'));
        $rows = $this->analytics->getPostingFrequency($userId, 'weekly', $start, $end);

        $total = 0;
        foreach ($rows as $r) {
            $total += (int) ($r['cnt'] ?? 0);
        }

        return $total / 4.0;
    }

    /**
     * @return array{status: string, growth_pct: ?float}
     */
    private function platformStatus(int $userId, string $platform): array
    {
        $rows = $this->db->fetchAll(
            'SELECT snapshot_date AS d, followers AS f, impressions AS i FROM analytics_snapshots
             WHERE user_id = :uid AND period = \'daily\' AND platform = :plat
             ORDER BY snapshot_date DESC LIMIT 5',
            ['uid' => $userId, 'plat' => $platform]
        );
        $rows = array_values(array_reverse($rows));

        if (count($rows) < 2) {
            return ['status' => 'unknown', 'growth_pct' => null];
        }

        $first = (int) $rows[0]['f'];
        $last = (int) $rows[count($rows) - 1]['f'];
        $growthPct = $first > 0 ? round((($last - $first) / $first) * 100, 1) : null;

        $decliningStreak = 1;
        for ($i = count($rows) - 1; $i > 0; $i--) {
            if ((int) $rows[$i]['i'] < (int) $rows[$i - 1]['i']) {
                $decliningStreak++;
            } else {
                break;
            }
        }

        if ($decliningStreak >= 3) {
            $status = 'at_risk';
        } elseif ($growthPct !== null && $growthPct > 0) {
            $status = 'healthy';
        } else {
            $status = 'steady';
        }

        return ['status' => $status, 'growth_pct' => $growthPct];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    private function store(int $userId, array $scores): void
    {
        $this->db->query(
            'INSERT INTO creator_scores (user_id, growth_score, engagement_score, consistency_score, creator_score, computed_at)
             VALUES (:uid, :g, :e, :c, :cs, NOW())
             ON DUPLICATE KEY UPDATE
                growth_score = VALUES(growth_score),
                engagement_score = VALUES(engagement_score),
                consistency_score = VALUES(consistency_score),
                creator_score = VALUES(creator_score),
                computed_at = VALUES(computed_at)',
            [
                'uid' => $userId,
                'g' => $scores['growth_score'],
                'e' => $scores['engagement_score'],
                'c' => $scores['consistency_score'],
                'cs' => $scores['creator_score'],
            ]
        );
    }
}
