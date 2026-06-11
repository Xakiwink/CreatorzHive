<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Covers queue dispatch/cancel and exponential backoff timing used by the scheduler/cron pipeline.
 */
final class SchedulerServiceTest extends TestCase
{
    public function testDispatchCreatesJobQueueEntry(): void
    {
        try {
            db_fetch('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available');
        }

        $before = (int) (db_fetch('SELECT COUNT(*) AS c FROM job_queue')['c'] ?? 0);
        $id = job_runner_dispatch('publish_post', ['post_id' => 999999], 'default', 0);
        $this->assertGreaterThan(0, $id);
        $after = (int) (db_fetch('SELECT COUNT(*) AS c FROM job_queue')['c'] ?? 0);
        $this->assertSame($before + 1, $after);

        db_delete('job_queue', 'id = :id', ['id' => $id]);
    }

    public function testCancelSetsJobStatusToFailed(): void
    {
        try {
            db_fetch('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available');
        }

        $id = job_runner_dispatch('publish_post', ['post_id' => 888888], 'default', 0);
        $this->assertTrue(job_runner_cancel((int) $id));
        $row = db_fetch('SELECT status FROM job_queue WHERE id = :id', ['id' => $id]);
        $this->assertSame('failed', $row['status'] ?? '');
        db_delete('job_queue', 'id = :id', ['id' => $id]);
    }

    public function testExponentialBackoffCalculation(): void
    {
        $this->assertSame(300, job_runner_backoff_seconds(1));
        $this->assertSame(900, job_runner_backoff_seconds(2));
        $this->assertSame(2700, job_runner_backoff_seconds(3));
    }
}
