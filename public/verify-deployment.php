<?php
/**
 * Comprehensive Deployment Verification
 * Visit: https://creatorzhive.infinityfree.io/verify-deployment.php
 *
 * Checks all critical systems needed for production deployment.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Deployment Verification</title><style>
body { font-family: monospace; background: #f5f5f5; margin: 0; padding: 20px; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h2 { color: #0056b3; margin-top: 20px; }
.check { margin: 10px 0; padding: 10px; border-left: 4px solid #ccc; }
.check.pass { border-left-color: #28a745; background: #f0f9f6; }
.check.fail { border-left-color: #dc3545; background: #fff5f5; }
.check.warn { border-left-color: #ffc107; background: #fffbf0; }
.check.info { border-left-color: #17a2b8; background: #f0f7f9; }
code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
.stat { display: inline-block; margin-right: 20px; padding: 10px; background: #f9f9f9; border-radius: 3px; }
</style></head><body><div class='container'>";

echo "<h1>✅ CreatorzHive Deployment Verification</h1>\n";
echo "<p>Checking all critical systems for production readiness...</p>\n";

$checks = [];
$passed = 0;
$failed = 0;
$warned = 0;

// Helper
function add_check($name, $result, $message = '') {
    global $checks, $passed, $failed, $warned;
    $status = $result === true ? 'pass' : ($result === false ? 'fail' : 'warn');
    if ($status === 'pass') $passed++;
    elseif ($status === 'fail') $failed++;
    else $warned++;
    $checks[] = [
        'name' => $name,
        'status' => $status,
        'message' => $message
    ];
}

// === ENVIRONMENT ===
echo "<h2>1. Environment & Configuration</h2>\n";

$env_loaded = function_exists('env');
add_check('env() helper loaded', $env_loaded);

$db_host = env('DB_HOST', 'NOT SET');
$db_name = env('DB_DATABASE', 'NOT SET');
$db_user = env('DB_USERNAME', 'NOT SET');
add_check('DB_HOST configured', $db_host !== 'NOT SET', "DB_HOST=$db_host");
add_check('DB_DATABASE configured', $db_name !== 'NOT SET', "DB_DATABASE=$db_name");
add_check('DB_USERNAME configured', $db_user !== 'NOT SET', "DB_USERNAME=$db_user");

$app_secret = env('APP_SECRET', '');
add_check('APP_SECRET set', !empty($app_secret), empty($app_secret) ? 'CRITICAL: Missing APP_SECRET' : 'Set (' . strlen($app_secret) . ' chars)');

$app_env = env('APP_ENV', 'NOT SET');
add_check('APP_ENV configured', $app_env !== 'NOT SET', "APP_ENV=$app_env");

// === DATABASE ===
echo "<h2>2. Database Connection</h2>\n";

try {
    require_once dirname(__DIR__) . '/backend/index.php';

    $pdo = null;
    if (function_exists('db_get_pdo')) {
        try {
            $pdo = db_get_pdo();
            $GLOBALS['_cz_pdo'] = $pdo;
        } catch (Throwable $dbEx) {
            add_check('PDO initialized', false, $dbEx->getMessage());
        }
    }
    if ($pdo) {
        add_check('PDO initialized', true);

        // Test connection
        try {
            $count = $pdo->query("SELECT 1")->fetchColumn();
            add_check('Database connection works', true);
        } catch (Throwable $e) {
            add_check('Database connection works', false, $e->getMessage());
        }

        // Check tables
        $tables = ['users', 'posts', 'social_accounts', 'job_queue', 'analytics', 'deals', 'invoices', 'notifications'];
        $missing = [];
        foreach ($tables as $table) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                add_check("Table exists: $table", true, "$count rows");
            } catch (Throwable $e) {
                $missing[] = $table;
                add_check("Table exists: $table", false, $e->getMessage());
            }
        }

        // Check for demo data
        if (empty($missing)) {
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $postCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            add_check('Demo data exists', $userCount > 0 || $postCount > 0, "Users: $userCount, Posts: $postCount");
        }
    }
} catch (Throwable $e) {
    add_check('Bootstrap successful', false, get_class($e) . ': ' . $e->getMessage());
}

// === APPLICATION ===
echo "<h2>3. Application Services</h2>\n";

if (isset($GLOBALS['cz_container'])) {
    $container = $GLOBALS['cz_container'];

    $services = [
        'DashboardService' => '\CreatorzHive\Services\DashboardService',
        'AuthService' => '\CreatorzHive\Services\AuthService',
        'PostService' => '\CreatorzHive\Services\PostService',
        'JobQueueService' => '\CreatorzHive\Services\JobQueueService',
    ];

    foreach ($services as $name => $class) {
        try {
            if ($container->has($class)) {
                $service = $container->get($class);
                add_check("$name available", true);
            } else {
                add_check("$name available", false, 'Not registered in container');
            }
        } catch (Throwable $e) {
            add_check("$name available", false, $e->getMessage());
        }
    }
}

// === SECURITY ===
echo "<h2>4. Security Checks</h2>\n";

// Check .env file permissions
$env_file = dirname(__DIR__) . '/.env';
if (is_file($env_file)) {
    $perms = substr(sprintf('%o', fileperms($env_file)), -3);
    $safe = $perms === '600' || $perms === '640';
    add_check('.env file permissions', $safe, "Permissions: $perms (should be 600 or 640)");
}

// Check for upload protection
$htaccess_path = dirname(__DIR__) . '/public/uploads/.htaccess';
$has_upload_protection = is_file($htaccess_path);
add_check('Upload directory protected', $has_upload_protection, $has_upload_protection ? 'Has .htaccess' : 'Missing .htaccess in uploads/');

// Check session configuration
add_check('Sessions configured', session_status() === PHP_SESSION_NONE || session_status() === PHP_SESSION_ACTIVE, 'Status: ' . (session_status() === PHP_SESSION_NONE ? 'Not started' : 'Active'));

// === JOB PROCESSING ===
echo "<h2>5. Job Processing (Background Tasks)</h2>\n";

if (isset($GLOBALS['_cz_pdo'])) {
    try {
        $pending = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM job_queue WHERE status = 'pending'")->fetchColumn();
        $processing = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM job_queue WHERE status = 'processing'")->fetchColumn();
        $failed = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM job_queue WHERE status = 'failed'")->fetchColumn();

        add_check('Job queue accessible', true, "Pending: $pending, Processing: $processing, Failed: $failed");
        add_check('Webhook endpoint exists', is_file(dirname(__DIR__) . '/public/webhook/process-jobs.php'), 'Webhook at /public/webhook/process-jobs.php');
    } catch (Throwable $e) {
        add_check('Job queue accessible', false, $e->getMessage());
    }
}

// === FEATURES ===
echo "<h2>6. Key Features Status</h2>\n";

if (isset($GLOBALS['_cz_pdo'])) {
    try {
        $accountCount = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM social_accounts")->fetchColumn();
        add_check('Social account integration', $accountCount >= 0, "Accounts in database: $accountCount");

        $dealCount = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM deals")->fetchColumn();
        add_check('Deals module', $dealCount >= 0, "Deals in database: $dealCount");

        $invoiceCount = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
        add_check('Invoices module', $invoiceCount >= 0, "Invoices in database: $invoiceCount");

        $notificationCount = $GLOBALS['_cz_pdo']->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
        add_check('Notifications module', $notificationCount >= 0, "Notifications in database: $notificationCount");
    } catch (Throwable $e) {
        add_check('Features accessible', false, $e->getMessage());
    }
}

// === RENDER RESULTS ===
echo "\n<h2>Verification Summary</h2>\n";
echo "<div class='stat'>✅ <strong>Passed:</strong> $passed</div>\n";
echo "<div class='stat'>❌ <strong>Failed:</strong> $failed</div>\n";
echo "<div class='stat'>⚠️ <strong>Warnings:</strong> $warned</div>\n";
echo "<div style='clear: both; margin-top: 20px;'></div>\n";

echo "<h2>Detailed Results</h2>\n";
foreach ($checks as $check) {
    $icon = $check['status'] === 'pass' ? '✅' : ($check['status'] === 'fail' ? '❌' : '⚠️');
    echo "<div class='check {$check['status']}'>";
    echo "$icon <strong>{$check['name']}</strong>";
    if ($check['message']) {
        echo " — <code>{$check['message']}</code>";
    }
    echo "</div>\n";
}

echo "<h2>Next Steps</h2>\n";
echo "<div style='background: #f0f7f9; padding: 15px; border-radius: 5px; border-left: 4px solid #17a2b8;'>\n";

if ($failed > 0) {
    echo "<p><strong>⚠️ Critical Issues Found</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Review the failed checks above</li>\n";
    echo "<li>Run setup.php at <code>/setup.php</code> to initialize database</li>\n";
    echo "<li>Check database credentials in <code>.env</code></li>\n";
    echo "</ul>\n";
} else {
    echo "<p><strong>✅ System Ready for Deployment</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Visit <a href='https://creatorzhive.infinityfree.io/'>https://creatorzhive.infinityfree.io/</a> to test the application</li>\n";
    echo "<li>Test login with demo account (if seeded)</li>\n";
    echo "<li>Monitor background jobs via job queue</li>\n";
    echo "<li>Delete this verification file after confirming status</li>\n";
    echo "</ul>\n";
}

echo "</div>\n";
echo "</div></body></html>";
