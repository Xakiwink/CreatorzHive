<?php

declare(strict_types=1);

router_get_action('ping', CreatorzHive\Controllers\SystemController::class, 'ping');
router_get_action('db-test', CreatorzHive\Controllers\SystemController::class, 'dbTest', ['auth', 'role:admin']);

router_get_action('api_me', CreatorzHive\Controllers\ApiMetaController::class, 'systemApiMe', ['auth']);
router_get_action('api_catalog', CreatorzHive\Controllers\ApiMetaController::class, 'systemApiCatalog', ['auth']);

router_get_action('check_username', CreatorzHive\Controllers\AuthController::class, 'checkUsername');
router_get_action('verify', CreatorzHive\Controllers\AuthController::class, 'verify');
router_post_action('register', CreatorzHive\Controllers\AuthController::class, 'register', ['csrf']);
router_post_action('login', CreatorzHive\Controllers\AuthController::class, 'login', ['csrf']);
router_post_action('logout', CreatorzHive\Controllers\AuthController::class, 'logout', ['auth', 'csrf']);
router_post_action('forgot-password', CreatorzHive\Controllers\AuthController::class, 'forgotPassword', ['csrf']);
router_post_action('reset-password', CreatorzHive\Controllers\AuthController::class, 'resetPassword', ['csrf']);
router_post_action('resend-verification', CreatorzHive\Controllers\AuthController::class, 'resendVerification', ['csrf']);

router_get_action('posts', CreatorzHive\Controllers\PostController::class, 'index', ['auth', 'non_admin']);
router_get_action('posts_calendar', CreatorzHive\Controllers\PostController::class, 'calendar', ['auth', 'non_admin']);
router_get_action('post', CreatorzHive\Controllers\PostController::class, 'show', ['auth', 'non_admin']);
router_post_action('create_post', CreatorzHive\Controllers\PostController::class, 'store', ['auth', 'non_admin', 'csrf']);
router_post_action('update_post', CreatorzHive\Controllers\PostController::class, 'update', ['auth', 'non_admin', 'csrf']);
router_post_action('delete_post', CreatorzHive\Controllers\PostController::class, 'destroy', ['auth', 'non_admin', 'csrf']);
router_post_action('duplicate_post', CreatorzHive\Controllers\PostController::class, 'duplicate', ['auth', 'non_admin', 'csrf']);
router_post_action('bulk_posts', CreatorzHive\Controllers\PostController::class, 'bulk', ['auth', 'non_admin', 'csrf']);

router_post_action('upload_media', CreatorzHive\Controllers\MediaController::class, 'upload', ['auth', 'non_admin', 'csrf']);
router_get_action('media_list', CreatorzHive\Controllers\MediaController::class, 'list', ['auth', 'non_admin']);
router_post_action('delete_media', CreatorzHive\Controllers\MediaController::class, 'delete', ['auth', 'non_admin', 'csrf']);

router_get_action('tags', CreatorzHive\Controllers\TagController::class, 'index', ['auth', 'non_admin']);
router_post_action('create_tag', CreatorzHive\Controllers\TagController::class, 'store', ['auth', 'non_admin', 'csrf']);

router_get_action('analytics_data', CreatorzHive\Controllers\AnalyticsController::class, 'data', ['auth', 'non_admin']);
router_post_action('seed_analytics', CreatorzHive\Controllers\AnalyticsController::class, 'seed', ['auth', 'non_admin', 'csrf']);

router_get_action('deals_data', CreatorzHive\Controllers\DealController::class, 'data', ['auth', 'non_admin']);
router_get_action('deal', CreatorzHive\Controllers\DealController::class, 'show', ['auth', 'non_admin']);
router_post_action('create_deal', CreatorzHive\Controllers\DealController::class, 'store', ['auth', 'non_admin', 'csrf']);
router_post_action('update_deal', CreatorzHive\Controllers\DealController::class, 'update', ['auth', 'non_admin', 'csrf']);
router_post_action('update_deal_status', CreatorzHive\Controllers\DealController::class, 'updateStatus', ['auth', 'non_admin', 'csrf']);
router_post_action('delete_deal', CreatorzHive\Controllers\DealController::class, 'destroy', ['auth', 'non_admin', 'csrf']);

router_get_action('invoices_data', CreatorzHive\Controllers\InvoiceController::class, 'list', ['auth', 'non_admin']);
router_get_action('invoice', CreatorzHive\Controllers\InvoiceController::class, 'show', ['auth', 'non_admin']);
router_post_action('create_invoice', CreatorzHive\Controllers\InvoiceController::class, 'store', ['auth', 'non_admin', 'csrf']);
router_post_action('update_invoice', CreatorzHive\Controllers\InvoiceController::class, 'update', ['auth', 'non_admin', 'csrf']);
router_post_action('mark_invoice_paid', CreatorzHive\Controllers\InvoiceController::class, 'markPaid', ['auth', 'non_admin', 'csrf']);

router_get_action('notifications_data', CreatorzHive\Controllers\NotificationController::class, 'data', ['auth']);
router_get_action('notifications_count', CreatorzHive\Controllers\NotificationController::class, 'unreadCount', ['auth']);
router_post_action('mark_read', CreatorzHive\Controllers\NotificationController::class, 'postMarkRead', ['auth', 'csrf']);
router_post_action('mark_all_read', CreatorzHive\Controllers\NotificationController::class, 'postMarkAllRead', ['auth', 'csrf']);
router_post_action('delete_notification', CreatorzHive\Controllers\NotificationController::class, 'postDelete', ['auth', 'csrf']);
router_post_action('delete_read_notifications', CreatorzHive\Controllers\NotificationController::class, 'postDeleteRead', ['auth', 'csrf']);

router_get_action('profile_data', CreatorzHive\Controllers\SettingsController::class, 'profileData', ['auth']);
router_post_action('update_profile', CreatorzHive\Controllers\SettingsController::class, 'updateProfile', ['auth', 'csrf']);
router_post_action('update_password', CreatorzHive\Controllers\SettingsController::class, 'updatePassword', ['auth', 'csrf']);
router_get_action('user_sessions', CreatorzHive\Controllers\SettingsController::class, 'getSessions', ['auth']);
router_post_action('revoke_session', CreatorzHive\Controllers\SettingsController::class, 'revokeSession', ['auth', 'csrf']);
router_post_action('revoke_all_sessions', CreatorzHive\Controllers\SettingsController::class, 'revokeAllSessions', ['auth', 'csrf']);
router_get_action('integrations_data', CreatorzHive\Controllers\SettingsController::class, 'integrationsData', ['auth']);
router_post_action('connect_platform', CreatorzHive\Controllers\SettingsController::class, 'connectPlatform', ['auth', 'csrf']);
router_post_action('disconnect_platform', CreatorzHive\Controllers\SettingsController::class, 'disconnectPlatform', ['auth', 'csrf']);
router_get_action('notification_prefs', CreatorzHive\Controllers\SettingsController::class, 'notificationPrefs', ['auth']);
router_post_action('update_notification_prefs', CreatorzHive\Controllers\SettingsController::class, 'updateNotificationPrefs', ['auth', 'csrf']);
router_post_action('update_preferences', CreatorzHive\Controllers\SettingsController::class, 'updatePreferences', ['auth', 'csrf']);

router_get_action('dashboard_data', CreatorzHive\Controllers\DashboardController::class, 'data', ['auth']);

router_get_action('admin_users', CreatorzHive\Controllers\AdminUserController::class, 'usersIndex', ['auth', 'role:admin']);
router_post_action('admin_create_user', CreatorzHive\Controllers\AdminUserController::class, 'usersStore', ['auth', 'role:admin', 'csrf']);
router_post_action('admin_update_user', CreatorzHive\Controllers\AdminUserController::class, 'usersUpdate', ['auth', 'role:admin', 'csrf']);
router_post_action('admin_delete_user', CreatorzHive\Controllers\AdminUserController::class, 'usersDestroy', ['auth', 'role:admin', 'csrf']);
router_post_action('admin_verify_user', CreatorzHive\Controllers\AdminUserController::class, 'usersVerify', ['auth', 'role:admin', 'csrf']);
router_get_action('admin_overview', CreatorzHive\Controllers\AdminUserController::class, 'platformOverview', ['auth', 'role:admin']);
router_post_action('admin_update_settings', CreatorzHive\Controllers\AdminUserController::class, 'settingsUpdate', ['auth', 'role:admin', 'csrf']);
router_get_action('admin_audit_logs', CreatorzHive\Controllers\AdminUserController::class, 'auditLogsIndex', ['auth', 'role:admin']);
router_get_action('admin_test_integration', CreatorzHive\Controllers\AdminUserController::class, 'integrationTest', ['auth', 'role:admin']);
router_get_action('admin_security_activity', CreatorzHive\Controllers\AdminUserController::class, 'securityActivity', ['auth', 'role:admin']);
