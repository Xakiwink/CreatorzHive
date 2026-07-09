<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Repositories\AnalyticsRepository;
use CreatorzHive\Repositories\DashboardRepository;

/**
 * Streak and badge computation, derived entirely from data already stored
 * in posts/analytics_snapshots/analytics/social_accounts/creator_scores --
 * no dedicated table, same "compute live from real history" approach as
 * CreatorScoreService::computePlatformHealth().
 */
final class AchievementService
{
    private const PUBLISH_TIERS = [1, 10, 50];
    private const STREAK_TIERS = [2, 4, 8];
    private const FOLLOWER_TIERS = [100, 1000, 10000];
    private const RISING_STAR_SCORE = 70.0;

    /** @var AnalyticsRepository */
    private $analytics;

    /** @var DashboardRepository */
    private $dashboard;

    /** @var CreatorScoreService */
    private $scores;

    public function __construct(AnalyticsRepository $analytics, DashboardRepository $dashboard, CreatorScoreService $scores)
    {
        $this->analytics = $analytics;
        $this->dashboard = $dashboard;
        $this->scores = $scores;
    }

    /**
     * @return array{streak: array{current_weeks: int, longest_weeks: int}, badges: list<array<string, mixed>>}
     */
    public function getAchievements(int $userId): array
    {
        $streak = $this->computeStreak($userId);
        $summary = $this->dashboard->findCreatorSummary($userId);
        $publishedPosts = (int) ($summary['published_posts'] ?? 0);
        $connectedPlatforms = count($this->dashboard->findActiveSocialAccounts($userId));
        $maxFollowers = $this->analytics->getMaxFollowersEver($userId);
        $creatorScore = $this->scores->getScores($userId);
        $revenue = (float) ($summary['total_revenue'] ?? 0);

        $badges = [];

        $badges[] = $this->simpleBadge(
            'connect_platform',
            'Getting started',
            'Connected a platform',
            'Linked your first social account.',
            'link',
            $connectedPlatforms >= 1
        );
        $badges[] = $this->simpleBadge(
            'multi_platform',
            'Getting started',
            'Multi-platform creator',
            'Connected 2 or more platforms.',
            'link',
            $connectedPlatforms >= 2
        );

        foreach ($this->tierBadges(
            'published_posts',
            'Publishing',
            'Published %d posts',
            'Published %d posts through CreatorzHive.',
            'post',
            $publishedPosts,
            self::PUBLISH_TIERS
        ) as $b) {
            $badges[] = $b;
        }

        foreach ($this->tierBadges(
            'streak_weeks',
            'Consistency',
            '%d-week streak',
            'Published at least once a week for %d weeks in a row.',
            'flame',
            $streak['longest_weeks'],
            self::STREAK_TIERS
        ) as $b) {
            $badges[] = $b;
        }

        foreach ($this->tierBadges(
            'followers',
            'Growth',
            '%s followers',
            'Reached %s total followers across your connected platforms.',
            'users',
            $maxFollowers,
            self::FOLLOWER_TIERS,
            true
        ) as $b) {
            $badges[] = $b;
        }

        $badges[] = $this->simpleBadge(
            'rising_star',
            'Momentum',
            'Rising star',
            'Your Creator Score is currently 70 or higher.',
            'trophy',
            $creatorScore['available'] === true && (float) $creatorScore['creator_score'] >= self::RISING_STAR_SCORE
        );

        $badges[] = $this->simpleBadge(
            'first_deal',
            'Monetization',
            'Landed a paid deal',
            'Recorded your first deal revenue.',
            'dollar',
            $revenue > 0
        );

        return [
            'streak' => $streak,
            'badges' => $badges,
        ];
    }

    /**
     * @return array{current_weeks: int, longest_weeks: int}
     */
    private function computeStreak(int $userId): array
    {
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-52 weeks'));
        $rows = $this->analytics->getPostingFrequency($userId, 'weekly', $start, $end);

        $weeks = [];
        foreach ($rows as $r) {
            if ((int) ($r['cnt'] ?? 0) > 0) {
                $weeks[(string) $r['bucket']] = true;
            }
        }

        if ($weeks === []) {
            return ['current_weeks' => 0, 'longest_weeks' => 0];
        }

        $currentWeeks = 0;
        $cursor = (int) date('oW');
        while (isset($weeks[(string) $cursor])) {
            $currentWeeks++;
            $cursor = $this->previousYearWeek($cursor);
        }

        $ordered = array_keys($weeks);
        sort($ordered, SORT_STRING);
        $longestWeeks = 1;
        $run = 1;
        for ($i = 1, $n = count($ordered); $i < $n; $i++) {
            $expectedNext = $this->nextYearWeek((int) $ordered[$i - 1]);
            if ($expectedNext === (int) $ordered[$i]) {
                $run++;
            } else {
                $run = 1;
            }
            $longestWeeks = max($longestWeeks, $run);
        }

        return [
            'current_weeks' => $currentWeeks,
            'longest_weeks' => max($longestWeeks, $currentWeeks),
        ];
    }

    private function nextYearWeek(int $yearWeek): int
    {
        $year = intdiv($yearWeek, 100);
        $week = $yearWeek % 100;
        $week++;
        $weeksInYear = (int) date('W', strtotime(sprintf('%d-12-28', $year)));
        if ($week > $weeksInYear) {
            $week = 1;
            $year++;
        }

        return $year * 100 + $week;
    }

    private function previousYearWeek(int $yearWeek): int
    {
        $year = intdiv($yearWeek, 100);
        $week = $yearWeek % 100;
        $week--;
        if ($week < 1) {
            $year--;
            $week = (int) date('W', strtotime(sprintf('%d-12-28', $year)));
        }

        return $year * 100 + $week;
    }

    /**
     * @param list<int> $tiers
     * @return list<array<string, mixed>>
     */
    private function tierBadges(
        string $idPrefix,
        string $category,
        string $labelFormat,
        string $descriptionFormat,
        string $icon,
        int $currentValue,
        array $tiers,
        bool $formatThousands = false
    ): array {
        $out = [];
        foreach ($tiers as $target) {
            $unlocked = $currentValue >= $target;
            $display = $formatThousands ? number_format($target) : (string) $target;
            $out[] = [
                'id' => $idPrefix . '_' . $target,
                'category' => $category,
                'label' => sprintf($labelFormat, $formatThousands ? $display : $target),
                'description' => sprintf($descriptionFormat, $formatThousands ? $display : $target),
                'icon' => $icon,
                'unlocked' => $unlocked,
                'progress' => $unlocked ? null : ['current' => min($currentValue, $target), 'target' => $target],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function simpleBadge(
        string $id,
        string $category,
        string $label,
        string $description,
        string $icon,
        bool $unlocked
    ): array {
        return [
            'id' => $id,
            'category' => $category,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'unlocked' => $unlocked,
            'progress' => null,
        ];
    }
}
