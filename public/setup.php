<?php

declare(strict_types=1);

/**
 * One-time setup endpoint for InfinityFree deployment.
 *
 * Usage:
 *   1. Upload project to InfinityFree via FTP
 *   2. Create MySQL database via InfinityFree control panel
 *   3. Copy .env.example to .env and edit database credentials
 *   4. Visit: https://creatorz.freedev.app/setup.php
 *   5. Follow the form to:
 *      - Run database migrations
 *      - Seed demo data (optional)
 *      - Create admin user
 *   6. Delete this file after setup completes
 *
 * IMPORTANT: This file should be deleted after first-time setup!
 * It poses a security risk if left in production.
 */

// === SECURITY CHECKS ===

// Allow setup from localhost by default, or if SETUP_ALLOWED_IPS env var is set
// For InfinityFree: set SETUP_ALLOWED_IPS=* in .env to allow any IP (less secure but needed for shared hosting)
$allowedIps = ['127.0.0.1', '::1'];
$setupAllowedIps = getenv('SETUP_ALLOWED_IPS');

if ($setupAllowedIps === '*') {
    // Allow from any IP (use only during first setup, then delete this file!)
    $isLocalhost = true;
} else {
    if ($setupAllowedIps) {
        // Add comma-separated IPs from .env
        foreach (explode(',', $setupAllowedIps) as $ip) {
            $allowedIps[] = trim($ip);
        }
    }
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocalhost = in_array($remoteIp, $allowedIps, true);
}

// Check if already set up (setup.lock file)
$setupLockFile = dirname(__FILE__) . '/setup.lock';
$isAlreadySetup = is_file($setupLockFile);

// === HELPER FUNCTIONS ===

function loadEnv(): void
{
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
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

function testDatabaseConnection(): ?string
{
    try {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE');
        $user = getenv('DB_USERNAME');
        $pass = getenv('DB_PASSWORD');

        if (!$database || !$user) {
            return 'Missing DB_DATABASE or DB_USERNAME in .env';
        }

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec('SELECT 1');
        return null; // No error
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

function runMigration(): ?string
{
    try {
        $root = dirname(__DIR__);
        require_once $root . '/backend/helpers/cli_bootstrap.php';

        cli_load_env();
        $pdo = cli_pdo();

        // Run schema
        $schemaFile = $root . '/database/schema.sql';
        if (is_file($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            if ($sql) {
                $pdo->exec($sql);
            }
        }

        // Run migrations
        $migrations = glob($root . '/database/migrations/*.sql');
        if ($migrations) {
            sort($migrations);
            foreach ($migrations as $file) {
                $sql = file_get_contents($file);
                if ($sql && trim($sql) !== '') {
                    $pdo->exec($sql);
                }
            }
        }

        return null; // Success
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

function runSeeding(): ?string
{
    try {
        $root = dirname(__DIR__);
        require_once $root . '/backend/helpers/cli_bootstrap.php';

        cli_load_env();
        $pdo = cli_pdo();

        $seeds = [
            'users.sql',
            'posts.sql',
            'deals.sql',
            'notifications.sql',
            'analytics.sql'
        ];

        foreach ($seeds as $seed) {
            $file = $root . '/database/seeds/' . $seed;
            if (is_file($file)) {
                $sql = file_get_contents($file);
                if ($sql && trim($sql) !== '') {
                    $pdo->exec($sql);
                }
            }
        }

        return null; // Success
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

function createAdminUser(string $email, string $name, string $password): ?string
{
    try {
        $root = dirname(__DIR__);
        require_once $root . '/backend/helpers/cli_bootstrap.php';
        require_once $root . '/backend/bootstrap-oop.php';
        require_once $root . '/backend/bootstrap-procedural.php';

        cli_load_env();

        // Use the application's auth service
        $hashedPassword = auth_service_hash_password($password);

        $result = user_create([
            'name' => $name,
            'username' => 'admin_' . bin2hex(random_bytes(4)),
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'admin',
        ]);

        if (!$result) {
            return 'Failed to create admin user';
        }

        return null; // Success
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

function markSetupComplete(): void
{
    $setupLockFile = dirname(__FILE__) . '/setup.lock';
    file_put_contents($setupLockFile, "Setup completed at: " . date('Y-m-d H:i:s') . "\n");
}

// === PROCESS FORM SUBMISSION ===

$dbError = null;
$migrationError = null;
$seedError = null;
$adminError = null;
$setupSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAlreadySetup) {
    if (!$isLocalhost) {
        $dbError = "Setup is restricted to localhost. Your IP: {$remoteIp}";
    } else {
        loadEnv();

        // Check database connection
        $dbError = testDatabaseConnection();

        if (!$dbError) {
            // Run migration
            if (isset($_POST['run_migration']) && $_POST['run_migration'] === '1') {
                $migrationError = runMigration();
            }

            // Run seeding (optional)
            if (!$migrationError && isset($_POST['run_seeding']) && $_POST['run_seeding'] === '1') {
                $seedError = runSeeding();
            }

            // Create admin user
            if (!$migrationError && isset($_POST['admin_email'], $_POST['admin_name'], $_POST['admin_password'])) {
                $adminEmail = trim((string) $_POST['admin_email']);
                $adminName = trim((string) $_POST['admin_name']);
                $adminPassword = trim((string) $_POST['admin_password']);

                if ($adminEmail && $adminName && $adminPassword) {
                    if (strlen($adminPassword) < 8) {
                        $adminError = 'Password must be at least 8 characters';
                    } else {
                        $adminError = createAdminUser($adminEmail, $adminName, $adminPassword);
                        if (!$adminError) {
                            $setupSuccess = true;
                            markSetupComplete();
                        }
                    }
                }
            }
        }
    }
}

// === LOAD .ENV DEFAULTS ===

loadEnv();
$dbDatabase = getenv('DB_DATABASE') ?: '';
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CreatorzHive - One-Time Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .alert-info {
            background: #eef;
            border: 1px solid #ccf;
            color: #33c;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }

        .checkbox-label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .form-section {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .form-section h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
        }

        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s;
        }

        button:hover {
            background: #5568d3;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }

        .text-muted {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .setup-complete {
            text-align: center;
        }

        .setup-complete h2 {
            color: #3c3;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .setup-complete .instructions {
            background: #f0f9ff;
            border: 1px solid #cce;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
            font-size: 14px;
        }

        .instructions ol {
            margin-left: 20px;
        }

        .instructions li {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .instructions code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }

        .next-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }

        .next-link:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <?php if ($isAlreadySetup): ?>
            <div class="alert alert-info">
                ✓ Setup already completed!
                <br><br>
                The application is ready to use. Please:
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li>Delete this file (setup.php) from your server</li>
                    <li>Go to: <a href="/" style="color: inherit; text-decoration: underline;">https://creatorz.freedev.app/</a></li>
                    <li>Log in with your admin credentials</li>
                </ol>
            </div>

        <?php elseif ($setupSuccess): ?>
            <div class="setup-complete">
                <h2>✓ Setup Successful!</h2>
                <div class="instructions">
                    <p><strong>Your CreatorzHive instance is ready!</strong></p>
                    <p style="margin-top: 15px;">Next steps:</p>
                    <ol>
                        <li><strong>Delete this file:</strong> Remove <code>public/setup.php</code> from your server for security</li>
                        <li><strong>Set up background jobs:</strong> Configure UptimeRobot or similar to trigger <code>/webhook/process-jobs.php</code> every minute</li>
                        <li><strong>Log in:</strong> Go to the application homepage and sign in</li>
                        <li><strong>Connect integrations:</strong> Go to Settings to connect Meta/Instagram and Google accounts</li>
                    </ol>
                </div>
                <a href="/" class="next-link">Go to CreatorzHive →</a>
            </div>

        <?php elseif (!$isLocalhost): ?>
            <h1>⚠ Access Denied</h1>
            <div class="subtitle">For security, setup is restricted to localhost</div>
            <div class="alert alert-error">
                Your IP address is: <strong><?= htmlspecialchars($remoteIp) ?></strong>
                <br><br>
                Setup endpoints should only be accessible from your local machine or trusted network.
                <br><br>
                To allow your IP, add this to your .env file:
                <br><code style="background: #f5f5f5; padding: 2px 6px;">SETUP_ALLOWED_IP=<?= htmlspecialchars($remoteIp) ?></code>
            </div>

        <?php else: ?>
            <h1>CreatorzHive Setup</h1>
            <div class="subtitle">Configure your installation for production deployment</div>

            <?php if ($dbError || $migrationError || $seedError || $adminError): ?>
                <div class="alert alert-error">
                    <strong>Setup Error:</strong><br>
                    <?php
                    if ($dbError) echo "Database: " . htmlspecialchars($dbError) . "<br>";
                    if ($migrationError) echo "Migration: " . htmlspecialchars($migrationError) . "<br>";
                    if ($seedError) echo "Seeding: " . htmlspecialchars($seedError) . "<br>";
                    if ($adminError) echo "Admin User: " . htmlspecialchars($adminError) . "<br>";
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Database Status -->
                <div class="form-section">
                    <h3>✓ Database Configuration</h3>
                    <p style="font-size: 14px; margin-bottom: 10px;">
                        Connected to: <strong><?= htmlspecialchars($dbHost) ?>/<?= htmlspecialchars($dbDatabase) ?></strong>
                    </p>
                    <div class="alert alert-info">
                        Make sure you've already:
                        <ol style="margin-left: 20px; margin-top: 8px;">
                            <li>Created a MySQL database via InfinityFree control panel</li>
                            <li>Edited <code>.env</code> with your database credentials</li>
                        </ol>
                    </div>
                </div>

                <!-- Migration -->
                <div class="form-section">
                    <h3>📦 Database Migrations</h3>
                    <div class="checkbox-group">
                        <input type="checkbox" id="run_migration" name="run_migration" value="1" checked required>
                        <label for="run_migration" class="checkbox-label">Run database migrations (create tables and schema)</label>
                    </div>
                    <p class="text-muted">Required: This creates all necessary database tables for CreatorzHive</p>
                </div>

                <!-- Seeding -->
                <div class="form-section">
                    <h3>🌱 Demo Data (Optional)</h3>
                    <div class="checkbox-group">
                        <input type="checkbox" id="run_seeding" name="run_seeding" value="1">
                        <label for="run_seeding" class="checkbox-label">Seed demo data (users, posts, analytics)</label>
                    </div>
                    <p class="text-muted">Optional: Populate sample data for testing. You can skip this and start fresh.</p>
                </div>

                <!-- Admin User -->
                <div class="form-section">
                    <h3>👤 Admin User</h3>
                    <div class="form-group">
                        <label for="admin_email">Admin Email Address</label>
                        <input type="email" id="admin_email" name="admin_email" required placeholder="admin@example.com">
                    </div>
                    <div class="form-group">
                        <label for="admin_name">Admin Full Name</label>
                        <input type="text" id="admin_name" name="admin_name" required placeholder="Your Name">
                    </div>
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input type="password" id="admin_password" name="admin_password" required placeholder="min. 8 characters">
                        <p class="text-muted">Use a strong password with letters, numbers, and symbols</p>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit">Complete Setup</button>
                </div>
            </form>

            <p class="text-muted" style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
                After setup, remember to:<br>
                • Delete <code>public/setup.php</code> for security<br>
                • Configure background job webhook trigger<br>
                • Set up SSL certificate (HTTPS)
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
