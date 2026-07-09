<?php

declare(strict_types=1);

router_get_action('home', CreatorzHive\Controllers\SystemController::class, 'home');

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
router_get_action('admin-settings', CreatorzHive\Controllers\AdminUserController::class, 'settingsPage', ['auth', 'role:admin']);
router_get_action('admin-overview', CreatorzHive\Controllers\AdminUserController::class, 'overviewPage', ['auth', 'role:admin']);
router_get_action('admin-security', CreatorzHive\Controllers\AdminUserController::class, 'securityPage', ['auth', 'role:admin']);

router_get_action('google-auth', CreatorzHive\Controllers\GoogleAuthController::class, 'start');
router_get_action('google-callback', CreatorzHive\Controllers\GoogleAuthController::class, 'callback');

router_get_action('instagram-connect', CreatorzHive\Controllers\InstagramOAuthController::class, 'connectStart', ['auth']);
router_get_action('instagram-callback', CreatorzHive\Controllers\InstagramOAuthController::class, 'callbackHandler');

router_get_action('youtube-connect', CreatorzHive\Controllers\YoutubeOAuthController::class, 'connectStart', ['auth']);
router_get_action('youtube-callback', CreatorzHive\Controllers\YoutubeOAuthController::class, 'callbackHandler');

router_get_action('tiktok-connect', CreatorzHive\Controllers\TiktokOAuthController::class, 'connectStart', ['auth']);
router_get_action('tiktok-callback', CreatorzHive\Controllers\TiktokOAuthController::class, 'callbackHandler');

router_get_action('privacy-policy', CreatorzHive\Controllers\SystemController::class, 'privacyPolicy');
router_get_action('terms-of-service', CreatorzHive\Controllers\SystemController::class, 'termsOfService');
