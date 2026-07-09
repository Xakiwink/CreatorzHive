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
use function env;
use function job_runner_dispatch;
use function upload_url_needs_public_segment;

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

        $post = $this->posts->getWithMedia($postId);
        if ($post === null) {
            throw new RuntimeException('Post not found');
        }

        if ((int) ($post['is_deleted'] ?? 0) === 1) {
            throw new RuntimeException('Post deleted');
        }

        if (($post['status'] ?? '') !== 'scheduled') {
            return;
        }

        // findById() only returns raw post columns -- cover_media_id is a
        // foreign key, not a URL. Resolve real, absolute URLs from the
        // attached media: an image for Instagram/TikTok, and separately a
        // video for YouTube -- these are not necessarily the same
        // attachment (a post can have a video plus a distinct cover
        // thumbnail), so pick each by its actual mime type rather than
        // assuming "cover" always means "the thing to upload".
        $media = $post['media'] ?? [];
        $coverMediaId = (int) ($post['cover_media_id'] ?? 0);
        $imageUrl = '';
        $videoUrl = '';
        $videoMime = '';
        foreach ($media as $m) {
            $mime = (string) ($m['mime_type'] ?? '');
            $url = (string) ($m['cdn_url'] ?? '');
            if ($url === '') {
                continue;
            }
            $isChosenCover = $coverMediaId > 0 && (int) ($m['id'] ?? 0) === $coverMediaId;
            if (strpos($mime, 'image/') === 0 && ($imageUrl === '' || $isChosenCover)) {
                $imageUrl = $url;
            }
            if (strpos($mime, 'video/') === 0 && $videoUrl === '') {
                $videoUrl = $url;
                $videoMime = $mime;
            }
        }
        if ($imageUrl === '' && $videoUrl === '' && $media !== []) {
            // Nothing matched by mime type (missing/unrecognized mime) --
            // fall back to whichever file was chosen as cover, or the first.
            foreach ($media as $m) {
                if ($coverMediaId > 0 && (int) ($m['id'] ?? 0) === $coverMediaId) {
                    $imageUrl = (string) ($m['cdn_url'] ?? '');
                    break;
                }
            }
            if ($imageUrl === '') {
                $imageUrl = (string) ($media[0]['cdn_url'] ?? '');
            }
        }
        $post['cover_url'] = $this->toAbsoluteUploadUrl($imageUrl);
        $post['video_url'] = $this->toAbsoluteUploadUrl($videoUrl);
        $post['video_mime'] = $videoMime;

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
            $this->analytics->recalculate($userId);
            $this->notifyFailedSafely($userId, (string) $post['title'], 'No platforms selected');

            return;
        }

        if ($successCount === 0) {
            $this->posts->save($postId, [
                'status' => 'failed',
                'published_at' => null,
            ]);
            $this->analytics->recalculate($userId);
            $this->notifyFailedSafely(
                $userId,
                (string) $post['title'],
                $firstError !== '' ? $firstError : 'All platforms failed'
            );
        } else {
            $this->posts->save($postId, [
                'status' => 'published',
                'published_at' => now(),
            ]);
            $this->analytics->recalculate($userId);
            $this->notifyPublishedSafely($userId, (string) $post['title'], $postId);
        }
    }

    // A notification failure (e.g. mail delivery) must never look like a
    // publish failure -- the post is already correctly saved and the
    // analytics summary already recalculated by this point either way.
    private function notifyPublishedSafely(int $userId, string $title, int $postId): void
    {
        try {
            $this->notifications->postPublished($userId, $title, $postId);
        } catch (\Throwable $e) {
            // Publishing itself already succeeded; a notification hiccup shouldn't fail the job.
        }
    }

    private function notifyFailedSafely(int $userId, string $title, string $reason): void
    {
        try {
            $this->notifications->postFailed($userId, $title, $reason);
        } catch (\Throwable $e) {
            // Nothing more useful to do here -- the post's own status already reflects the failure.
        }
    }

    // media_files.cdn_url is stored relative (e.g. "uploads/x.jpg") -- fine
    // for an <img> tag resolved against the page origin, but the platform
    // APIs fetch/upload this URL themselves server-side, so it must be a
    // fully-qualified public URL, not a relative/root-relative path.
    private function toAbsoluteUploadUrl(string $relativeOrAbsolute): string
    {
        if ($relativeOrAbsolute === '' || preg_match('#^https?://#i', $relativeOrAbsolute) === 1) {
            return $relativeOrAbsolute;
        }

        $relative = ltrim($relativeOrAbsolute, '/');
        if (upload_url_needs_public_segment() && strpos($relative, 'public/') !== 0) {
            $relative = 'public/' . $relative;
        }

        return rtrim((string) env('APP_URL', 'http://localhost'), '/') . '/' . $relative;
    }
}
