<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\AnalyticsRepository;

/**
 * Growth deltas, rule-based insights, and simple predictions built on top of
 * the existing analytics_snapshots history. Insights/predictions are cached
 * in insights_cache/prediction_cache and regenerated at most once per hour
 * per user (see ensureFresh()) since regeneration is cheap internal SQL, not
 * an external API call.
 */
final class AnalyticsIntelligenceService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const META_INSIGHT_KEY = '_meta_last_run';
    private const PREDICTABLE_METRICS = ['followers', 'engagement_rate'];

    /** @var Connection */
    private $db;

    /** @var AnalyticsRepository */
    private $analytics;

    public function __construct(Connection $db, AnalyticsRepository $analytics)
    {
        $this->db = $db;
        $this->analytics = $analytics;
    }

    public function ensureFresh(int $userId): void
    {
        $row = $this->db->fetchOne(
            'SELECT generated_at FROM insights_cache WHERE user_id = :uid AND insight_key = :k LIMIT 1',
            ['uid' => $userId, 'k' => self::META_INSIGHT_KEY]
        );

        $stale = $row === null
            || (time() - strtotime((string) $row['generated_at'])) > self::CACHE_TTL_SECONDS;

        if ($stale) {
            $this->regenerate($userId);
        }
    }

    private function regenerate(int $userId): void
    {
        foreach ($this->generateInsights($userId) as $insight) {
            $this->storeInsight($userId, $insight);
        }
        foreach ($this->predict($userId, null) as $prediction) {
            $this->storePrediction($userId, $prediction);
        }

        $this->storeInsight($userId, [
            'insight_key' => self::META_INSIGHT_KEY,
            'message' => '',
            'severity' => 'neutral',
            'metric' => null,
            'platform' => null,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getInsights(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT insight_key, message, severity, metric, platform, generated_at
             FROM insights_cache
             WHERE user_id = :uid AND insight_key != :meta
             ORDER BY generated_at DESC',
            ['uid' => $userId, 'meta' => self::META_INSIGHT_KEY]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getPredictions(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT platform, metric, horizon, method, predicted_value, based_on_snapshots, generated_at
             FROM prediction_cache
             WHERE user_id = :uid
             ORDER BY metric ASC, horizon ASC',
            ['uid' => $userId]
        );

        foreach ($rows as &$r) {
            $r['predicted_value'] = (float) $r['predicted_value'];
            $r['based_on_snapshots'] = (int) $r['based_on_snapshots'];
        }

        return $rows;
    }

    /**
     * Live (uncached) today/week/month deltas for the headline metrics.
     * Each window compares against the nearest available snapshot on or
     * before the target date, since jobs on InfinityFree only run when the
     * webhook is hit and daily rows can have gaps.
     */
    public function computeGrowthDeltas(int $userId, ?string $platform): array
    {
        $today = date('Y-m-d');
        $current = $this->snapshotAsOf($userId, $today, $platform);

        if ($current === null) {
            return ['available' => false, 'as_of' => null, 'metrics' => []];
        }

        $prevDay = $this->snapshotAsOf($userId, date('Y-m-d', strtotime($current['date'] . ' -1 day')), $platform);
        $prevWeek = $this->snapshotAsOf($userId, date('Y-m-d', strtotime($current['date'] . ' -7 day')), $platform);
        $prevMonth = $this->snapshotAsOf($userId, date('Y-m-d', strtotime($current['date'] . ' -30 day')), $platform);

        $metrics = [];
        foreach (['followers', 'impressions', 'reach', 'engagement_rate'] as $metric) {
            $metrics[$metric] = [
                'value' => $current[$metric],
                'today' => $this->deltaWindow($current[$metric], $prevDay[$metric] ?? null),
                'week' => $this->deltaWindow($current[$metric], $prevWeek[$metric] ?? null),
                'month' => $this->deltaWindow($current[$metric], $prevMonth[$metric] ?? null),
            ];
        }

        return ['available' => true, 'as_of' => $current['date'], 'metrics' => $metrics];
    }

    /**
     * @return array{date: string, followers: int, impressions: int, reach: int, engagement_rate: float}|null
     */
    private function snapshotAsOf(int $userId, string $asOfDate, ?string $platform): ?array
    {
        $sql = 'SELECT MAX(snapshot_date) AS d FROM analytics_snapshots
                WHERE user_id = :uid AND period = \'daily\' AND snapshot_date <= :asof';
        $params = ['uid' => $userId, 'asof' => $asOfDate];
        [$sql, $params] = $this->analytics->sqlWithPlatformFilter($sql, $params, $platform);

        $dateRow = $this->db->fetchOne($sql, $params);
        $date = $dateRow['d'] ?? null;
        if ($date === null) {
            return null;
        }

        $sql2 = 'SELECT COALESCE(SUM(followers), 0) AS followers,
                        COALESCE(SUM(impressions), 0) AS impressions,
                        COALESCE(SUM(reach), 0) AS reach,
                        COALESCE(AVG(engagement_rate), 0) AS engagement_rate
                 FROM analytics_snapshots
                 WHERE user_id = :uid AND period = \'daily\' AND snapshot_date = :dt';
        $params2 = ['uid' => $userId, 'dt' => $date];
        [$sql2, $params2] = $this->analytics->sqlWithPlatformFilter($sql2, $params2, $platform);

        $row = $this->db->fetchOne($sql2, $params2);

        return [
            'date' => (string) $date,
            'followers' => (int) ($row['followers'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'reach' => (int) ($row['reach'] ?? 0),
            'engagement_rate' => round((float) ($row['engagement_rate'] ?? 0), 2),
        ];
    }

    /**
     * @param int|float $current
     * @param int|float|null $previous
     */
    private function deltaWindow($current, $previous): ?array
    {
        if ($previous === null) {
            return null;
        }

        $delta = $current - $previous;
        $pct = $previous != 0
            ? round(($delta / $previous) * 100, 2)
            : ($delta > 0 ? 100.0 : 0.0);

        return ['delta' => $delta, 'pct' => $pct];
    }

    /**
     * @return list<array{insight_key: string, message: string, severity: string, metric: ?string, platform: ?string}>
     */
    private function generateInsights(int $userId): array
    {
        $insights = [];

        $deltas = $this->computeGrowthDeltas($userId, null);
        if ($deltas['available']) {
            $followerWeek = $deltas['metrics']['followers']['week'] ?? null;
            if ($followerWeek !== null && abs($followerWeek['pct']) >= 2.0) {
                $insights[] = [
                    'insight_key' => 'follower_growth_wow',
                    'message' => $followerWeek['pct'] > 0
                        ? sprintf('Follower growth increased %.1f%% compared to last week.', $followerWeek['pct'])
                        : sprintf('Follower growth decreased %.1f%% compared to last week.', abs($followerWeek['pct'])),
                    'severity' => $followerWeek['pct'] > 0 ? 'positive' : 'negative',
                    'metric' => 'followers',
                    'platform' => null,
                ];
            }

            $engWeek = $deltas['metrics']['engagement_rate']['week'] ?? null;
            if ($engWeek !== null && $followerWeek !== null && ($engWeek['pct'] - $followerWeek['pct']) >= 1.0) {
                $insights[] = [
                    'insight_key' => 'engagement_outpacing_followers',
                    'message' => 'Engagement is growing faster than your follower count this week.',
                    'severity' => 'positive',
                    'metric' => 'engagement_rate',
                    'platform' => null,
                ];
            }
        }

        foreach ($this->decliningStreakInsights($userId) as $insight) {
            $insights[] = $insight;
        }

        $postingInsight = $this->postingFrequencyInsight($userId);
        if ($postingInsight !== null) {
            $insights[] = $postingInsight;
        }

        $weekendInsight = $this->weekendEngagementInsight($userId);
        if ($weekendInsight !== null) {
            $insights[] = $weekendInsight;
        }

        return $insights;
    }

    /**
     * Detects 3+ consecutive daily drops in impressions, per platform.
     *
     * @return list<array{insight_key: string, message: string, severity: string, metric: ?string, platform: ?string}>
     */
    private function decliningStreakInsights(int $userId): array
    {
        $platforms = $this->db->fetchAll(
            'SELECT DISTINCT platform FROM analytics_snapshots
             WHERE user_id = :uid AND period = \'daily\' AND platform IS NOT NULL AND platform != \'\'',
            ['uid' => $userId]
        );

        $out = [];
        foreach ($platforms as $p) {
            $platform = (string) $p['platform'];
            $rows = $this->db->fetchAll(
                'SELECT snapshot_date AS d, impressions AS v FROM analytics_snapshots
                 WHERE user_id = :uid AND period = \'daily\' AND platform = :plat
                 ORDER BY snapshot_date DESC LIMIT 5',
                ['uid' => $userId, 'plat' => $platform]
            );
            $rows = array_reverse($rows);

            if (count($rows) < 3) {
                continue;
            }

            $streak = 1;
            for ($i = count($rows) - 1; $i > 0; $i--) {
                if ((int) $rows[$i]['v'] < (int) $rows[$i - 1]['v']) {
                    $streak++;
                } else {
                    break;
                }
            }

            if ($streak >= 3) {
                $out[] = [
                    'insight_key' => 'declining_streak_' . $platform,
                    'message' => sprintf(
                        '%s impressions have declined for %d consecutive snapshots.',
                        ucfirst($platform),
                        $streak
                    ),
                    'severity' => 'negative',
                    'metric' => 'impressions',
                    'platform' => $platform,
                ];
            }
        }

        return $out;
    }

    private function postingFrequencyInsight(int $userId): ?array
    {
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-56 days'));
        $rows = $this->analytics->getPostingFrequency($userId, 'weekly', $start, $end);

        if (count($rows) < 2) {
            return null;
        }

        $last = (int) $rows[count($rows) - 1]['cnt'];
        $prev = (int) $rows[count($rows) - 2]['cnt'];

        if ($prev === 0) {
            return null;
        }

        $pct = round((($last - $prev) / $prev) * 100, 1);
        if (abs($pct) < 15.0) {
            return null;
        }

        return [
            'insight_key' => 'posting_frequency_change',
            'message' => $pct < 0
                ? sprintf('Posting frequency dropped %.0f%% compared to the previous week.', abs($pct))
                : sprintf('Posting frequency increased %.0f%% compared to the previous week.', $pct),
            'severity' => $pct < 0 ? 'negative' : 'positive',
            'metric' => 'posts',
            'platform' => null,
        ];
    }

    private function weekendEngagementInsight(int $userId): ?array
    {
        $start = date('Y-m-d', strtotime('-29 days'));
        $row = $this->db->fetchOne(
            'SELECT
                AVG(CASE WHEN DAYOFWEEK(snapshot_date) IN (1,7) THEN engagement_rate END) AS weekend_rate,
                AVG(CASE WHEN DAYOFWEEK(snapshot_date) NOT IN (1,7) THEN engagement_rate END) AS weekday_rate
             FROM analytics_snapshots
             WHERE user_id = :uid AND period = \'daily\' AND snapshot_date >= :start',
            ['uid' => $userId, 'start' => $start]
        );

        $weekend = $row['weekend_rate'] ?? null;
        $weekday = $row['weekday_rate'] ?? null;
        if ($weekend === null || $weekday === null || (float) $weekday <= 0) {
            return null;
        }

        $pct = round(((float) $weekend - (float) $weekday) / (float) $weekday * 100, 1);
        if (abs($pct) < 5.0) {
            return null;
        }

        return [
            'insight_key' => 'weekend_vs_weekday_engagement',
            'message' => $pct > 0
                ? sprintf('Weekend posts receive %.0f%% more engagement than weekdays.', $pct)
                : sprintf('Weekday posts receive %.0f%% more engagement than weekends.', abs($pct)),
            'severity' => 'neutral',
            'metric' => 'engagement_rate',
            'platform' => null,
        ];
    }

    /**
     * @return list<array{platform: ?string, metric: string, horizon: string, method: string, predicted_value: float, based_on_snapshots: int}>
     */
    private function predict(int $userId, ?string $platform): array
    {
        $out = [];

        foreach (self::PREDICTABLE_METRICS as $metric) {
            $series = $this->recentDailySeries($userId, $platform, $metric, 30);
            if (count($series) < 3) {
                continue;
            }

            $method = count($series) >= 5 ? 'linear_regression' : 'moving_average';
            $lastX = $series[count($series) - 1]['x'];

            if ($method === 'linear_regression') {
                [$slope, $intercept] = $this->linearRegression($series);
                $predictWeek = $intercept + $slope * ($lastX + 7);
                $predictMonth = $intercept + $slope * ($lastX + 30);
            } else {
                $avgDelta = $this->averageDailyDelta($series);
                $lastY = $series[count($series) - 1]['y'];
                $predictWeek = $lastY + $avgDelta * 7;
                $predictMonth = $lastY + $avgDelta * 30;
            }

            $out[] = [
                'platform' => $platform,
                'metric' => $metric,
                'horizon' => 'next_week',
                'method' => $method,
                'predicted_value' => round(max(0.0, $predictWeek), 2),
                'based_on_snapshots' => count($series),
            ];
            $out[] = [
                'platform' => $platform,
                'metric' => $metric,
                'horizon' => 'next_month',
                'method' => $method,
                'predicted_value' => round(max(0.0, $predictMonth), 2),
                'based_on_snapshots' => count($series),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{x: int, y: float}>
     */
    private function recentDailySeries(int $userId, ?string $platform, string $metric, int $limitDays): array
    {
        $agg = $metric === 'engagement_rate' ? 'AVG(engagement_rate)' : 'SUM(' . $metric . ')';

        $sql = 'SELECT snapshot_date AS d, ' . $agg . ' AS v FROM analytics_snapshots
                WHERE user_id = :uid AND period = \'daily\'';
        $params = ['uid' => $userId];
        [$sql, $params] = $this->analytics->sqlWithPlatformFilter($sql, $params, $platform);
        $sql .= ' GROUP BY snapshot_date ORDER BY snapshot_date DESC LIMIT ' . max(1, min(90, $limitDays));

        $rows = array_reverse($this->db->fetchAll($sql, $params));

        $out = [];
        foreach (array_values($rows) as $i => $r) {
            $out[] = ['x' => $i, 'y' => (float) ($r['v'] ?? 0)];
        }

        return $out;
    }

    /**
     * @param list<array{x: int, y: float}> $series
     * @return array{0: float, 1: float}
     */
    private function linearRegression(array $series): array
    {
        $n = count($series);
        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        foreach ($series as $point) {
            $sumX += $point['x'];
            $sumY += $point['y'];
            $sumXY += $point['x'] * $point['y'];
            $sumX2 += $point['x'] * $point['x'];
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0.0) {
            return [0.0, $sumY / $n];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [$slope, $intercept];
    }

    /**
     * @param list<array{x: int, y: float}> $series
     */
    private function averageDailyDelta(array $series): float
    {
        $n = count($series);
        if ($n < 2) {
            return 0.0;
        }

        $span = max(1, $series[$n - 1]['x'] - $series[0]['x']);

        return ($series[$n - 1]['y'] - $series[0]['y']) / $span;
    }

    private function storeInsight(int $userId, array $insight): void
    {
        $this->db->query(
            'INSERT INTO insights_cache (user_id, insight_key, message, severity, metric, platform, generated_at)
             VALUES (:uid, :key, :msg, :sev, :metric, :platform, NOW())
             ON DUPLICATE KEY UPDATE
                message = VALUES(message),
                severity = VALUES(severity),
                metric = VALUES(metric),
                platform = VALUES(platform),
                generated_at = VALUES(generated_at)',
            [
                'uid' => $userId,
                'key' => $insight['insight_key'],
                'msg' => $insight['message'],
                'sev' => $insight['severity'],
                'metric' => $insight['metric'],
                'platform' => $insight['platform'],
            ]
        );
    }

    private function storePrediction(int $userId, array $prediction): void
    {
        $this->db->query(
            'INSERT INTO prediction_cache (
                user_id, platform, metric, horizon, method, predicted_value, based_on_snapshots, generated_at
             ) VALUES (
                :uid, :platform, :metric, :horizon, :method, :value, :based_on, NOW()
             ) ON DUPLICATE KEY UPDATE
                method = VALUES(method),
                predicted_value = VALUES(predicted_value),
                based_on_snapshots = VALUES(based_on_snapshots),
                generated_at = VALUES(generated_at)',
            [
                'uid' => $userId,
                'platform' => $prediction['platform'] ?? '',
                'metric' => $prediction['metric'],
                'horizon' => $prediction['horizon'],
                'method' => $prediction['method'],
                'value' => $prediction['predicted_value'],
                'based_on' => $prediction['based_on_snapshots'],
            ]
        );
    }
}
