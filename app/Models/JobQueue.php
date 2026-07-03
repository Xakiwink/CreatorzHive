<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class JobQueue
{
    public static function dispatch(string $job, array $payload = [], int $delaySeconds = 0): int
    {
        $availableAt = date('Y-m-d H:i:s', time() + $delaySeconds);

        return DB::insert('job_queue', [
            'job'          => $job,
            'payload'      => json_encode($payload),
            'status'       => 'pending',
            'attempts'     => 0,
            'available_at' => $availableAt,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public static function pending(int $limit = 10): array
    {
        return DB::fetchAll(
            'SELECT * FROM job_queue WHERE status = ? AND available_at <= NOW() ORDER BY available_at ASC LIMIT ?',
            ['pending', $limit]
        );
    }

    public static function markRunning(int $id): void
    {
        DB::update('job_queue', ['status' => 'running', 'started_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function markDone(int $id): void
    {
        DB::update('job_queue', ['status' => 'completed', 'finished_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function markFailed(int $id, string $error): void
    {
        DB::run(
            'UPDATE job_queue SET status = ?, attempts = attempts + 1, error_message = ?, failed_at = NOW() WHERE id = ?',
            ['failed', $error, $id]
        );
    }
}
