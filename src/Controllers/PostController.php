<?php

declare(strict_types=1);

namespace CreatorzHive\Controllers;

use CreatorzHive\Controllers\Support\AbstractController;
use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Http\JsonResponder;
use CreatorzHive\Core\Http\ViewRenderer;
use CreatorzHive\Repositories\AnalyticsRepository;
use CreatorzHive\Repositories\JobQueueRepository;
use CreatorzHive\Repositories\MediaFileRepository;
use CreatorzHive\Repositories\PostRepository;
use CreatorzHive\Services\NotificationService;
use CreatorzHive\Support\PostInputNormalizer;
use function now;
use function request_all;
use function request_get;
use function request_json_body;
use function session_get_user;
use function upload_url;
use function validator_validate;

final class PostController extends AbstractController
{
    /** @var PostRepository */
    private $posts;

    /** @var JobQueueRepository */
    private $jobQueue;

    /** @var MediaFileRepository */
    private $media;

    /** @var AnalyticsRepository */
    private $analytics;

    /** @var NotificationService */
    private $notifications;

    /** @var PostInputNormalizer */
    private $input;

    public function __construct(
        ViewRenderer $views,
        JsonResponder $json,
        Connection $db,
        PostRepository $posts,
        JobQueueRepository $jobQueue,
        MediaFileRepository $media,
        AnalyticsRepository $analytics,
        NotificationService $notifications,
        PostInputNormalizer $input
    ) {
        parent::__construct($views, $json, $db);
        $this->posts = $posts;
        $this->jobQueue = $jobQueue;
        $this->media = $media;
        $this->analytics = $analytics;
        $this->notifications = $notifications;
        $this->input = $input;
    }

    public function plannerPage()
    {
        $this->views->render('planner/index');
    }

    public function index()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $filters = [
                    'status' => (string) request_get('status', ''),
                    'platform' => (string) request_get('platform', ''),
                    'date_from' => (string) request_get('date_from', ''),
                    'date_to' => (string) request_get('date_to', ''),
                    'search' => (string) request_get('search', ''),
                    'sort' => (string) request_get('sort', 'date'),
                    'dir' => (string) request_get('dir', 'desc'),
                    'page' => (int) request_get('page', 1),
                    'per_page' => (int) request_get('per_page', 10),
                ];
        
                $result = $this->posts->getAllByUser((int) $user['id'], $filters);
                foreach ($result['posts'] as $i => $p) {
                    $cu = (string) ($p['cover_url'] ?? '');
                    if ($cu !== '' && strpos($cu, 'http') !== 0) {
                        $result['posts'][$i]['cover_url'] = upload_url(ltrim($cu, '/'));
                    }
                    $ct = (string) ($p['cover_thumb'] ?? '');
                    if ($ct !== '' && strpos($ct, 'http') !== 0) {
                        $result['posts'][$i]['cover_thumb'] = upload_url(ltrim($ct, '/'));
                    }
                }
        
                $this->json->success($result, 'Posts loaded');
    }

    public function calendar()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $month = (string) request_get('month', date('n'));
                $year = (string) request_get('year', date('Y'));
                $posts = $this->posts->getCalendarPosts((int) $user['id'], $month, $year);
        
                $byDate = [];
                foreach ($posts as $p) {
                    $d = (string) ($p['calendar_date'] ?? '');
                    if ($d === '') {
                        continue;
                    }
                    if (!isset($byDate[$d])) {
                        $byDate[$d] = [];
                    }
                    $byDate[$d][] = $p;
                }
        
                $this->json->success([
                    'dates' => $byDate,
                    'month' => (int) $month,
                    'year' => (int) $year,
                ], 'Calendar loaded');
    }

    public function show()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $id = (int) request_get('id', 0);
                if ($id < 1) {
                    $this->json->error('Invalid post', 422);
                }
        
                $post = $this->posts->getFullForUser($id, (int) $user['id']);
                if ($post === null) {
                    $this->json->error('Post not found', 404);
                }
        
                foreach ($post['media'] as $i => $m) {
                    $cdn = (string) ($m['cdn_url'] ?? '');
                    if ($cdn !== '' && strpos($cdn, 'http') !== 0) {
                        $post['media'][$i]['cdn_url'] = upload_url(ltrim($cdn, '/'));
                    }
                    $th = (string) ($m['thumbnail_url'] ?? '');
                    if ($th !== '' && strpos($th, 'http') !== 0) {
                        $post['media'][$i]['thumbnail_url'] = upload_url(ltrim($th, '/'));
                    }
                }
        
                $this->json->success($post, 'Post loaded');
    }

    public function store()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $payload = array_merge(request_all(), request_json_body());
                if (!isset($payload['status']) || trim((string) $payload['status']) === '') {
                    $payload['status'] = 'draft';
                }
        
                $platforms = $this->input->normalizePlatforms($payload['platforms'] ?? null);
        
                $validation = validator_validate($payload, [
                    'title' => 'required|max:255',
                    'content' => 'required|max:10000',
                    'status' => 'in:draft,scheduled,published',
                ]);
        
                if (!$validation['valid']) {
                    $this->json->error('Validation failed', 422, $validation['errors']);
                }
        
                $status = (string) $payload['status'];
                $scheduledAt = null;
                $publishedAt = null;
        
                if ($status === 'scheduled') {
                    $scheduledAt = isset($payload['scheduled_at']) ? trim((string) $payload['scheduled_at']) : '';
                    if ($scheduledAt === '') {
                        $this->json->error('Scheduled time is required for scheduled posts', 422);
                    }
                    $ts = strtotime($scheduledAt);
                    if ($ts === false) {
                        $this->json->error('Invalid scheduled time', 422);
                    }
                    $scheduledAt = date('Y-m-d H:i:s', $ts);
                } elseif ($status === 'published') {
                    $publishedAt = now();
                }
        
                $userId = (int) $user['id'];
                $mediaIds = $this->input->normalizeIdList($payload['media_ids'] ?? null);
                $tagIds = $this->input->normalizeIdList($payload['tag_ids'] ?? null);
                $coverMediaId = !empty($mediaIds) ? (int) $mediaIds[0] : null;
                if ($coverMediaId !== null && $this->media->findByIdForUser($coverMediaId, $userId) === null) {
                    $this->json->error('Invalid media attachment', 422);
                }
        
                $postId = $this->posts->create([
                    'user_id' => $userId,
                    'title' => (string) $payload['title'],
                    'content' => (string) $payload['content'],
                    'caption' => isset($payload['caption']) ? (string) $payload['caption'] : null,
                    'platforms' => $platforms,
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'published_at' => $publishedAt,
                    'cover_media_id' => $coverMediaId,
                ]);
        
                $this->posts->syncMedia($postId, $mediaIds);
                $this->input->syncPostTags($postId, $tagIds, $userId);
        
                if ($status === 'scheduled') {
                    $this->jobQueue->queueEnqueuePublishPost($postId);
                }
        
                $this->analytics->recalculate($userId);
        
                if ($status === 'published') {
                    $this->notifications->postPublished($userId, (string) $payload['title'], $postId);
                }
        
                $post = $this->posts->getFullForUser($postId, $userId);
                $this->json->success(['id' => $postId, 'post' => $post], 'Post created successfully');
    }

    public function update()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $payload = array_merge(request_all(), request_json_body());
                $postId = (int) ($payload['id'] ?? $payload['post_id'] ?? 0);
                if ($postId < 1) {
                    $this->json->error('Invalid post', 422);
                }
        
                $row = $this->posts->findById($postId);
                if ($row === null) {
                    $this->json->error('Post not found', 404);
                }
                if ((int) ($row['user_id'] ?? 0) !== (int) $user['id']) {
                    $this->json->error('You do not have access to this post', 403);
                }
                $existing = $row;
        
                $platforms = $this->input->normalizePlatforms($payload['platforms'] ?? null);
        
                $validation = validator_validate($payload, [
                    'title' => 'required|max:255',
                    'content' => 'required|max:10000',
                    'status' => 'in:draft,scheduled,published',
                ]);
        
                if (!$validation['valid']) {
                    $this->json->error('Validation failed', 422, $validation['errors']);
                }
        
                $status = (string) $payload['status'];
                $scheduledAt = null;
                $publishedAt = null;
        
                if ($status === 'scheduled') {
                    $scheduledAt = isset($payload['scheduled_at']) ? trim((string) $payload['scheduled_at']) : '';
                    if ($scheduledAt === '') {
                        $this->json->error('Scheduled time is required for scheduled posts', 422);
                    }
                    $ts = strtotime($scheduledAt);
                    if ($ts === false) {
                        $this->json->error('Invalid scheduled time', 422);
                    }
                    $scheduledAt = date('Y-m-d H:i:s', $ts);
                } elseif ($status === 'published') {
                    $publishedAt = $existing['published_at'] ?? null;
                    if ($publishedAt === null || $publishedAt === '') {
                        $publishedAt = now();
                    }
                }
        
                if ($status === 'draft') {
                    $scheduledAt = null;
                    $publishedAt = null;
                }
        
                $this->jobQueue->queueCancelPendingPublishForPost($postId);
        
                $userId = (int) $user['id'];
                $mediaIds = $this->input->normalizeIdList($payload['media_ids'] ?? null);
                $tagIds = $this->input->normalizeIdList($payload['tag_ids'] ?? null);
                $coverMediaId = !empty($mediaIds) ? (int) $mediaIds[0] : null;
                if ($coverMediaId !== null && $this->media->findByIdForUser($coverMediaId, $userId) === null) {
                    $this->json->error('Invalid media attachment', 422);
                }
        
                $update = [
                    'title' => (string) $payload['title'],
                    'content' => (string) $payload['content'],
                    'caption' => isset($payload['caption']) ? (string) $payload['caption'] : null,
                    'platforms' => $platforms,
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'published_at' => $publishedAt,
                    'cover_media_id' => $coverMediaId,
                ];
        
                $this->posts->save($postId, $update);
                $this->posts->syncMedia($postId, $mediaIds);
                $this->input->syncPostTags($postId, $tagIds, $userId);
        
                if ($status === 'scheduled') {
                    $this->jobQueue->queueEnqueuePublishPost($postId);
                }
        
                $this->analytics->recalculate($userId);
        
                $wasPublished = (string) ($existing['status'] ?? '') === 'published';
                if ($status === 'published' && !$wasPublished) {
                    $this->notifications->postPublished($userId, (string) $payload['title'], $postId);
                }
        
                $post = $this->posts->getFullForUser($postId, $userId);
                $this->json->success(['post' => $post], 'Post updated successfully');
    }

    public function destroy()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $payload = array_merge(request_all(), request_json_body());
                $postId = (int) ($payload['post_id'] ?? $payload['id'] ?? 0);
                if ($postId < 1) {
                    $this->json->error('Invalid post', 422);
                }
        
                $this->jobQueue->queueCancelPendingPublishForPost($postId);
        
                if (!$this->posts->softDelete($postId, (int) $user['id'])) {
                    $this->json->error('Post not found', 404);
                }
        
                $this->analytics->recalculate((int) $user['id']);
        
                $this->json->success(null, 'Post deleted successfully');
    }

    public function duplicate()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $payload = array_merge(request_all(), request_json_body());
                $postId = (int) ($payload['post_id'] ?? $payload['id'] ?? 0);
                if ($postId < 1) {
                    $this->json->error('Invalid post', 422);
                }
        
                $newId = $this->posts->duplicate($postId, (int) $user['id']);
                if ($newId < 1) {
                    $this->json->error('Post not found', 404);
                }
        
                $this->analytics->recalculate((int) $user['id']);
        
                $post = $this->posts->getFullForUser($newId, (int) $user['id']);
                $this->json->success(['id' => $newId, 'post' => $post], 'Post duplicated');
    }

    public function bulk()
    {
        $user = session_get_user();
                if ($user === null) {
                    $this->json->error('Unauthorized', 401);
                }
        
                $payload = array_merge(request_all(), request_json_body());
                $action = (string) ($payload['action'] ?? '');
                $ids = $this->input->normalizeIdList($payload['ids'] ?? null);
                if ($ids === []) {
                    $this->json->error('No posts selected', 422);
                }
        
                $userId = (int) $user['id'];
        
                if ($action === 'delete') {
                    foreach ($ids as $pid) {
                        $this->jobQueue->queueCancelPendingPublishForPost($pid);
                        $this->posts->softDelete($pid, $userId);
                    }
                    $this->analytics->recalculate($userId);
                    $this->json->success(null, 'Posts deleted');
        
                    return;
                }
        
                if ($action === 'status') {
                    $status = (string) ($payload['status'] ?? '');
                    if (!in_array($status, ['draft', 'scheduled', 'published', 'failed'], true)) {
                        $this->json->error('Invalid status', 422);
                    }
                    if ($status === 'scheduled') {
                        $this->json->error('Use the editor to schedule posts with a date', 422);
                    }
        
                    foreach ($ids as $pid) {
                        if ($this->posts->findByIdForUser($pid, $userId) === null) {
                            continue;
                        }
                        $this->jobQueue->queueCancelPendingPublishForPost($pid);
                        $extra = ['status' => $status];
                        if ($status === 'published') {
                            $extra['published_at'] = now();
                            $extra['scheduled_at'] = null;
                        }
                        if ($status === 'draft' || $status === 'failed') {
                            $extra['scheduled_at'] = null;
                            $extra['published_at'] = null;
                        }
                        $this->posts->save($pid, $extra);
                    }
        
                    $this->analytics->recalculate($userId);
                    $this->json->success(null, 'Posts updated');
        
                    return;
                }
        
                $this->json->error('Invalid action', 422);
    }

}
