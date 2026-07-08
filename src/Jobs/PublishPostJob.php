<?php

declare(strict_types=1);

namespace CreatorzHive\Jobs;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Repositories\AnalyticsRepository;
use CreatorzHive\Repositories\PostRepository;
use CreatorzHive\Repositories\SocialAccountRepository;
use CreatorzHive\Services\NotificationService;
use CreatorzHive\Services\SocialApiService;
use RuntimeException;
use function job_runner_dispatch;

final class PublishPostJob implements JobHandlerInterface
{
    /** @var PostRepository */
    private $posts;

    /** @var SocialAccountRepository */
    private $accounts;

    /** @var SocialApiService */
    private $socialApi;

    /** @var NotificationService */
    private $notifications;

    /** @var AnalyticsRepository */
    private $analytics;

    /** @var Connection */
    private $db;

    public function __construct(
        PostRepository $posts,
        SocialAccountRepository $accounts,
        SocialApiService $socialApi,
        NotificationService $notifications,
        AnalyticsRepository $analytics,
        Connection $db
    ) {
        $this->posts = $posts;
        $this->accounts = $accounts;
        $this->socialApi = $socialApi;
        $this->notifications = $notifications;
        $this->analytics = $analytics;
        $this->db = $db;
    }

    public function handle(array $payload): void
    {
        $postId = (int) ($payload['post_id'] ?? 0);
        if ($postId < 1) {
            throw new RuntimeException('Invalid post_id');
        }

        $post = $this->posts->findById($postId);
        if ($post === null) {
            throw new RuntimeException('Post not found');
        }

        if ((int) ($post['is_deleted'] ?? 0) === 1) {
            throw new RuntimeException('Post deleted');
        }

        if (($post['status'] ?? '') !== 'scheduled') {
            return;
        }

        $userId = (int) $post['user_id'];
        $platforms = $post['platforms'] ?? [];
        if (!is_array($platforms)) {
            $platforms = [];
        }

        $successCount = 0;
        $failCount = 0;
        $firstError = '';

        foreach ($platforms as $plat) {
            $platform = is_string($plat) ? strtolower(trim($plat)) : '';
            if ($platform === '') {
                continue;
            }

            $account = $this->accounts->accountFetch($userId, $platform, true);

            if ($account !== null && $platform === 'instagram') {
                $expiresAt = (string) ($account['token_expires_at'] ?? '');
                if ($expiresAt !== '' && strtotime($expiresAt) < strtotime('+7 days')) {
                    $refreshed = $this->socialApi->refreshToken($account);
                    if (!empty($refreshed['success']) && ($refreshed['access_token'] ?? '') !== '') {
                        $this->accounts->accountUpsert($userId, [
                            'platform' => (string) $account['platform'],
                            'platform_user_id' => (string) ($account['platform_user_id'] ?? ''),
                            'username' => (string) ($account['username'] ?? ''),
                            'display_name' => (string) ($account['display_name'] ?? ''),
                            'avatar_url' => $account['avatar_url'] ?? null,
                            'access_token' => (string) $refreshed['access_token'],
                            'refresh_token' => (string) ($account['refresh_token'] ?? ''),
                            'token_expires_at' => date('Y-m-d H:i:s', strtotime('+55 days')),
                            'follower_count' => (int) ($account['follower_count'] ?? 0),
                        ]);
                        $account = $this->accounts->accountFetch($userId, $platform, true) ?? $account;
                    }
                }
            }

            if ($account === null) {
                $this->db->insert('platform_post_results', [
                    'post_id' => $postId,
                    'social_account_id' => null,
                    'platform' => $platform,
                    'platform_post_id' => null,
                    'platform_url' => null,
                    'status' => 'failed',
                    'error_message' => 'No connected account for ' . $platform,
                    'published_at' => null,
                ]);
                $failCount++;
                if ($firstError === '') {
                    $firstError = 'No connected account for ' . $platform;
                }

                continue;
            }

            $result = $this->socialApi->publish($account, $post);
            $sid = (int) ($account['id'] ?? 0);

            if (!empty($result['success'])) {
                $resultId = $this->db->insert('platform_post_results', [
                    'post_id' => $postId,
                    'social_account_id' => $sid,
                    'platform' => $platform,
                    'platform_post_id' => (string) ($result['platform_post_id'] ?? ''),
                    'platform_url' => isset($result['platform_url']) ? (string) $result['platform_url'] : null,
                    'status' => 'success',
                    'error_message' => null,
                    'published_at' => now(),
                ]);
                if ($platform === 'instagram' || $platform === 'youtube') {
                    job_runner_dispatch(
                        'fetch_post_performance',
                        ['platform_post_result_id' => (int) $resultId],
                        'default',
                        1800
                    );
                }
                $successCount++;
            } else {
                $err = (string) ($result['error'] ?? 'Publish failed');
                $this->db->insert('platform_post_results', [
                    'post_id' => $postId,
                    'social_account_id' => $sid,
                    'platform' => $platform,
                    'platform_post_id' => null,
                    'platform_url' => null,
                    'status' => 'failed',
                    'error_message' => $err,
                    'published_at' => null,
                ]);
                $failCount++;
                if ($firstError === '') {
                    $firstError = $err;
                }
            }
        }

        if ($platforms === []) {
            $this->posts->save($postId, [
                'status' => 'failed',
                'published_at' => null,
            ]);
            $this->notifications->postFailed($userId, (string) $post['title'], 'No platforms selected');
            $this->analytics->recalculate($userId);

            return;
        }

        if ($successCount === 0) {
            $this->posts->save($postId, [
                'status' => 'failed',
                'published_at' => null,
            ]);
            $this->notifications->postFailed(
                $userId,
                (string) $post['title'],
                $firstError !== '' ? $firstError : 'All platforms failed'
            );
        } else {
            $this->posts->save($postId, [
                'status' => 'published',
                'published_at' => now(),
            ]);
            $this->notifications->postPublished($userId, (string) $post['title'], $postId);
        }

        $this->analytics->recalculate($userId);
    }
}
