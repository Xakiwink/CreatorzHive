<?php

declare(strict_types=1);

// Auto-generated model compat → OOP repositories.

function user_find_by_id(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->findById(...func_get_args());
}

function user_find_by_email(string $email)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->findByEmail(...func_get_args());
}

function user_find_by_username(string $username)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->findByUsername(...func_get_args());
}

function user_create(array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->create(...func_get_args());
}

function user_update(int $id, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->update(...func_get_args());
}

function user_update_last_login(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->updateLastLogin(...func_get_args());
}

function user_verify_email(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->verifyEmail(...func_get_args());
}

function user_update_password(int $id, string $hashedPassword)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->updatePassword(...func_get_args());
}

function user_is_email_taken(string $email)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->isEmailTaken(...func_get_args());
}

function user_is_username_taken(string $username)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->isUsernameTaken(...func_get_args());
}

function user_list_all(int $limit = 200, int $offset = 0)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->listAll(...func_get_args());
}

function user_count_all()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserRepository::class)->countAll(...func_get_args());
}

function deal_statuses_list()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->statusesList(...func_get_args());
}

function deal_types_list()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->typesList(...func_get_args());
}

function deal_statuses()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->statuses(...func_get_args());
}

function deal_types()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->types(...func_get_args());
}

function deal_get_by_user(int $userId, array $filters = [])
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->getByUser(...func_get_args());
}

function deal_get_kanban(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->getKanban(...func_get_args());
}

function deal_normalize_deal_row(array $row)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->normalizeDealRow(...func_get_args());
}

function deal_find_by_id(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->findById(...func_get_args());
}

function deal_find_for_user(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->findForUser(...func_get_args());
}

function deal_prepare_row(array $data, bool $forInsert)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->prepareRow(...func_get_args());
}

function deal_create(array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->create(...func_get_args());
}

function deal_update(int $id, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->update(...func_get_args());
}

function deal_update_status(int $id, string $status, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->updateStatus(...func_get_args());
}

function deal_soft_delete(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->softDelete(...func_get_args());
}

function deal_attach_post(int $dealId, int $postId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->attachPost(...func_get_args());
}

function deal_get_revenue_summary(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->getRevenueSummary(...func_get_args());
}

function deal_get_recent_activity(int $userId, int $limit = 10)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->getRecentActivity(...func_get_args());
}

function deal_get_activity_for_deal(int $dealId, int $userId, int $limit = 25)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->getActivityForDeal(...func_get_args());
}

function deal_get_linked_posts(int $dealId, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\DealRepository::class)->getLinkedPosts(...func_get_args());
}

function invoice_statuses_list()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->statusesList(...func_get_args());
}

function invoice_statuses()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->statuses(...func_get_args());
}

function invoice_get_by_user(int $userId, array $filters = [])
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->getByUser(...func_get_args());
}

function invoice_find_by_id(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->findById(...func_get_args());
}

function invoice_find_for_user(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->findForUser(...func_get_args());
}

function invoice_create(array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->create(...func_get_args());
}

function invoice_update(int $id, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->update(...func_get_args());
}

function invoice_generate_number(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->generateNumber(...func_get_args());
}

function invoice_mark_paid(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->markPaid(...func_get_args());
}

function invoice_get_by_deal(int $dealId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->getByDeal(...func_get_args());
}

function invoice_normalize_row(array $row)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->normalizeRow(...func_get_args());
}

function invoice_sum_paid_total(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\InvoiceRepository::class)->sumPaidTotal(...func_get_args());
}

function tag_get_by_user(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\TagRepository::class)->getByUser(...func_get_args());
}

function tag_find_by_name(int $userId, string $name)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\TagRepository::class)->findByName(...func_get_args());
}

function tag_create(int $userId, string $name, string $color)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\TagRepository::class)->create(...func_get_args());
}

function tag_delete(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\TagRepository::class)->delete(...func_get_args());
}

function notification_get_by_user(int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0, ?string $category = null)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->getByUser(...func_get_args());
}

function notification_count_for_user(int $userId, bool $unreadOnly = false, ?string $category = null)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->countForUser(...func_get_args());
}

function notification_count_unread(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->countUnread(...func_get_args());
}

function notification_mark_read(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->markRead(...func_get_args());
}

function notification_mark_all_read(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->markAllRead(...func_get_args());
}

function notification_create(int $userId, string $type, string $title, string $body = '', string $actionUrl = '', ?string $icon = null)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->create(...func_get_args());
}

function notification_delete(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->delete(...func_get_args());
}

function notification_delete_read(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationRepository::class)->deleteRead(...func_get_args());
}

function notification_preference_get_by_user_id(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationPreferenceRepository::class)->preferenceGetByUserId(...func_get_args());
}

function notification_preference_upsert(int $userId, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\NotificationPreferenceRepository::class)->preferenceUpsert(...func_get_args());
}

function user_preferences_get_by_user_id(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserPreferencesRepository::class)->preferencesGetByUserId(...func_get_args());
}

function user_preferences_upsert(int $userId, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserPreferencesRepository::class)->preferencesUpsert(...func_get_args());
}

function user_session_touch(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserSessionRepository::class)->touch(...func_get_args());
}

function user_session_destroy_by_id(string $sessionId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserSessionRepository::class)->destroyById(...func_get_args());
}

function user_session_list_for_user(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserSessionRepository::class)->listForUser(...func_get_args());
}

function user_session_revoke(string $sessionId, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserSessionRepository::class)->revoke(...func_get_args());
}

function user_session_revoke_others(int $userId, string $currentSessionId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\UserSessionRepository::class)->revokeOthers(...func_get_args());
}

function media_file_get_by_user(int $userId, ?string $type = null)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\MediaFileRepository::class)->fileGetByUser(...func_get_args());
}

function media_file_find_by_id(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\MediaFileRepository::class)->fileFindById(...func_get_args());
}

function media_file_find_by_id_for_user(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\MediaFileRepository::class)->fileFindByIdForUser(...func_get_args());
}

function media_file_create(array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\MediaFileRepository::class)->fileCreate(...func_get_args());
}

function media_file_delete(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\MediaFileRepository::class)->fileDelete(...func_get_args());
}

function social_account_decrypt_row(array $row)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountDecryptRow(...func_get_args());
}

function social_account_encrypt_token(string $plaintext)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountEncryptToken(...func_get_args());
}

function social_account_fetch(int $userId, string $platform, bool $activeOnly = true)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountFetch(...func_get_args());
}

function social_account_fetch_by_id(int $accountId, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountFetchById(...func_get_args());
}

function social_account_upsert(int $userId, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountUpsert(...func_get_args());
}

function social_account_update_tokens(int $accountId, string $accessToken, string $refreshToken, string $expiresAt): void
{
    \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountUpdateTokens(...func_get_args());
}

function social_account_migrate_plaintext_tokens()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\SocialAccountRepository::class)->accountMigratePlaintextTokens(...func_get_args());
}

function job_queue_publish_job_type()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\JobQueueRepository::class)->queuePublishJobType(...func_get_args());
}

function job_queue_legacy_publish_class()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\JobQueueRepository::class)->queueLegacyPublishClass(...func_get_args());
}

function job_queue_enqueue_publish_post(int $postId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\JobQueueRepository::class)->queueEnqueuePublishPost(...func_get_args());
}

function job_queue_cancel_pending_publish_for_post(int $postId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\JobQueueRepository::class)->queueCancelPendingPublishForPost(...func_get_args());
}

function audit_log_create(
    ?int $userId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    array $oldValues = [],
    array $newValues = []
)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AuditLogRepository::class)->create(...func_get_args());
}

function audit_log_list_recent(int $limit = 100)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AuditLogRepository::class)->listRecent(...func_get_args());
}

function analytics_sql_with_platform_filter(string $sql, array $params, ?string $platform, bool $allPlatformsWhenEmpty = true)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->sqlWithPlatformFilter(...func_get_args());
}

function analytics_get_by_user(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getByUser(...func_get_args());
}

function analytics_upsert(int $userId, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->upsert(...func_get_args());
}

function analytics_recalculate(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->recalculate(...func_get_args());
}

function analytics_get_snapshots(int $userId, string $period, string $startDate, string $endDate, ?string $platform = null)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getSnapshots(...func_get_args());
}

function analytics_sum_followers_on_date(int $userId, string $date, ?string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->sumFollowersOnDate(...func_get_args());
}

function analytics_sum_metrics_in_range(int $userId, string $startDate, string $endDate, ?string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->sumMetricsInRange(...func_get_args());
}

function analytics_has_snapshot_data(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->hasSnapshotData(...func_get_args());
}

function analytics_get_growth_trend(int $userId, string $startDate, string $endDate, ?string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getGrowthTrend(...func_get_args());
}

function analytics_get_daily_rollup_trend(int $userId, string $startDate, string $endDate, ?string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getDailyRollupTrend(...func_get_args());
}

function analytics_get_engagement_trend(int $userId, string $startDate, string $endDate, ?string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getEngagementTrend(...func_get_args());
}

function analytics_get_posting_frequency(int $userId, string $granularity, string $startDate, string $endDate)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getPostingFrequency(...func_get_args());
}

function analytics_get_platform_breakdown(int $userId, string $startDate, string $endDate, ?string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getPlatformBreakdown(...func_get_args());
}

function analytics_get_top_posts(int $userId, int $limit = 5, ?string $platform = null)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->getTopPosts(...func_get_args());
}

function analytics_count_published_in_range(int $userId, string $startDate, string $endDate)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->countPublishedInRange(...func_get_args());
}

function analytics_first_platform_slug($platforms)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\AnalyticsRepository::class)->firstPlatformSlug(...func_get_args());
}

function post_get_recent_by_user(int $userId, int $limit = 5)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getRecentByUser(...func_get_args());
}

function post_count_by_status(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->countByStatus(...func_get_args());
}

function post_get_upcoming(int $userId, int $limit = 5)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getUpcoming(...func_get_args());
}

function post_find_by_id(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->findById(...func_get_args());
}

function post_find_by_id_for_user(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->findByIdForUser(...func_get_args());
}

function post_create(array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->create(...func_get_args());
}

function post_save(int $id, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->save(...func_get_args());
}

function post_soft_delete(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->softDelete(...func_get_args());
}

function post_get_calendar_posts(int $userId, string $month, string $year)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getCalendarPosts(...func_get_args());
}

function post_get_all_by_user(int $userId, array $filters = [])
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getAllByUser(...func_get_args());
}

function post_attach_tags_to_posts(array $posts)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->attachTagsToPosts(...func_get_args());
}

function post_get_with_media(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getWithMedia(...func_get_args());
}

function post_get_with_tags(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getWithTags(...func_get_args());
}

function post_get_full_for_user(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->getFullForUser(...func_get_args());
}

function post_attach_media(int $postId, int $mediaId, int $sortOrder = 0)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->attachMedia(...func_get_args());
}

function post_detach_media(int $postId, int $mediaId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->detachMedia(...func_get_args());
}

function post_detach_all_media(int $postId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->detachAllMedia(...func_get_args());
}

function post_sync_media(int $postId, array $mediaIdsOrdered)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->syncMedia(...func_get_args());
}

function post_attach_tag(int $postId, int $tagId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->attachTag(...func_get_args());
}

function post_detach_all_tags(int $postId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->detachAllTags(...func_get_args());
}

function post_sync_tags(int $postId, array $tagIds)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->syncTags(...func_get_args());
}

function post_publish(int $id)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->publish(...func_get_args());
}

function post_mark_failed(int $id, string $reason)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->markFailed(...func_get_args());
}

function post_duplicate(int $id, int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->duplicate(...func_get_args());
}

function post_normalize_row(array $row)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Repositories\PostRepository::class)->normalizeRow(...func_get_args());
}
