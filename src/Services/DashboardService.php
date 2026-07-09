<?php

declare(strict_types=1);

namespace CreatorzHive\Services;



use function post_count_by_status;
use function post_get_recent_by_user;
use function post_get_upcoming;
use CreatorzHive\Repositories\DashboardRepository;

/**
 * Dashboard business \logic(SRP: dashboard use cases).
 */
final class DashboardService
{
    /** @var DashboardRepository */
    private $repository;

    /** @var CreatorScoreService */
    private $scores;

    /** @var AchievementService */
    private $achievements;

    /** @var list<string> */
    private const PLATFORM_SLUGS = ['instagram', 'tiktok', 'youtube', 'twitter'];

    public function __construct(DashboardRepository $repository, CreatorScoreService $scores, AchievementService $achievements)
    {
        $this->repository = $repository;
        $this->scores = $scores;
        $this->achievements = $achievements;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(int $userId): array
    {
        $summary = $this->repository->findCreatorSummary($userId);
        $achievements = $this->achievements->getAchievements($userId);

        $stats = [
            'total_posts' => (int) ($summary['total_posts'] ?? 0),
            'published_posts' => (int) ($summary['published_posts'] ?? 0),
            'scheduled_posts' => (int) ($summary['scheduled_posts'] ?? 0),
            'total_followers' => (int) ($summary['total_followers'] ?? 0),
            'avg_engagement_rate' => (float) ($summary['avg_engagement_rate'] ?? 0),
            'total_revenue' => (float) ($summary['total_revenue'] ?? 0),
            'active_deals' => (int) ($summary['active_deals'] ?? 0),
            'unread_notifications' => (int) ($summary['unread_notifications'] ?? 0),
            'trend_posts' => 0,
            'trend_published' => 0,
            'trend_scheduled' => 0,
            'trend_followers' => 0,
            'posting_streak_weeks' => (int) $achievements['streak']['current_weeks'],
        ];

        $recentPosts = \function_exists('post_get_recent_by_user')
            ? \post_get_recent_by_user($userId, 5)
            : [];
        $upcomingPosts = \function_exists('post_get_upcoming')
            ? \post_get_upcoming($userId, 5)
            : [];
        $breakdown = \function_exists('post_count_by_status')
            ? \post_count_by_status($userId)
            : [];

        $accounts = $this->repository->findActiveSocialAccounts($userId);
        $byPlatform = [];
        foreach ($accounts as $account) {
            $byPlatform[(string) $account['platform']] = $account;
        }

        $health = [];
        foreach ($this->scores->computePlatformHealth($userId) as $h) {
            $health[(string) $h['platform']] = $h;
        }

        $platformStatus = [];
        foreach (self::PLATFORM_SLUGS as $platform) {
            $acc = $byPlatform[$platform] ?? null;
            $h = $health[$platform] ?? null;
            $platformStatus[] = [
                'platform' => $platform,
                'connected' => $acc !== null,
                'username' => $acc['username'] ?? null,
                'health' => $h['status'] ?? 'unknown',
                'growth_pct' => $h['growth_pct'] ?? null,
            ];
        }

        return [
            'stats' => $stats,
            'scores' => $this->scores->getScores($userId),
            'recent_posts' => $recentPosts,
            'upcoming_posts' => $upcomingPosts,
            'platform_status' => $platformStatus,
            'post_status_breakdown' => $breakdown,
            'achievements' => $achievements,
        ];
    }
}
