<?php
/**
 * Generate bcrypt password hash for manual database user creation
 *
 * Usage:
 *   php scripts/hash-password.php MyPassword123
 *
 * Output:
 *   Hash: $2y$12$....
 *
 * Then copy the hash and paste into phpMyAdmin's users table password field
 */

if (php_sapi_name() !== 'cli') {
    echo "Error: This script must be run from command line\n";
    echo "Usage: php scripts/hash-password.php <password>\n";
    exit(1);
}

$password = $argv[1] ?? 'Admin@1234';

if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "\n";
echo "Password: " . $password . "\n";
echo "Hash:     " . $hash . "\n";
echo "\n";
echo "📋 Copy the hash above and paste it into phpMyAdmin's users table:\n";
echo "   1. Open phpMyAdmin\n";
echo "   2. Click 'users' table\n";
echo "   3. Click 'Insert'\n";
echo "   4. Paste hash into 'password' field\n";
echo "\n";
