#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * One-time migration: encrypt plaintext access_token / refresh_token in social_accounts.
 *
 *   php scripts/encrypt-social-tokens.php
 */

require_once __DIR__ . '/../backend/helpers/cli_bootstrap.php';
cli_load_env();
cli_boot_oop(true);

if (trim((string) env('APP_SECRET', '')) === '') {
    fwrite(STDERR, "Warning: APP_SECRET is empty; using insecure dev key.\n");
}

$result = social_account_migrate_plaintext_tokens();

echo sprintf(
    "Done. scanned=%d encrypted=%d already_encrypted_or_empty=%d\n",
    (int) $result['scanned'],
    (int) $result['encrypted'],
    (int) $result['skipped']
);
