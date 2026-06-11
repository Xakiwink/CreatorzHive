#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Run every minute via cron:
 *   * * * * * php /path/to/creatorzhive/scripts/cron.php >> /tmp/creatorzhive-cron.log 2>&1
 *
 * Options:
 *   --queue=default|analytics|cleanup   Process only that queue once (no scheduler extras).
 */

require_once __DIR__ . '/../backend/helpers/cli_bootstrap.php';
cli_load_env();
cli_boot_oop(true);

$queueOnly = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--queue=') === 0) {
        $queueOnly = substr($arg, 8);
        break;
    }
}

if ($queueOnly !== null && $queueOnly !== '') {
    job_runner_run($queueOnly, 50);
    exit(0);
}

$lockPath = storage_path('cron.lock');
$lockFh = fopen($lockPath, 'c+');
if ($lockFh === false) {
    fwrite(STDERR, "Cannot open lock file\n");
    exit(1);
}

if (!flock($lockFh, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "cron.php: another instance is running\n");
    exit(0);
}

try {
    job_runner_cleanup_completed_older_than_days(30);

    job_runner_run('default', 10);

    $stateFile = storage_path('cron-state.json');
    $state = [];
    if (is_file($stateFile)) {
        $raw = file_get_contents($stateFile);
        if ($raw !== false && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $state = is_array($decoded) ? $decoded : [];
        }
    }

    $now = time();
    $lastAnalytics = (int) ($state['last_analytics'] ?? 0);
    if ($now - $lastAnalytics >= 3600) {
        $accounts = db_fetch_all(
            'SELECT id, user_id FROM social_accounts WHERE is_active = 1'
        );
        foreach ($accounts as $acc) {
            job_runner_dispatch(
                'fetch_analytics',
                [
                    'user_id' => (int) ($acc['user_id'] ?? 0),
                    'social_account_id' => (int) ($acc['id'] ?? 0),
                ],
                'analytics',
                0
            );
        }
        job_runner_run('analytics', 50);
        $state['last_analytics'] = $now;
    }

    $today = date('Y-m-d');
    $hour = (int) date('G');
    $lastCleanupDay = (string) ($state['last_cleanup_day'] ?? '');
    if ($hour === 2 && $lastCleanupDay !== $today) {
        job_runner_dispatch('cleanup_media', [], 'cleanup', 0);
        job_runner_run('cleanup', 10);
        $state['last_cleanup_day'] = $today;
    }

    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
} finally {
    flock($lockFh, LOCK_UN);
    fclose($lockFh);
}
