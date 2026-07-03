<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__, 2));

require ROOT . '/vendor/autoload.php';

use App\Core\{Env, DB};
use App\Models\{JobQueue, Post, SocialAccount};
use App\Services\Instagram;

Env::load(ROOT . '/.env');

header('Content-Type: application/json');

$secret  = $_GET['secret'] ?? $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
$allowed = Env::get('WEBHOOK_SECRET', 'dev-secret-key');

if ($secret !== $allowed) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$jobs      = JobQueue::pending(10);
$processed = 0;
$success   = 0;
$failed    = 0;
$errors    = [];

foreach ($jobs as $job) {
    $id      = (int) $job['id'];
    $payload = json_decode($job['payload'] ?? '{}', true) ?: [];

    JobQueue::markRunning($id);
    $processed++;

    try {
        if ($job['job'] === 'publish_post') {
            handlePublishPost($payload);
        } else {
            throw new RuntimeException('Unknown job: ' . $job['job']);
        }
        JobQueue::markDone($id);
        $success++;
    } catch (Throwable $e) {
        JobQueue::markFailed($id, $e->getMessage());
        $errors[] = 'Job #' . $id . ': ' . $e->getMessage();
        $failed++;
    }
}

echo json_encode([
    'success'   => true,
    'processed' => $processed,
    'ok'        => $success,
    'failed'    => $failed,
    'errors'    => $errors,
    'ts'        => date('c'),
]);

function handlePublishPost(array $payload): void
{
    $postId = (int) ($payload['post_id'] ?? 0);
    if ($postId === 0) throw new RuntimeException('Missing post_id');

    $post = DB::fetch('SELECT * FROM posts WHERE id = ?', [$postId]);
    if (!$post) throw new RuntimeException('Post not found: ' . $postId);
    if ($post['status'] !== 'scheduled') {
        throw new RuntimeException('Post not in scheduled state: ' . $post['status']);
    }

    $platforms = json_decode($post['platforms'] ?? '[]', true) ?: [];

    if (in_array('instagram', $platforms, true)) {
        $userId  = (int) $post['user_id'];
        $account = SocialAccount::find($userId, 'instagram');
        if (!$account) throw new RuntimeException('No Instagram account for user ' . $userId);

        $token    = (string) ($account['access_token'] ?? '');
        $igUserId = (string) ($account['platform_user_id'] ?? '');
        $imageUrl = (string) ($post['media_url'] ?? '');

        if ($imageUrl === '') throw new RuntimeException('Post has no media_url for Instagram publish');

        $result = Instagram::publish($token, $igUserId, $imageUrl, (string) $post['caption']);
        if (!$result['ok']) throw new RuntimeException('Instagram publish failed: ' . $result['error']);
    }

    Post::markPublished($postId);
}
