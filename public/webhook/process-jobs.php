<?php

declare(strict_types=1);

$rootDir    = dirname(__DIR__, 2);
$backendDir = $rootDir . '/backend';

require_once $backendDir . '/helpers/functions.php';
load_env($rootDir . '/.env');

header('Content-Type: application/json; charset=utf-8');

$provided = trim((string) ($_GET['secret'] ?? $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? ''));
$expected = trim((string) env('WEBHOOK_SECRET', ''));

if ($expected === '' || $provided !== $expected) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

require_once $backendDir . '/config/app.php';

$autoload = $rootDir . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists(\CreatorzHive\Core\Application::class)) {
    \CreatorzHive\Core\Application::boot();
}

require_once $backendDir . '/compat/models.php';
require_once $backendDir . '/compat/services.php';
require_once $backendDir . '/compat/auth.php';
require_once $backendDir . '/helpers/platforms.php';
require_once $backendDir . '/core/database.php';
require_once $backendDir . '/core/job_runner.php';

if (!empty($_GET['details'])) {
    $jobs = [];
    try {
        $jobs = db_fetchAll(
            'SELECT id, queue, job_class, status, available_at, attempts, error_message, created_at
             FROM job_queue ORDER BY id DESC LIMIT 20'
        );
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    echo json_encode(['success' => true, 'jobs' => $jobs, 'ts' => date('c')]);
    exit;
}

$queue   = trim((string) ($_GET['queue'] ?? 'default'));
$maxJobs = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

$before = [];
try {
    $before = job_runner_stats_by_status();
} catch (\Throwable $ignored) {
}

try {
    job_runner_run($queue, $maxJobs);
} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'ts'      => date('c'),
    ]);
    exit;
}

$after = [];
try {
    $after = job_runner_stats_by_status();
} catch (\Throwable $ignored) {
}

echo json_encode([
    'success' => true,
    'queue'   => $queue,
    'before'  => $before,
    'after'   => $after,
    'ts'      => date('c'),
]);
