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

if (!empty($_GET['refresh_analytics'])) {
    try {
        $accounts = db_fetchAll(
            'SELECT id, user_id FROM social_accounts WHERE is_active = 1'
        );
        $dispatched = 0;
        foreach ($accounts as $acct) {
            job_runner_dispatch('fetch_analytics', [
                'user_id'           => (int) $acct['user_id'],
                'social_account_id' => (int) $acct['id'],
            ]);
            $dispatched++;
        }
        echo json_encode(['success' => true, 'dispatched' => $dispatched, 'ts' => date('c')]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (!empty($_GET['diagnose'])) {
    $out = ['php_now' => date('Y-m-d H:i:s'), 'php_tz' => date_default_timezone_get()];

    try {
        $out['mysql_now'] = db_fetch('SELECT NOW() AS n')['n'] ?? null;
    } catch (\Throwable $e) {
        $out['mysql_now_err'] = $e->getMessage();
    }

    try {
        $out['select_with_params'] = db_fetchAll(
            'SELECT id, queue, status, available_at FROM job_queue
             WHERE queue = :q AND status = :s AND available_at <= NOW()',
            ['q' => 'default', 's' => 'pending']
        );
    } catch (\Throwable $e) {
        $out['select_with_params_err'] = $e->getMessage();
    }

    try {
        $out['select_no_params'] = db_fetchAll(
            "SELECT id, queue, status, available_at FROM job_queue
             WHERE queue = 'default' AND status = 'pending' AND available_at <= NOW()"
        );
    } catch (\Throwable $e) {
        $out['select_no_params_err'] = $e->getMessage();
    }

    try {
        $out['container_ok'] = !empty($GLOBALS['cz_container'])
            && $GLOBALS['cz_container'] instanceof \CreatorzHive\Core\Container;
    } catch (\Throwable $e) {
        $out['container_err'] = $e->getMessage();
    }

    echo json_encode($out);
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
