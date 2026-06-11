#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Database setup (default) or job_queue maintenance:
 *
 *   php scripts/migrate.php                  — apply schema / migrations
 *   php scripts/migrate.php status           — job queue counts by status
 *   php scripts/migrate.php failed             — list failed jobs
 *   php scripts/migrate.php retry <job_id>    — reset failed job to pending
 *   php scripts/migrate.php flush              — delete completed & failed rows
 */

$root = dirname(__DIR__);

require_once $root . '/backend/helpers/cli_bootstrap.php';
cli_load_env();

$cmd = $argv[1] ?? 'migrate';

if (in_array($cmd, ['status', 'failed', 'retry', 'flush'], true)) {
    cli_boot_oop(true);

    if ($cmd === 'status') {
        $stats = job_runner_stats_by_status();
        echo 'Job queue:' . PHP_EOL;
        foreach ($stats as $k => $v) {
            echo sprintf('  %-12s %d', $k, $v) . PHP_EOL;
        }
        exit(0);
    }

    if ($cmd === 'failed') {
        $rows = job_runner_list_failed(50);
        if ($rows === []) {
            echo 'No failed jobs.' . PHP_EOL;
            exit(0);
        }
        foreach ($rows as $r) {
            echo sprintf(
                '#%s [%s] %s attempts=%s/%s failed_at=%s err=%s',
                $r['id'] ?? '',
                $r['queue'] ?? '',
                $r['job_class'] ?? '',
                $r['attempts'] ?? '',
                $r['max_attempts'] ?? '',
                $r['failed_at'] ?? '',
                substr((string) ($r['error_message'] ?? ''), 0, 120)
            ) . PHP_EOL;
        }
        exit(0);
    }

    if ($cmd === 'retry') {
        $jobId = (int) ($argv[2] ?? 0);
        if ($jobId < 1) {
            fwrite(STDERR, "Usage: php scripts/migrate.php retry <job_id>\n");
            exit(1);
        }
        $ok = job_runner_retry_failed($jobId);
        echo $ok ? "Job #{$jobId} queued for retry.\n" : "Job #{$jobId} not found or not failed.\n";
        exit($ok ? 0 : 1);
    }

    if ($cmd === 'flush') {
        $n = job_runner_flush_finished();
        echo "Deleted {$n} completed/failed job row(s).\n";
        exit(0);
    }
}

$pdo = cli_pdo();

$runSqlFile = static function (string $path) use ($pdo): void {
    if (!is_file($path)) {
        return;
    }

    $sql = file_get_contents($path);

    if ($sql === false || trim($sql) === '') {
        echo '[SKIP] ' . basename($path) . PHP_EOL;

        return;
    }

    $pdo->exec($sql);
    echo '[OK]   ' . basename($path) . PHP_EOL;
};

echo 'Running database setup...' . PHP_EOL;

$schemaFile = base_path('database/schema.sql');
$runSqlFile($schemaFile);

$migrationFiles = glob(base_path('database/migrations/*.sql'));
sort($migrationFiles);

foreach ($migrationFiles as $migrationFile) {
    $runSqlFile($migrationFile);
}

echo 'Done.' . PHP_EOL;
