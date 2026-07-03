<?php

declare(strict_types=1);

/**
 * Job Queue Webhook Trigger
 *
 * This endpoint is called by external cron service (UptimeRobot, EasyCron, etc.)
 * to process pending jobs from the queue.
 *
 * Setup (UptimeRobot Free Plan):
 *   1. Go to: https://uptimerobot.com
 *   2. Create account (free tier included)
 *   3. Click "Add New Monitor"
 *   4. Select "Cron Job"
 *   5. Cron Expression: 0 * * * * (every minute)
 *   6. URL: https://creatorzhive.infinityfree.io/webhook/process-jobs.php?secret=YOUR_SECRET_KEY
 *   7. Replace YOUR_SECRET_KEY with a random value from your .env (WEBHOOK_SECRET)
 *   8. Save and test
 *
 * Alternative: EasyCron.com, Cron-job.org, or any HTTP-based cron service
 *
 * Usage:
 *   GET /webhook/process-jobs.php?secret=WEBHOOK_SECRET
 *   Returns: JSON with job processing status
 */

header('Content-Type: application/json');

// === CONFIGURATION ===

$maxJobsPerCall = 3; // Process 2-3 jobs per webhook trigger to avoid timeout
$timeout = 30; // PHP execution timeout (should be less than webhook timeout)

// === SECURITY ===

// Load environment
$envFile = dirname(__DIR__, 2) . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines) {
        foreach ($lines as $line) {
            if (strpos($line, '=') === false || strpos($line, '#') === 0) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, ' "\'');
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            @putenv("{$key}={$value}");
        }
    }
}

$webhookSecret = $_ENV['WEBHOOK_SECRET'] ?? getenv('WEBHOOK_SECRET') ?: 'dev-secret-key';
$providedSecret = $_GET['secret'] ?? '';

// Verify secret (constant-time comparison)
if (!hash_equals($webhookSecret, $providedSecret)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid or missing secret parameter',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

// === BOOTSTRAP ===

try {
    $root = dirname(__DIR__, 2);
    require_once $root . '/backend/helpers/cli_bootstrap.php';
    require_once $root . '/backend/bootstrap-oop.php';
    require_once $root . '/backend/bootstrap-procedural.php';

    cli_load_env();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Bootstrap failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

// === PROCESS JOBS ===

try {
    set_time_limit($timeout);

    $statsBefore = job_runner_stats_by_status();
    $pendingBefore = (int) ($statsBefore['pending'] ?? 0);

    if ($pendingBefore === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No pending jobs',
            'jobs_processed' => 0,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        exit;
    }

    job_runner_run('default', $maxJobsPerCall);

    $statsAfter = job_runner_stats_by_status();
    $pendingAfter = (int) ($statsAfter['pending'] ?? 0);
    $processed = $pendingBefore - $pendingAfter;

    echo json_encode([
        'success' => true,
        'jobs_processed' => max(0, $processed),
        'queue_stats' => $statsAfter,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}
