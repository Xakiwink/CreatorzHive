<?php
/**
 * Phase 2: Feature Testing Dashboard
 * Visit: https://creatorz.freedev.app/test-features.php
 *
 * Systematically test all major features to identify what's working and what needs fixes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Phase 2: Feature Testing</title><style>
body { font-family: monospace; background: #f5f5f5; margin: 0; padding: 20px; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h2 { color: #0056b3; margin-top: 20px; }
.test { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; background: #f9f9f9; }
.test.pass { border-left-color: #28a745; background: #f0f9f6; }
.test.fail { border-left-color: #dc3545; background: #fff5f5; }
.test.warn { border-left-color: #ffc107; background: #fffbf0; }
code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.detail { margin-top: 10px; padding: 10px; background: white; border-radius: 3px; font-size: 12px; }
</style></head><body><div class='container'>";

echo "<h1>✅ Phase 2: Feature Testing & Verification</h1>\n";
echo "<p>Testing all major application features...</p>\n";

$results = [];
$passed = 0;
$failed = 0;

function test($name, $callback) {
    global $passed, $failed, $results;
    try {
        $result = $callback();
        if ($result['status'] === 'pass') $passed++;
        else $failed++;
        $results[] = ['name' => $name, 'result' => $result];
    } catch (Throwable $e) {
        $failed++;
        $results[] = [
            'name' => $name,
            'result' => ['status' => 'fail', 'message' => $e->getMessage()]
        ];
    }
}

// Bootstrap
try {
    require_once dirname(__DIR__) . '/backend/index.php';
    $pdo = $GLOBALS['_cz_pdo'];
    $container = $GLOBALS['cz_container'];
} catch (Throwable $e) {
    echo "<div class='test fail'>";
    echo "❌ <strong>Bootstrap Failed</strong><br>";
    echo "Cannot continue testing without bootstrap: " . $e->getMessage();
    echo "</div>";
    exit;
}

// ============ TEST: Database Connection ============
echo "<h2>1. Database Connection</h2>\n";

test("Database connection works", function() use ($pdo) {
    $pdo->query("SELECT 1");
    return ['status' => 'pass', 'message' => 'Connected to InfinityFree MySQL'];
});

test("All tables exist", function() use ($pdo) {
    $tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    $expected = ['users', 'posts', 'analytics', 'deals', 'social_accounts', 'notifications'];
    $missing = array_diff($expected, $tables);
    if (empty($missing)) {
        return ['status' => 'pass', 'message' => count($tables) . ' tables found'];
    }
    return ['status' => 'fail', 'message' => 'Missing tables: ' . implode(', ', $missing)];
});

test("Demo data exists", function() use ($pdo) {
    $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    if ($users > 0 && $posts > 0) {
        return ['status' => 'pass', 'message' => "$users users, $posts posts"];
    }
    return ['status' => 'fail', 'message' => "Insufficient data: users=$users, posts=$posts"];
});

// ============ TEST: OOP Services ============
echo "<h2>2. OOP Services</h2>\n";

test("UserRepository available", function() use ($container) {
    $repo = $container->get(\CreatorzHive\Repositories\UserRepository::class);
    $users = $repo->listAll(5);
    return ['status' => 'pass', 'message' => 'Loaded ' . count($users) . ' users'];
});

test("PostRepository available", function() use ($container) {
    $repo = $container->get(\CreatorzHive\Repositories\PostRepository::class);
    return ['status' => 'pass', 'message' => 'Repository instantiated'];
});

test("AnalyticsRepository available", function() use ($container) {
    $repo = $container->get(\CreatorzHive\Repositories\AnalyticsRepository::class);
    return ['status' => 'pass', 'message' => 'Repository instantiated'];
});

test("DashboardService available", function() use ($container) {
    $service = $container->get(\CreatorzHive\Services\DashboardService::class);
    return ['status' => 'pass', 'message' => 'Service instantiated'];
});

// ============ TEST: Procedural Functions ============
echo "<h2>3. Procedural Functions (Compatibility Layer)</h2>\n";

test("user_find_by_id() works", function() {
    $user = user_find_by_id(1);
    if ($user) {
        return ['status' => 'pass', 'message' => 'Found user: ' . ($user['name'] ?? 'Unknown')];
    }
    return ['status' => 'warn', 'message' => 'No user with ID 1'];
});

test("post_get_recent_by_user() works", function() use ($pdo) {
    // Get first user ID
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) return ['status' => 'warn', 'message' => 'No users in database'];

    $posts = post_get_recent_by_user($userId, 5);
    return ['status' => 'pass', 'message' => 'Function returns ' . count($posts) . ' posts'];
});

test("post_count_by_status() works", function() use ($pdo) {
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) return ['status' => 'warn', 'message' => 'No users in database'];

    $counts = post_count_by_status($userId);
    return ['status' => 'pass', 'message' => 'Returns status breakdown: ' . json_encode($counts)];
});

// ============ TEST: Dashboard Queries ============
echo "<h2>4. Dashboard Data Queries</h2>\n";

test("DashboardRepository::getTotalPosts()", function() use ($container, $pdo) {
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) return ['status' => 'warn', 'message' => 'No users in database'];

    $repo = $container->get(\CreatorzHive\Repositories\DashboardRepository::class);
    $total = $repo->getTotalPosts($userId);
    return ['status' => 'pass', 'message' => "User has $total posts"];
});

test("DashboardRepository::getTotalPublished()", function() use ($container, $pdo) {
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) return ['status' => 'warn', 'message' => 'No users in database'];

    $repo = $container->get(\CreatorzHive\Repositories\DashboardRepository::class);
    $published = $repo->getTotalPublished($userId);
    return ['status' => 'pass', 'message' => "User has $published published"];
});

test("DashboardRepository::findActiveSocialAccounts()", function() use ($container, $pdo) {
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) return ['status' => 'warn', 'message' => 'No users in database'];

    $repo = $container->get(\CreatorzHive\Repositories\DashboardRepository::class);
    $accounts = $repo->findActiveSocialAccounts($userId);
    return ['status' => 'pass', 'message' => count($accounts) . ' social accounts connected'];
});

test("DashboardService::buildPayload()", function() use ($container, $pdo) {
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) return ['status' => 'warn', 'message' => 'No users in database'];

    $service = $container->get(\CreatorzHive\Services\DashboardService::class);
    $payload = $service->buildPayload($userId);

    if (is_array($payload) && isset($payload['stats'])) {
        return ['status' => 'pass', 'message' => 'Payload has keys: ' . implode(', ', array_keys($payload))];
    }
    return ['status' => 'fail', 'message' => 'Invalid payload structure'];
});

// ============ TEST: Authentication ============
echo "<h2>5. Authentication</h2>\n";

test("Password hashing works", function() {
    $hash = password_hash('TestPassword123', PASSWORD_BCRYPT, ['cost' => 12]);
    $verify = password_verify('TestPassword123', $hash);
    return $verify ? ['status' => 'pass', 'message' => 'Bcrypt working'] : ['status' => 'fail', 'message' => 'Hash mismatch'];
});

test("User exists with valid email", function() use ($pdo) {
    $user = $pdo->query("SELECT * FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['email']) {
        return ['status' => 'pass', 'message' => "Found user: {$user['email']}"];
    }
    return ['status' => 'fail', 'message' => 'No users in database'];
});

// ============ RENDER RESULTS ============
echo "\n<h2>Test Summary</h2>\n";
echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px;'>\n";
echo "✅ <strong>Passed: $passed</strong><br>\n";
echo "❌ <strong>Failed: $failed</strong><br>\n";
echo "</div>\n";

foreach ($results as $test) {
    $status = $test['result']['status'];
    $icon = $status === 'pass' ? '✅' : ($status === 'fail' ? '❌' : '⚠️');
    $class = $status;

    echo "<div class='test $class'>";
    echo "$icon <strong>{$test['name']}</strong>";
    if (isset($test['result']['message'])) {
        echo "<br><code>{$test['result']['message']}</code>";
    }
    echo "</div>\n";
}

echo "<h2>Next Steps</h2>\n";
if ($failed === 0) {
    echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>\n";
    echo "✅ <strong>All Core Systems Working!</strong><br>\n";
    echo "Ready to test dashboard and API endpoints.<br>\n";
    echo "Next: Test login and feature workflows.\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; border-left: 4px solid #ff9800;'>\n";
    echo "⚠️ <strong>Some tests failed</strong><br>\n";
    echo "Review the failures above and share with technical support.\n";
    echo "</div>\n";
}

echo "</div></body></html>";
