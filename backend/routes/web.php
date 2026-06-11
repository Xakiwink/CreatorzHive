<?php

declare(strict_types=1);

router_get_action('login', CreatorzHive\Controllers\AuthController::class, 'loginPage');
router_get_action('register', CreatorzHive\Controllers\AuthController::class, 'registerPage');
router_get_action('forgot-password', CreatorzHive\Controllers\AuthController::class, 'forgotPage');
router_get_action('reset-password', CreatorzHive\Controllers\AuthController::class, 'resetPage');
router_get_action('verify-email', CreatorzHive\Controllers\AuthController::class, 'verifyPage');
router_get_action('logout', CreatorzHive\Controllers\AuthController::class, 'logoutPage');

router_get_action('dashboard', CreatorzHive\Controllers\DashboardController::class, 'index', ['auth']);
router_get_action('planner', CreatorzHive\Controllers\PostController::class, 'plannerPage', ['auth', 'non_admin']);
router_get_action('analytics', CreatorzHive\Controllers\AnalyticsController::class, 'index', ['auth', 'non_admin']);
router_get_action('deals', CreatorzHive\Controllers\DealController::class, 'index', ['auth', 'non_admin']);
router_get_action('invoices', CreatorzHive\Controllers\InvoiceController::class, 'index', ['auth', 'non_admin']);
router_get_action('media', CreatorzHive\Controllers\MediaController::class, 'index', ['auth', 'non_admin']);
router_get_action('notifications', CreatorzHive\Controllers\NotificationController::class, 'index', ['auth']);
router_get_action('settings', CreatorzHive\Controllers\SettingsController::class, 'profile', ['auth']);
router_get_action('settings-profile', CreatorzHive\Controllers\SettingsController::class, 'profile', ['auth']);
router_get_action('settings-security', CreatorzHive\Controllers\SettingsController::class, 'security', ['auth']);
router_get_action('settings-integrations', CreatorzHive\Controllers\SettingsController::class, 'integrations', ['auth']);
router_get_action('settings-notifications', CreatorzHive\Controllers\SettingsController::class, 'notifications', ['auth']);
router_get_action('settings-preferences', CreatorzHive\Controllers\SettingsController::class, 'preferences', ['auth']);
router_get_action('admin-users', CreatorzHive\Controllers\AdminUserController::class, 'usersPage', ['auth', 'role:admin']);

router_get_action('google-auth', CreatorzHive\Controllers\GoogleAuthController::class, 'start');
router_get_action('google-callback', CreatorzHive\Controllers\GoogleAuthController::class, 'callback');

router_get_action('oauth-connect', CreatorzHive\Controllers\OauthController::class, 'connectStart', ['auth']);
router_get_action('oauth-callback', CreatorzHive\Controllers\OauthController::class, 'callbackHandler');
