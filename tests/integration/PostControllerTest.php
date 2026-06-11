<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTestCase;

final class PostControllerTest extends IntegrationTestCase
{
    private function loginAsDavid(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'david@creatorzhive.com',
            'password' => 'Creator@1234',
        ]);
    }

    private function loginAsBrand(): void
    {
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $this->dispatchRoute('POST', 'login', [
            'email' => 'brand@creatorzhive.com',
            'password' => 'Brand@1234',
        ]);
    }

    public function testCreatePostAsAuthenticatedUser(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $suffix = bin2hex(random_bytes(3));
        $res = $this->dispatchRoute('POST', 'create_post', [
            'title' => 'Integration Post ' . $suffix,
            'content' => 'Body content for test.',
            'status' => 'draft',
        ]);

        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success'] ?? false);
        $id = (int) (($res->payload['data']['id'] ?? 0));
        $this->assertGreaterThan(0, $id);
        db_delete('posts', 'id = :id', ['id' => $id]);
    }

    public function testCreatePostAsUnauthenticatedUserReturns401(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        $res = $this->dispatchRoute('POST', 'create_post', [
            'title' => 'No auth',
            'content' => 'Should fail',
            'status' => 'draft',
        ]);

        $this->assertSame(401, $res->httpStatus);
    }

    public function testUpdatePostBelongingToAnotherUserReturns403(): void
    {
        $this->requireDatabase();

        $david = user_find_by_email('david@creatorzhive.com');
        $this->assertNotNull($david);
        $row = db_fetch(
            'SELECT id FROM posts WHERE user_id = :uid AND is_deleted = 0 LIMIT 1',
            ['uid' => (int) $david['id']]
        );
        if ($row === null) {
            $this->markTestSkipped('No posts for David in database; run seeds.');
        }
        $postId = (int) $row['id'];

        $this->loginAsBrand();

        $res = $this->dispatchRoute('POST', 'update_post', [
            'id' => $postId,
            'title' => 'Hacked title',
            'content' => 'Hacked content',
            'status' => 'draft',
        ]);

        $this->assertSame(403, $res->httpStatus);
    }

    public function testSoftDeleteSetsIsDeletedFlag(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $suffix = bin2hex(random_bytes(3));
        $created = $this->dispatchRoute('POST', 'create_post', [
            'title' => 'To delete ' . $suffix,
            'content' => 'Will be soft deleted.',
            'status' => 'draft',
        ]);
        $postId = (int) (($created->payload['data']['id'] ?? 0));
        $this->assertGreaterThan(0, $postId);

        $del = $this->dispatchRoute('POST', 'delete_post', [
            'post_id' => $postId,
        ]);
        $this->assertSame(200, $del->httpStatus);

        $check = db_fetch('SELECT is_deleted FROM posts WHERE id = :id', ['id' => $postId]);
        $this->assertSame(1, (int) ($check['is_deleted'] ?? 0));

        db_delete('posts', 'id = :id', ['id' => $postId]);
    }

    public function testScheduledPostCreatesJobQueueEntry(): void
    {
        $this->requireDatabase();
        $this->loginAsDavid();

        $suffix = bin2hex(random_bytes(3));
        $when = date('Y-m-d H:i:s', strtotime('+2 days'));

        $res = $this->dispatchRoute('POST', 'create_post', [
            'title' => 'Scheduled ' . $suffix,
            'content' => 'Scheduled body.',
            'status' => 'scheduled',
            'scheduled_at' => $when,
        ]);

        $this->assertSame(200, $res->httpStatus);
        $postId = (int) (($res->payload['data']['id'] ?? 0));
        $this->assertGreaterThan(0, $postId);

        $publishType = job_queue_publish_job_type();
        $jq = db_fetch(
            'SELECT id FROM job_queue WHERE job_class = :jc AND JSON_UNQUOTE(JSON_EXTRACT(payload, \'$.post_id\')) = :pid AND status = :st LIMIT 1',
            [
                'jc' => $publishType,
                'pid' => (string) $postId,
                'st' => 'pending',
            ]
        );
        $this->assertNotNull($jq);

        db_delete('job_queue', 'JSON_UNQUOTE(JSON_EXTRACT(payload, \'$.post_id\')) = :pid', ['pid' => (string) $postId]);
        db_delete('posts', 'id = :id', ['id' => $postId]);
    }
}
