<?php

declare(strict_types=1);

namespace CreatorzHive\Jobs;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\SocialAccountRepository;
use CreatorzHive\Services\AnalyticsService;
use CreatorzHive\Services\SocialApiService;
use RuntimeException;

final class FetchAnalyticsJob implements JobHandlerInterface
{
    /** @var SocialAccountRepository */
    private $accounts;

    /** @var SocialApiService */
    private $socialApi;

    /** @var AnalyticsService */
    private $analytics;

    /** @var Connection */
    private $db;

    public function __construct(
        SocialAccountRepository $accounts,
        SocialApiService $socialApi,
        AnalyticsService $analytics,
        Connection $db
    ) {
        $this->accounts = $accounts;
        $this->socialApi = $socialApi;
        $this->analytics = $analytics;
        $this->db = $db;
    }

    public function handle(array $payload): void
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $accountId = (int) ($payload['social_account_id'] ?? 0);
        if ($userId < 1 || $accountId < 1) {
            throw new RuntimeException('user_id and social_account_id required');
        }

        $account = $this->accounts->accountFetchById($accountId, $userId);
        if ($account === null) {
            throw new RuntimeException('Social account not found');
        }

        $today = date('Y-m-d');
        $platform = (string) ($account['platform'] ?? '');
        $metrics = $this->socialApi->getAnalytics($account, $today);

        $followers = (int) ($metrics['followers'] ?? 0);

        $this->db->query(
            'INSERT INTO analytics_snapshots (
                user_id, social_account_id, platform, snapshot_date, period,
                followers, impressions, reach, likes, comments, shares, saves,
                link_clicks, profile_visits, engagement_rate
            ) VALUES (
                :uid, :said, :plat, :dt, \'daily\',
                :fol, :imp, :reach, :likes, :comments, :shares, :saves,
                :clicks, :pvis, :erate
            )
            ON DUPLICATE KEY UPDATE
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
                'said' => $accountId,
                'plat' => $platform,
                'dt' => $today,
                'fol' => $followers,
                'imp' => (int) ($metrics['impressions'] ?? 0),
                'reach' => (int) ($metrics['reach'] ?? 0),
                'likes' => (int) ($metrics['likes'] ?? 0),
                'comments' => (int) ($metrics['comments'] ?? 0),
                'shares' => (int) ($metrics['shares'] ?? 0),
                'saves' => (int) ($metrics['saves'] ?? 0),
                'clicks' => (int) (($metrics['likes'] ?? 0) * 0.03),
                'pvis' => (int) (($metrics['reach'] ?? 0) * 0.01),
                'erate' => (float) ($metrics['engagement_rate'] ?? 0),
            ]
        );

        if ($followers > 0) {
            $this->db->update(
                'social_accounts',
                ['follower_count' => $followers],
                'id = :id',
                ['id' => $accountId]
            );
        }

        $this->analytics->aggregateTotals($userId);
    }
}
