#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Seed demo data.
 *
 *   php scripts/seed.php           Run all seeds (expects schema already applied)
 *   php scripts/seed.php --fresh Drop & recreate DB from schema, then seed
 *   php scripts/seed.php users     Run only database/seeds/users.sql
 */

require_once __DIR__ . '/../backend/helpers/cli_bootstrap.php';
cli_load_env();

$argvList = array_slice($argv, 1);
$fresh = in_array('--fresh', $argvList, true);
$only = null;
foreach ($argvList as $a) {
    if ($a !== '--fresh' && $a !== '' && strpos($a, '-') !== 0) {
        $only = $a;
        break;
    }
}

$dbName = (string) env('DB_DATABASE', 'creatorz_hive');
$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$user = (string) env('DB_USERNAME', 'root');
$pass = (string) env('DB_PASSWORD', '');

$seedDir = base_path('database/seeds');
$order = ['users.sql', 'posts.sql', 'analytics.sql', 'deals.sql', 'notifications.sql'];

if ($fresh) {
    echo "[FRESH] Recreating database {$dbName}..." . PHP_EOL;

    $adminDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
    $pdo = new PDO($adminDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('DROP DATABASE IF EXISTS `' . str_replace('`', '``', $dbName) . '`');
    $pdo->exec('CREATE DATABASE `' . str_replace('`', '``', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $schema = base_path('database/schema.sql');
    if (!is_file($schema)) {
        $schema = base_path('schema.sql');
    }
    if (!is_file($schema)) {
        fwrite(STDERR, "Schema file not found.\n");
        exit(1);
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
        $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
    }

    $sql = file_get_contents($schema);
    if ($sql === false) {
        exit(1);
    }
    $pdo->exec($sql);

    $migrations = glob(base_path('database/migrations/*.sql'));
    sort($migrations);
    foreach ($migrations as $m) {
        $pdo->exec((string) file_get_contents($m));
    }

    echo "[OK] Schema applied.\n";
} else {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

$runFile = static function (string $path) use ($pdo): void {
    if (!is_file($path)) {
        fwrite(STDERR, "[SKIP] Missing: {$path}\n");

        return;
    }
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        return;
    }
    $pdo->exec($sql);
    echo '[OK] ' . basename($path) . PHP_EOL;
};

if ($only !== null) {
    $path = $seedDir . '/' . $only;
    if (strlen($path) < 4 || substr($path, -4) !== '.sql') {
        $path .= '.sql';
    }
    if (!is_file($path)) {
        $path = $seedDir . '/' . $only . '.sql';
    }
    $runFile($path);
    echo "Done.\n";
    exit(0);
}

foreach ($order as $file) {
    $runFile($seedDir . '/' . $file);
}

echo "Done.\n";
