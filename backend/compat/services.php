<?php

declare(strict_types=1);


// Auto-generated service compat → OOP services.

function admin_service_settings_defaults()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->settingsDefaults(...func_get_args());
}

function admin_service_settings_path()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->settingsPath(...func_get_args());
}

function admin_service_settings_get_all()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->settingsGetAll(...func_get_args());
}

function admin_service_settings_save(array $settings)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->settingsSave(...func_get_args());
}

function admin_service_setting_bool(string $key, bool $default)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->settingBool(...func_get_args());
}

function admin_service_platform_summary()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->platformSummary(...func_get_args());
}

function admin_service_integration_providers()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->integrationProviders(...func_get_args());
}

function admin_service_integration_enabled(string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->integrationEnabled(...func_get_args());
}

function admin_service_integration_statuses()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->integrationStatuses(...func_get_args());
}

function admin_service_validate_saved_credentials(string $group, array $savedFieldKeys)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AdminService::class)->validateSavedCredentials(...func_get_args());
}

function meta_oauth_is_configured()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->isConfigured(...func_get_args());
}

function meta_oauth_redirect_uri()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->redirectUri(...func_get_args());
}

function meta_oauth_allowed_platforms()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->allowedPlatforms(...func_get_args());
}

function meta_oauth_scopes(string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->scopes(...func_get_args());
}

function meta_oauth_authorize_url(string $platform, string $state)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->authorizeUrl(...func_get_args());
}

function meta_oauth_exchange_code(string $code)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->exchangeCode(...func_get_args());
}

function meta_oauth_long_lived_token(string $shortToken)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->longLivedToken(...func_get_args());
}

function meta_oauth_fetch_pages(string $userAccessToken)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->fetchPages(...func_get_args());
}

function meta_oauth_save_facebook_page(int $userId, array $page)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->saveFacebookPage(...func_get_args());
}


function meta_oauth_upsert_social_account(int $userId, array $data)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->upsertSocialAccount(...func_get_args());
}

function meta_oauth_complete_connection(int $userId, string $platform, string $code)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\MetaOAuthService::class)->completeConnection(...func_get_args());
}

function youtube_oauth_is_configured()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\YoutubeOAuthService::class)->isConfigured(...func_get_args());
}

function youtube_oauth_redirect_uri()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\YoutubeOAuthService::class)->redirectUri(...func_get_args());
}

function youtube_oauth_complete_connection(int $userId, string $code)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\YoutubeOAuthService::class)->completeConnection(...func_get_args());
}

function platform_api_secrets_storage_path()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->storagePath(...func_get_args());
}

function platform_api_secrets_credential_groups()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->credentialGroups(...func_get_args());
}

function platform_api_secrets_all_groups_public()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->allGroupsPublic(...func_get_args());
}

function platform_api_secrets_group(string $group)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->group(...func_get_args());
}

function platform_api_secrets_field_definition(string $fieldKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->fieldDefinition(...func_get_args());
}

function platform_api_secrets_encrypt(string $plaintext)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->encrypt(...func_get_args());
}

function platform_api_secrets_decrypt(string $encoded)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->decrypt(...func_get_args());
}

function platform_api_secrets_load_store()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->loadStore(...func_get_args());
}

function platform_api_secrets_save_store(array $store)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->saveStore(...func_get_args());
}

function platform_api_secrets_stored_value(string $fieldKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->storedValue(...func_get_args());
}

function platform_api_secrets_resolve(string $fieldKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->resolve(...func_get_args());
}

function platform_api_secrets_resolve_env(string $envKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->resolveEnv(...func_get_args());
}

function platform_api_secrets_mask(string $value)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->mask(...func_get_args());
}

function platform_api_secrets_field_status(string $fieldKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->fieldStatus(...func_get_args());
}

function platform_api_secrets_group_public(string $groupKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->groupPublic(...func_get_args());
}

function platform_api_secrets_primary_token_field(string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->primaryTokenField(...func_get_args());
}

function platform_api_secrets_token_source_for_platform(string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->tokenSourceForPlatform(...func_get_args());
}

function platform_api_secrets_source_for_field(string $fieldKey)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->sourceForField(...func_get_args());
}

function platform_api_secrets_platform_token_configured(string $platform)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->platformTokenConfigured(...func_get_args());
}

function platform_api_secrets_apply_group_update(string $groupKey, array $payload)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\PlatformApiSecretsService::class)->applyGroupUpdate(...func_get_args());
}

function analytics_service_sync_from_platform(int $socialAccountId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AnalyticsService::class)->syncFromPlatform(...func_get_args());
}

function analytics_service_calculate_engagement_rate(int $likes, int $comments, int $shares, int $followers)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AnalyticsService::class)->calculateEngagementRate(...func_get_args());
}

function analytics_service_generate_demo_data(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AnalyticsService::class)->generateDemoData(...func_get_args());
}

function analytics_service_aggregate_totals(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\AnalyticsService::class)->aggregateTotals(...func_get_args());
}

function social_api_service_mock_enabled()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->mockEnabled(...func_get_args());
}

function social_api_service_mock_publish_result()
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->mockPublishResult(...func_get_args());
}

function social_api_service_http_request(
    string $method,
    string $url,
    array $headers = [],
    ?array $body = null
)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->httpRequest(...func_get_args());
}

function social_api_service_bearer(array $account)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->bearer(...func_get_args());
}

function social_api_service_env_token(string $key)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->envToken(...func_get_args());
}

function social_api_service_token_or_fallback(array $account, string $fallbackEnv)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->tokenOrFallback(...func_get_args());
}

function social_api_service_publish(array $account, array $post)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->publish(...func_get_args());
}

function social_api_service_publish_to_instagram(array $account, array $post)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->publishToInstagram(...func_get_args());
}

function social_api_service_publish_to_tiktok(array $account, array $post)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->publishToTiktok(...func_get_args());
}

function social_api_service_publish_to_youtube(array $account, array $post)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->publishToYoutube(...func_get_args());
}

function social_api_service_publish_to_facebook(array $account, array $post)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->publishToFacebook(...func_get_args());
}

function social_api_service_publish_to_twitter(array $account, array $post)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->publishToTwitter(...func_get_args());
}

function social_api_service_get_analytics(array $account, string $date)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->getAnalytics(...func_get_args());
}

function social_api_service_refresh_token(array $account)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->refreshToken(...func_get_args());
}

function social_api_service_revoke_access(array $account)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\SocialApiService::class)->revokeAccess(...func_get_args());
}

function notification_service_prefs(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->prefs(...func_get_args());
}

function notification_service_user_email(int $userId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->userEmail(...func_get_args());
}

function notification_service_maybe_email(int $userId, string $prefKey, string $subject, string $html)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->maybeEmail(...func_get_args());
}

function notification_service_allow_in_app(int $userId, string $type)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->allowInApp(...func_get_args());
}

function notification_service_create_in_app(
    int $userId,
    string $type,
    string $title,
    string $body = '',
    string $actionUrl = '',
    ?string $icon = null
)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->createInApp(...func_get_args());
}

function notification_service_post_published(int $userId, string $postTitle, int $postId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->postPublished(...func_get_args());
}

function notification_service_post_failed(int $userId, string $postTitle, string $reason)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->postFailed(...func_get_args());
}

function notification_service_deal_status_changed(int $userId, string $dealTitle, string $newStatus)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->dealStatusChanged(...func_get_args());
}

function notification_service_deal_completed(int $userId, string $dealTitle, int $dealId)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->dealCompleted(...func_get_args());
}

function notification_service_invoice_paid(int $userId, string $invoiceNumber, string $amount)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->invoicePaid(...func_get_args());
}

function notification_service_welcome_user(int $userId, string $name)
{
    return \CreatorzHive\Core\Application::instance()->get(CreatorzHive\Services\NotificationService::class)->welcomeUser(...func_get_args());
}
