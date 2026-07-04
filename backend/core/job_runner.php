<?php

declare(strict_types=1);

function job_runner_log_path(): string
{
    $dir = storage_path('logs');
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return sys_get_temp_dir() . '/creatorzhive-jobs.log';
    }

    return $dir . '/jobs-' . date('Y-m-d') . '.log';
}

function job_runner_log_line(string $level, string $message): void
{
    $line = sprintf(
        '[%s] [%-5s] %s',
        date('Y-m-d H:i:s'),
        $level,
        $message
    ) . PHP_EOL;
    @file_put_contents(job_runner_log_path(), $line, FILE_APPEND);
}

function job_runner_resolve_callable(string $jobClass): ?callable
{
    $classMap = [
        'App\\Jobs\\PublishPostJob' => \CreatorzHive\Jobs\PublishPostJob::class,
        'publish_post' => \CreatorzHive\Jobs\PublishPostJob::class,
        'App\\Jobs\\FetchAnalyticsJob' => \CreatorzHive\Jobs\FetchAnalyticsJob::class,
        'fetch_analytics' => \CreatorzHive\Jobs\FetchAnalyticsJob::class,
        'App\\Jobs\\CleanupMediaJob' => \CreatorzHive\Jobs\CleanupMediaJob::class,
        'cleanup_media' => \CreatorzHive\Jobs\CleanupMediaJob::class,
        'App\\Jobs\\SendNotificationJob' => \CreatorzHive\Jobs\SendNotificationJob::class,
        'send_notification' => \CreatorzHive\Jobs\SendNotificationJob::class,
    ];

    $handlerClass = $classMap[$jobClass] ?? null;
    if ($handlerClass === null) {
        return null;
    }

    if (empty($GLOBALS['cz_container']) || !$GLOBALS['cz_container'] instanceof \CreatorzHive\Core\Container) {
        return null;
    }

    if (!$GLOBALS['cz_container']->has($handlerClass)) {
        return null;
    }

    $handler = $GLOBALS['cz_container']->get($handlerClass);
    if (!$handler instanceof \CreatorzHive\Jobs\JobHandlerInterface) {
        return null;
    }

    return static function (array $payload) use ($handler): void {
        $handler->handle($payload);
    };
}

function job_runner_dispatch(string $jobClass, array $payload, string $queue = 'default', int $delaySeconds = 0): int
{
    if (!empty($GLOBALS['cz_container']) && $GLOBALS['cz_container'] instanceof \CreatorzHive\Core\Container) {
        $repo = $GLOBALS['cz_container']->get(\CreatorzHive\Repositories\JobQueueRepository::class);

        return $repo->dispatch($jobClass, $payload, $queue, $delaySeconds);
    }

    $available = $delaySeconds > 0
        ? date('Y-m-d H:i:s', time() + $delaySeconds)
        : now();

    db_insert('job_queue', [
        'queue' => $queue,
        'job_class' => $jobClass,
        'payload' => json_encode($payload) ?: '{}',
        'status' => 'pending',
        'available_at' => $available,
    ]);

    return (int) db_last_insert_id();
}

function job_runner_cancel(int $jobId): bool
{
    return db_update(
        'job_queue',
        ['status' => 'failed', 'error_message' => 'cancelled', 'failed_at' => now()],
        'id = :id AND status = :pending',
        ['id' => $jobId, 'pending' => 'pending']
    ) > 0;
}

function job_runner_backoff_seconds(int $attempts): int
{
    if ($attempts === 1) {
        return 300;
    }
    if ($attempts === 2) {
        return 900;
    }

    return 2700;
}

function job_runner_payload_summary(array $payload): string
{
    $parts = [];
    foreach (['post_id', 'user_id', 'social_account_id', 'type'] as $k) {
        if (isset($payload[$k])) {
            $parts[] = $k . '=' . $payload[$k];
        }
    }

    return $parts !== [] ? implode(', ', $parts) : json_encode($payload);
}

function job_runner_run(string $queue = 'default', int $maxJobs = 10): void
{
    $maxJobs = max(1, min(100, $maxJobs));

    $rows = db_fetchAll(
        'SELECT * FROM job_queue
            WHERE queue = :q AND status = :pending
            ORDER BY id ASC LIMIT ' . $maxJobs,
        ['q' => $queue, 'pending' => 'pending']
    );

    foreach ($rows as $jobRow) {
        $jobId = (int) $jobRow['id'];
        $updated = db_update(
            'job_queue',
            ['status' => 'running', 'started_at' => now()],
            'id = :id AND status = :pending',
            ['id' => $jobId, 'pending' => 'pending']
        );

        if ($updated === 0) {
            continue;
        }

        $className = (string) $jobRow['job_class'];
        $payload = json_decode((string) $jobRow['payload'], true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $handler = job_runner_resolve_callable($className);
        if ($handler === null) {
            db_update(
                'job_queue',
                [
                    'status' => 'failed',
                    'attempts' => (int) $jobRow['attempts'] + 1,
                    'failed_at' => now(),
                    'error_message' => 'Unknown job type: ' . $className,
                    'started_at' => null,
                ],
                'id = :id',
                ['id' => $jobId]
            );
            job_runner_log_line('ERROR', 'Unknown job: ' . $className . ' #' . $jobId);

            continue;
        }

        $started = microtime(true);
        job_runner_log_line(
            'INFO',
            sprintf(
                'Job started: %s #%d (%s)',
                $className,
                $jobId,
                job_runner_payload_summary($payload)
            )
        );

        try {
            $handler($payload);

            db_update(
                'job_queue',
                ['status' => 'completed', 'completed_at' => now(), 'error_message' => null],
                'id = :id',
                ['id' => $jobId]
            );

            $dur = round(microtime(true) - $started, 2);
            job_runner_log_line(
                'INFO',
                sprintf('Job completed: %s #%d (duration=%ss)', $className, $jobId, $dur)
            );
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            job_runner_log_line(
                'ERROR',
                sprintf('Job failed: %s #%d — %s', $className, $jobId, $msg)
            );

            $attempts = (int) $jobRow['attempts'] + 1;
            $maxAttempts = (int) ($jobRow['max_attempts'] ?? 3);
            if ($maxAttempts < 1) {
                $maxAttempts = 3;
            }

            if ($attempts < $maxAttempts) {
                $delay = job_runner_backoff_seconds($attempts);
                $nextAt = gmdate('Y-m-d H:i:s', time() + $delay);
                db_update(
                    'job_queue',
                    [
                        'status' => 'pending',
                        'attempts' => $attempts,
                        'available_at' => $nextAt,
                        'started_at' => null,
                        'error_message' => $msg,
                    ],
                    'id = :id',
                    ['id' => $jobId]
                );
                job_runner_log_line(
                    'WARN',
                    sprintf(
                        'Retrying job #%d in %d minutes (attempt %d/%d)',
                        $jobId,
                        (int) round($delay / 60),
                        $attempts,
                        $maxAttempts
                    )
                );
            } else {
                db_update(
                    'job_queue',
                    [
                        'status' => 'failed',
                        'attempts' => $attempts,
                        'failed_at' => now(),
                        'error_message' => $msg,
                        'started_at' => null,
                    ],
                    'id = :id',
                    ['id' => $jobId]
                );
            }
        }
    }
}

function job_runner_cleanup_completed_older_than_days(int $days = 30): int
{
    $days = max(1, $days);

    return db_delete(
        'job_queue',
        'status = :done AND completed_at IS NOT NULL AND completed_at < DATE_SUB(NOW(), INTERVAL ' . (int) $days . ' DAY)',
        ['done' => 'completed']
    );
}

function job_runner_retry_failed(int $jobId): bool
{
    return db_update(
        'job_queue',
        [
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ],
        'id = :id AND status = :failed',
        ['id' => $jobId, 'failed' => 'failed']
    ) > 0;
}

function job_runner_stats_by_status(): array
{
    $rows = db_fetchAll(
        'SELECT status, COUNT(*) AS c FROM job_queue GROUP BY status'
    );
    $out = [];
    foreach ($rows as $r) {
        $k = (string) ($r['status'] ?? '');
        if ($k !== '') {
            $out[$k] = (int) ($r['c'] ?? 0);
        }
    }

    return $out;
}

function job_runner_list_failed(int $limit = 50): array
{
    $limit = max(1, min(200, $limit));

    $params = db_bind_limit(['failed' => 'failed'], $limit, 200);

    return db_fetchAll(
        'SELECT id, queue, job_class, payload, attempts, max_attempts, error_message, failed_at, created_at
            FROM job_queue WHERE status = :failed ORDER BY id DESC LIMIT :limit',
        $params
    );
}

function job_runner_flush_finished(): int
{
    $stmt = db_query('DELETE FROM job_queue WHERE status IN (\'completed\',\'failed\')');

    return $stmt->rowCount();
}
