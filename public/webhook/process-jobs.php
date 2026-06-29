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
$envFile = dirname(__DIR__) . '/.env';
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
            putenv("{$key}={$value}");
        }
    }
}

$webhookSecret = getenv('WEBHOOK_SECRET') ?: 'dev-secret-key';
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
    $root = dirname(__DIR__);
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

    $jobsProcessed = 0;
    $jobsSuccess = 0;
    $jobsFailed = 0;
    $errors = [];

    // Get pending jobs
    $jobs = db_fetch_all(
        'SELECT id, queue, job_class, payload FROM job_queue WHERE status = :status LIMIT :limit',
        ['status' => 'pending', 'limit' => $maxJobsPerCall]
    );

    if (!$jobs) {
        echo json_encode([
            'success' => true,
            'message' => 'No pending jobs',
            'jobs_processed' => 0,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        exit;
    }

    foreach ($jobs as $job) {
        $jobId = (int) ($job['id'] ?? 0);
        $jobClass = (string) ($job['job_class'] ?? '');
        $payload = (array) json_decode((string) ($job['payload'] ?? '{}'), true);
        $queue = (string) ($job['queue'] ?? 'default');

        $jobsProcessed++;

        try {
            // Get job handler instance from container
            $handler = app_resolve($jobClass);

            // Execute job
            $handler->handle($payload);

            // Mark as completed
            db_update(
                'job_queue',
                ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $jobId]
            );

            $jobsSuccess++;
        } catch (Throwable $jobError) {
            $jobsFailed++;
            $errors[] = "Job #{$jobId} ({$jobClass}): " . $jobError->getMessage();

            // Update job with error
            $attempts = db_fetch(
                'SELECT attempts, max_attempts FROM job_queue WHERE id = :id',
                ['id' => $jobId]
            );

            $currentAttempts = (int) ($attempts['attempts'] ?? 0);
            $maxAttempts = (int) ($attempts['max_attempts'] ?? 3);

            if ($currentAttempts >= $maxAttempts) {
                // Mark as failed
                db_update(
                    'job_queue',
                    [
                        'status' => 'failed',
                        'failed_at' => date('Y-m-d H:i:s'),
                        'error_message' => $jobError->getMessage(),
                    ],
                    'id = :id',
                    ['id' => $jobId]
                );
            } else {
                // Increment retry count
                db_update(
                    'job_queue',
                    [
                        'attempts' => $currentAttempts + 1,
                        'next_retry_at' => date('Y-m-d H:i:s', time() + (60 * ($currentAttempts + 1))), // Exponential backoff
                    ],
                    'id = :id',
                    ['id' => $jobId]
                );
            }
        }
    }

    // === RESPONSE ===

    echo json_encode([
        'success' => true,
        'jobs_processed' => $jobsProcessed,
        'jobs_success' => $jobsSuccess,
        'jobs_failed' => $jobsFailed,
        'errors' => $errors ?: [],
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
