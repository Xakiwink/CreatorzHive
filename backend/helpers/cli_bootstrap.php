<?php

declare(strict_types=1);

/**
 * Shared bootstrap for CLI scripts (cron, migrate, seed, verify, etc.).
 */

function cli_project_root(): string
{
    return dirname(__DIR__, 2);
}

function cli_load_env(): void
{
    require_once cli_project_root() . '/backend/helpers/functions.php';
    load_env(base_path('.env'));
}

function cli_boot_oop(bool $procedural = true): void
{
    static $booted = false;
    if ($booted) {
        return;
    }

    $root = cli_project_root();
    $composer = $root . '/vendor/autoload.php';
    if (is_file($composer)) {
        require_once $composer;
    }

    require_once $root . '/backend/bootstrap-oop.php';
    require_once $root . '/backend/core/database.php';

    if ($procedural) {
        require_once $root . '/backend/bootstrap-procedural.php';
    }

    $booted = true;
}

/**
 * PDO for schema/migration scripts (uses OOP Connection when available).
 */
function cli_pdo(): PDO
{
    cli_boot_oop(false);

    if (class_exists(\CreatorzHive\Core\Application::class)) {
        $app = \CreatorzHive\Core\Application::instance();
        if ($app !== null) {
            return $app->get(\CreatorzHive\Core\Database\Connection::class)->pdo();
        }
    }

    require_once cli_project_root() . '/backend/core/database.php';

    return db_connection();
}
