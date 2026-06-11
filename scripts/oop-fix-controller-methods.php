#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/** @var array<string, array<string, string>> */
$renames = [
    'PostController.php' => [
        'sIndex' => 'index',
        'sCalendar' => 'calendar',
        'sShow' => 'show',
        'sStore' => 'store',
        'sUpdate' => 'update',
        'sDestroy' => 'destroy',
        'sDuplicate' => 'duplicate',
        'sBulk' => 'bulk',
    ],
    'DealController.php' => [
        'sIndex' => 'index',
        'sData' => 'data',
        'sShow' => 'show',
        'sStore' => 'store',
        'sUpdate' => 'update',
        'sUpdateStatus' => 'updateStatus',
        'sDestroy' => 'destroy',
    ],
    'InvoiceController.php' => [
        'sIndex' => 'index',
        'sList' => 'list',
        'sShow' => 'show',
        'sStore' => 'store',
        'sUpdate' => 'update',
        'sMarkPaid' => 'markPaid',
    ],
    'NotificationController.php' => [
        'sIndex' => 'index',
        'sData' => 'data',
        'sUnreadCount' => 'unreadCount',
        'sPostMarkRead' => 'postMarkRead',
        'sPostMarkAllRead' => 'postMarkAllRead',
        'sPostDelete' => 'postDelete',
        'sPostDeleteRead' => 'postDeleteRead',
    ],
    'AdminUserController.php' => [
        'sPage' => 'usersPage',
        'sIndex' => 'usersIndex',
        'sStore' => 'usersStore',
        'sUpdate' => 'usersUpdate',
        'sDestroy' => 'usersDestroy',
        'sVerify' => 'usersVerify',
        'adminPlatformOverview' => 'platformOverview',
        'adminPlatformCredentials' => 'platformCredentials',
        'adminUpdatePlatformCredentials' => 'updatePlatformCredentials',
        'adminSettingsUpdate' => 'settingsUpdate',
        'adminIntegrationTest' => 'integrationTest',
        'adminAuditLogsIndex' => 'auditLogsIndex',
    ],
];

foreach ($renames as $file => $map) {
    $path = $root . '/src/Controllers/' . $file;
    if (!is_file($path)) {
        continue;
    }
    $code = file_get_contents($path);
    foreach ($map as $from => $to) {
        $code = str_replace('function ' . $from . '(', 'function ' . $to . '(', $code);
    }
    file_put_contents($path, $code);
    echo "Fixed {$file}\n";
}

echo "Done.\n";
