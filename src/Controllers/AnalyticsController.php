<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\AnalyticsRepository;
use CreatorzHive\Services\AnalyticsService;
use CreatorzHive\Services\AnalyticsIntelligenceService;
use CreatorzHive\Helpers\PlatformHelper;
use CreatorzHive\Support\AnalyticsReportHelper;
use function env;
use function request_get;
use function session_get_user;

final class AnalyticsController extends AbstractController
{
    /** @var AnalyticsRepository */
    private $analytics;

    /** @var AnalyticsReportHelper */
    private $reports;

    /** @var AnalyticsService */
    private $analyticsService;

    /** @var AnalyticsIntelligenceService */
    private $intelligence;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        AnalyticsRepository $analytics,
        AnalyticsReportHelper $reports,
        AnalyticsService $analyticsService,
        AnalyticsIntelligenceService $intelligence
    ) {
        parent::__construct($views, $json, $db);
        $this->analytics = $analytics;
        $this->reports = $reports;
        $this->analyticsService = $analyticsService;
        $this->intelligence = $intelligence;
    }

    public function index(): void
    {
        $this->views->render('analytics/index');
    }

    public function data(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $userId = (int) $user['id'];
        $period = (string) request_get('period', '30d');
        $startIn = (string) request_get('start_date', '');
        $endIn = (string) request_get('end_date', '');
        $platformRaw = (string) request_get('platform', '');
        $platform = PlatformHelper::normalize($platformRaw !== '' ? $platformRaw : null);
        $sortRaw = (string) request_get('sort', 'top');
        $sort = in_array($sortRaw, ['top', 'worst', 'most_commented', 'highest_reach'], true) ? $sortRaw : 'top';

        [$curStart, $curEnd] = $this->reports->resolvePeriodRange($period, $startIn, $endIn, $userId);
        $rangeDays = max(1, (int) round((strtotime($curEnd) - strtotime($curStart)) / 86400) + 1);
        $prevEnd = date('Y-m-d', strtotime($curStart . ' -1 day'));
        $prevStart = date('Y-m-d', strtotime($prevEnd . ' -' . ($rangeDays - 1) . ' days'));

        $hasData = $this->analytics->hasSnapshotData($userId);
        if ($hasData) {
            $this->intelligence->ensureFresh($userId);
        }

        $curFollowEnd = $this->analytics->sumFollowersOnDate($userId, $curEnd, $platform);
        $prevFollowEnd = $this->analytics->sumFollowersOnDate($userId, $prevEnd, $platform);
        $followerGrowth = $curFollowEnd - $prevFollowEnd;
        $followerGrowthPct = $prevFollowEnd > 0
            ? round(($followerGrowth / $prevFollowEnd) * 100, 2)
            : ($followerGrowth > 0 ? 100.0 : 0.0);

        $curM = $this->analytics->sumMetricsInRange($userId, $curStart, $curEnd, $platform);
        $prevM = $this->analytics->sumMetricsInRange($userId, $prevStart, $prevEnd, $platform);

        $postsPublished = $this->analytics->countPublishedInRange($userId, $curStart, $curEnd);
        $postsPrev = $this->analytics->countPublishedInRange($userId, $prevStart, $prevEnd);

        $followerTrend = $this->analytics->getGrowthTrend($userId, $curStart, $curEnd, $platform);
        $dailyRollup = $this->analytics->getDailyRollupTrend($userId, $curStart, $curEnd, $platform);
        $engagementTrend = $this->analytics->getEngagementTrend($userId, $curStart, $curEnd, $platform);
        $postingFrequency = $this->analytics->getPostingFrequency($userId, 'weekly', $curStart, $curEnd);
        $platformBreakdown = $this->analytics->getPlatformBreakdown($userId, $curStart, $curEnd, $platform);
        $topPosts = $this->analytics->getTopPosts($userId, 5, $platform, $sort);

        foreach ($platformBreakdown as &$pb) {
            $pb['engagement_rate'] = round((float) ($pb['engagement_rate'] ?? 0), 2);
        }
        unset($pb);

        $sparkN = 14;
        $sparkFollowers = array_map(static function (array $r): int {
            return (int) $r['followers'];
        }, array_slice($followerTrend, -$sparkN));
        $sliceRollup = array_slice($dailyRollup, -$sparkN);
        $sparkImpressions = array_map(static function (array $r): int {
            return (int) $r['impressions'];
        }, $sliceRollup);
        $sparkReach = array_map(static function (array $r): int {
            return (int) $r['reach'];
        }, $sliceRollup);
        $sparkEngagements = array_map(static function (array $r): int {
            return (int) $r['engagements'];
        }, $sliceRollup);
        $sparkRates = array_map(static function (array $r): float {
            return (float) $r['rate'];
        }, $sliceRollup);
        $sparkPosts = $this->reports->postsPublishedSparkline($userId, $curEnd, $sparkN);

        $this->json->success([
            'has_data' => $hasData,
            'range' => [
                'start' => $curStart,
                'end' => $curEnd,
                'period' => $period,
                'platform' => $platform,
                'sort' => $sort,
            ],
            'summary' => [
                'total_followers' => $curFollowEnd,
                'follower_growth' => $followerGrowth,
                'follower_growth_pct' => $followerGrowthPct,
                'total_impressions' => $curM['impressions'],
                'impressions_change_pct' => $this->reports->percentChange((float) $curM['impressions'], (float) $prevM['impressions']),
                'total_reach' => $curM['reach'],
                'reach_change_pct' => $this->reports->percentChange((float) $curM['reach'], (float) $prevM['reach']),
                'total_engagements' => $curM['engagements'],
                'engagements_change_pct' => $this->reports->percentChange((float) $curM['engagements'], (float) $prevM['engagements']),
                'avg_engagement_rate' => $curM['avg_engagement_rate'],
                'avg_engagement_change_pct' => $this->reports->percentChange((float) $curM['avg_engagement_rate'], (float) $prevM['avg_engagement_rate']),
                'posts_published' => $postsPublished,
                'posts_published_change_pct' => $this->reports->percentChange((float) $postsPublished, (float) $postsPrev),
            ],
            'sparklines' => [
                'followers' => $sparkFollowers,
                'impressions' => $sparkImpressions,
                'reach' => $sparkReach,
                'engagements' => $sparkEngagements,
                'avg_engagement_rate' => $sparkRates,
                'posts_published' => $sparkPosts,
            ],
            'follower_trend' => $followerTrend,
            'engagement_trend' => $engagementTrend,
            'posting_frequency' => $this->reports->formatPostingFrequency($postingFrequency),
            'platform_breakdown' => $platformBreakdown,
            'top_posts' => $topPosts,
            'growth_deltas' => $hasData ? $this->intelligence->computeGrowthDeltas($userId, $platform) : ['available' => false, 'as_of' => null, 'metrics' => []],
            'insights' => $hasData ? $this->intelligence->getInsights($userId) : [],
            'predictions' => $hasData ? $this->intelligence->getPredictions($userId) : [],
        ], 'Analytics loaded');
    }

    public function seed(): void
    {
        $user = session_get_user();
        if ($user === null) {
            $this->json->error('Unauthorized', 401);
        }

        $env = strtolower((string) env('APP_ENV', 'development'));
        if ($env !== 'development' && (string) env('APP_ALLOW_ANALYTICS_SEED', '') !== '1') {
            $this->json->error('Analytics seed is only available in development', 403);
        }

        $this->analyticsService->generateDemoData((int) $user['id']);
        $this->json->success(null, 'Demo analytics data generated');
    }
}
