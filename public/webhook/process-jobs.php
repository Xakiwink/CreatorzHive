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

if (!empty($_GET['clean_analytics'])) {
    try {
        $userId = (int) (db_fetch("SELECT user_id FROM social_accounts WHERE is_active = 1 LIMIT 1")['user_id'] ?? 0);
        if ($userId < 1) {
            echo json_encode(['success' => false, 'error' => 'No active social account found']);
            exit;
        }

        $deleted = db_query(
            'DELETE FROM analytics_snapshots WHERE user_id = :uid AND social_account_id IS NULL',
            ['uid' => $userId]
        )->rowCount();

        analytics_recalculate($userId);

        $accounts = db_fetchAll('SELECT id, user_id FROM social_accounts WHERE is_active = 1');
        $dispatched = 0;
        foreach ($accounts as $acct) {
            job_runner_dispatch('fetch_analytics', [
                'user_id'           => (int) $acct['user_id'],
                'social_account_id' => (int) $acct['id'],
            ]);
            $dispatched++;
        }

        echo json_encode([
            'success'           => true,
            'seed_rows_deleted' => $deleted,
            'posts_recounted'   => true,
            'jobs_dispatched'   => $dispatched,
            'ts'                => date('c'),
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (!empty($_GET['test_insights'])) {
    try {
        $rawRow = db_fetch("SELECT user_id FROM social_accounts WHERE platform = 'instagram' AND is_active = 1 LIMIT 1");
        if ($rawRow === null) {
            echo json_encode(['success' => false, 'error' => 'No active Instagram account found']);
            exit;
        }
        $account = social_account_fetch((int) $rawRow['user_id'], 'instagram');
        if ($account === null) {
            echo json_encode(['success' => false, 'error' => 'No active Instagram account found']);
            exit;
        }
        $token = trim((string) ($account['access_token'] ?? ''));
        $id    = trim((string) ($account['platform_user_id'] ?? ''));

        $since = (int) strtotime('yesterday 00:00:00 UTC');
        $until = $since + 86400;

        $ch = curl_init('https://graph.instagram.com/v25.0/' . rawurlencode($id) . '/insights?' . http_build_query([
            'metric'       => 'impressions,reach',
            'period'       => 'day',
            'since'        => $since,
            'until'        => $until,
            'access_token' => $token,
        ]));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 15]);
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        echo json_encode([
            'since'    => date('Y-m-d H:i:s', $since) . ' UTC',
            'until'    => date('Y-m-d H:i:s', $until) . ' UTC',
            'status'   => $status,
            'response' => $raw !== false ? json_decode((string) $raw, true) : null,
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
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

if (!empty($_GET['test_instagram'])) {
    $out = [];
    try {
        $rawRow = db_fetch(
            "SELECT user_id FROM social_accounts WHERE platform = 'instagram' AND is_active = 1 LIMIT 1"
        );
        if ($rawRow === null) {
            echo json_encode(['success' => false, 'error' => 'No active Instagram account found']);
            exit;
        }
        $account = social_account_fetch((int) $rawRow['user_id'], 'instagram');
        if ($account === null) {
            echo json_encode(['success' => false, 'error' => 'No active Instagram account found']);
            exit;
        }
        $token   = trim((string) ($account['access_token'] ?? ''));
        $igId    = trim((string) ($account['platform_user_id'] ?? ''));
        $out['account_id']    = (int) $account['id'];
        $out['ig_id']         = $igId;
        $out['token_length']  = strlen($token);

        if ($token !== '' && $igId !== '') {
            $ch = curl_init('https://graph.instagram.com/v25.0/' . rawurlencode($igId) . '?fields=followers_count,username,account_type&access_token=' . rawurlencode($token));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 15]);
            $raw    = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err    = curl_error($ch);
            curl_close($ch);
            $out['profile_status']   = $status;
            $out['profile_error']    = $err ?: null;
            $out['profile_response'] = $raw !== false ? json_decode((string) $raw, true) : null;

            $appSecretLen = 0;
            try {
                if (class_exists(\CreatorzHive\Core\Application::class) && \CreatorzHive\Core\Application::instance() !== null) {
                    $sec = platform_api_secrets_resolve('instagram_app_secret');
                    $appSecretLen = strlen((string) $sec);
                }
            } catch (\Throwable $ignored) {}
            $out['app_secret_length'] = $appSecretLen;

            if ($appSecretLen > 0) {
                $appSecret = platform_api_secrets_resolve('instagram_app_secret');
                $ch2 = curl_init('https://graph.instagram.com/access_token?' . http_build_query([
                    'grant_type'    => 'ig_exchange_token',
                    'client_secret' => $appSecret,
                    'access_token'  => $token,
                ]));
                curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 15]);
                $raw2    = curl_exec($ch2);
                $status2 = (int) curl_getinfo($ch2, CURLINFO_RESPONSE_CODE);
                curl_close($ch2);
                $out['exchange_status']   = $status2;
                $out['exchange_response'] = $raw2 !== false ? json_decode((string) $raw2, true) : null;
            }
        } else {
            $out['error'] = 'No token or IG ID on account';
        }
    } catch (\Throwable $e) {
        $out['exception'] = $e->getMessage();
    }
    echo json_encode($out);
    exit;
}

if (!empty($_GET['read_oauth_log'])) {
    $logFile = dirname(__DIR__, 2) . '/backend/storage/logs/oauth-instagram-debug.json';
    if (!is_file($logFile)) {
        echo json_encode(['error' => 'No OAuth debug log found — reconnect Instagram first']);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo file_get_contents($logFile);
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
