<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class JobQueueRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function queuePublishJobType()
    {
        return 'publish_post';
    }

    public function queueLegacyPublishClass()
    {
        return 'App\\Jobs\\PublishPostJob';
    }

    public function queueEnqueuePublishPost(int $postId)
    {
        $this->db->insert('job_queue', [
                'queue' => 'default',
                'job_class' => $this->queuePublishJobType(),
                'payload' => json_encode(['post_id' => $postId]),
                'status' => 'pending',
                'available_at' => now(),
            ]);
    }

    public function queueCancelPendingPublishForPost(int $postId)
    {
        $types = [$this->queuePublishJobType(), $this->queueLegacyPublishClass()];
            foreach ($types as $jc) {
                $this->db->query(
                    'UPDATE job_queue SET status = :st, error_message = :msg, failed_at = NOW()
                     WHERE job_class = :jc AND status = :pend
                     AND JSON_UNQUOTE(JSON_EXTRACT(payload, \'$.post_id\')) = :pid',
                    [
                        'st' => 'failed',
                        'msg' => 'cancelled',
                        'jc' => $jc,
                        'pend' => 'pending',
                        'pid' => (string) $postId,
                    ]
                );
            }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $jobClass, array $payload, string $queue = 'default', int $delaySeconds = 0): int
    {
        $available = $delaySeconds > 0
            ? date('Y-m-d H:i:s', time() + $delaySeconds)
            : now();

        $id = $this->db->insert('job_queue', [
            'queue' => $queue,
            'job_class' => $jobClass,
            'payload' => json_encode($payload) ?: '{}',
            'status' => 'pending',
            'available_at' => $available,
        ]);

        return (int) $id;
    }

}
