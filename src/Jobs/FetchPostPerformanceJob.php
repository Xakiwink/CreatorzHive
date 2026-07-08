<?php

declare(strict_types=1);

namespace CreatorzHive\Jobs;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\SocialAccountRepository;
use CreatorzHive\Services\SocialApiService;
use RuntimeException;

final class FetchPostPerformanceJob implements JobHandlerInterface
{
    /** @var Connection */
    private $db;

    /** @var SocialAccountRepository */
    private $accounts;

    /** @var SocialApiService */
    private $socialApi;

    public function __construct(Connection $db, SocialAccountRepository $accounts, SocialApiService $socialApi)
    {
        $this->db = $db;
        $this->accounts = $accounts;
        $this->socialApi = $socialApi;
    }

    public function handle(array $payload): void
    {
        $resultId = (int) ($payload['platform_post_result_id'] ?? 0);
        if ($resultId < 1) {
            throw new RuntimeException('platform_post_result_id required');
        }

        $row = $this->db->fetchOne(
            'SELECT ppr.id, ppr.post_id, ppr.social_account_id, ppr.platform, ppr.platform_post_id, p.user_id
             FROM platform_post_results ppr
             INNER JOIN posts p ON p.id = ppr.post_id
             WHERE ppr.id = :id AND ppr.status = \'success\'
             LIMIT 1',
            ['id' => $resultId]
        );

        if ($row === null) {
            return;
        }

        $postId = (int) $row['post_id'];
        $platform = (string) $row['platform'];
        $platformPostId = (string) ($row['platform_post_id'] ?? '');
        $accountId = $row['social_account_id'] !== null ? (int) $row['social_account_id'] : 0;
        $userId = (int) $row['user_id'];

        $insights = ['available' => false];
        if ($accountId > 0 && $platformPostId !== '') {
            $account = $this->accounts->accountFetchById($accountId, $userId);
            if ($account !== null) {
                $insights = $this->socialApi->getPostInsights($account, $platformPostId);
            }
        }

        $this->store($resultId, $postId, $platform, $insights);
    }

    private function store(int $resultId, int $postId, string $platform, array $insights): void
    {
        $available = !empty($insights['available']);

        $this->db->query(
            'INSERT INTO post_performance (
                platform_post_result_id, post_id, platform, available,
                likes, comments, shares, saves, reach, engagement_rate, fetched_at
             ) VALUES (
                :rid, :pid, :plat, :avail,
                :likes, :comments, :shares, :saves, :reach, :erate, NOW()
             ) ON DUPLICATE KEY UPDATE
                available = VALUES(available),
                likes = VALUES(likes),
                comments = VALUES(comments),
                shares = VALUES(shares),
                saves = VALUES(saves),
                reach = VALUES(reach),
                engagement_rate = VALUES(engagement_rate),
                fetched_at = VALUES(fetched_at)',
            [
                'rid' => $resultId,
                'pid' => $postId,
                'plat' => $platform,
                'avail' => $available ? 1 : 0,
                'likes' => (int) ($insights['likes'] ?? 0),
                'comments' => (int) ($insights['comments'] ?? 0),
                'shares' => (int) ($insights['shares'] ?? 0),
                'saves' => (int) ($insights['saves'] ?? 0),
                'reach' => (int) ($insights['reach'] ?? 0),
                'erate' => (float) ($insights['engagement_rate'] ?? 0),
            ]
        );
    }
}
