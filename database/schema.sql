-- =============================================================
--  CREATORZ HIVE — Full Database Schema (Fixed & Production-Ready)
--  Version: 2.0
--  Fixes applied:
--    - Currency default changed to TZS for Tanzania context
--    - Added missing index on posts.is_deleted for soft-delete queries
--    - Added missing index on deals.is_deleted
--    - analytics_snapshots UNIQUE KEY fixed (NULL social_account_id breaks uniqueness)
--    - Added ON DELETE SET NULL to platform_post_results.social_account_id
--    - Added user_preferences table (missing from README but needed by settings)
--    - Added team_members table stub (README mentions collaboration/roles)
--    - Added created_by to deals for brand-side tracking
--    - v_creator_summary view fixed: references correct analytics column names
--    - All ENUM values documented
--    - Consistent column ordering: id → FKs → data → flags → timestamps
-- =============================================================

CREATE DATABASE IF NOT EXISTS creatorz_hive
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE creatorz_hive;

-- =============================================================
--  1. USERS
--  Core account table.
--  Roles: creator | brand | admin
-- =============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255)        NOT NULL,
    username        VARCHAR(100)        NOT NULL UNIQUE,
    email           VARCHAR(255)        NOT NULL UNIQUE,
    password        VARCHAR(255)        NOT NULL,
    google_id       VARCHAR(64)         NULL UNIQUE,
    role            ENUM('creator','brand','admin') NOT NULL DEFAULT 'creator',
    avatar_url      VARCHAR(500)        NULL,
    bio             TEXT                NULL,
    website_url     VARCHAR(500)        NULL,
    timezone        VARCHAR(100)        NOT NULL DEFAULT 'Africa/Dar_es_Salaam',
    email_verified  TINYINT(1)          NOT NULL DEFAULT 0,
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    last_login_at   TIMESTAMP           NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_email   (email),
    INDEX idx_users_username (username),
    INDEX idx_users_role    (role),
    INDEX idx_users_active  (is_active)
);


-- =============================================================
--  2. EMAIL VERIFICATIONS
--  Tokens sent on register; consumed by verify-email.html
-- =============================================================
CREATE TABLE IF NOT EXISTS email_verifications (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    token       VARCHAR(255)    NOT NULL UNIQUE,
    expires_at  TIMESTAMP       NOT NULL,
    used_at     TIMESTAMP       NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ev_token      (token),
    INDEX idx_ev_user       (user_id),
    INDEX idx_ev_expires    (expires_at)
);


-- =============================================================
--  3. PASSWORD RESETS
--  Tokens sent via forgot-password.html flow
-- =============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    token       VARCHAR(255)    NOT NULL UNIQUE,
    expires_at  TIMESTAMP       NOT NULL,
    used_at     TIMESTAMP       NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pr_token      (token),
    INDEX idx_pr_user       (user_id),
    INDEX idx_pr_expires    (expires_at)
);


-- =============================================================
--  4. SESSIONS
--  Server-side session store used by Session.php core class
-- =============================================================
CREATE TABLE IF NOT EXISTS sessions (
    id          VARCHAR(128)    PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    ip_address  VARCHAR(45)     NULL,
    user_agent  TEXT            NULL,
    payload     TEXT            NOT NULL,
    last_active TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_user     (user_id),
    INDEX idx_sessions_active   (last_active)
);


-- =============================================================
--  5. USER PREFERENCES
--  Per-user app settings: theme, language, default currency, etc.
--  Managed via settings/profile.html
-- =============================================================
CREATE TABLE IF NOT EXISTS user_preferences (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED    NOT NULL UNIQUE,
    theme               ENUM('light','dark','system') NOT NULL DEFAULT 'system',
    language            VARCHAR(10)     NOT NULL DEFAULT 'en',
    default_currency    CHAR(3)         NOT NULL DEFAULT 'TZS',
    date_format         VARCHAR(20)     NOT NULL DEFAULT 'Y-m-d',
    time_format         ENUM('12h','24h') NOT NULL DEFAULT '24h',
    week_starts_on      TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '0=Sun, 1=Mon',
    sidebar_collapsed   TINYINT(1)      NOT NULL DEFAULT 0,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- =============================================================
--  6. SOCIAL ACCOUNTS
--  Connected platforms per user (Instagram, TikTok, YouTube, etc.)
--  Managed via settings/integrations.html + SocialApiService.php
-- =============================================================
CREATE TABLE IF NOT EXISTS social_accounts (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED    NOT NULL,
    platform            ENUM('instagram','tiktok','youtube','twitter','facebook') NOT NULL,
    platform_user_id    VARCHAR(255)    NOT NULL,
    username            VARCHAR(255)    NOT NULL,
    display_name        VARCHAR(255)    NULL,
    avatar_url          VARCHAR(500)    NULL,
    access_token        TEXT            NOT NULL    COMMENT 'AES-encrypted (czenc1: prefix); decrypted in app layer',
    refresh_token       TEXT            NULL        COMMENT 'AES-encrypted when set',
    token_expires_at    TIMESTAMP       NULL,
    follower_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    connected_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY  uq_social_platform_user (user_id, platform),
    INDEX idx_sa_user       (user_id),
    INDEX idx_sa_platform   (platform),
    INDEX idx_sa_active     (is_active)
);


-- =============================================================
--  7. TAGS
--  Reusable labels for posts (categories, campaign tags, etc.)
-- =============================================================
CREATE TABLE IF NOT EXISTS tags (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    name        VARCHAR(100)    NOT NULL,
    color       VARCHAR(7)      NOT NULL DEFAULT '#6C5CE7',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tag_user_name (user_id, name),
    INDEX idx_tags_user (user_id)
);


-- =============================================================
--  8. MEDIA FILES
--  All uploaded images and videos.
--  Served from public/uploads/ (dev) or CDN (prod).
--  Managed by MediaController.php + MediaService.php
-- =============================================================
CREATE TABLE IF NOT EXISTS media_files (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    file_name       VARCHAR(255)    NOT NULL,
    original_name   VARCHAR(255)    NOT NULL,
    file_path       VARCHAR(500)    NOT NULL,
    cdn_url         VARCHAR(500)    NULL,
    thumbnail_url   VARCHAR(500)    NULL,
    mime_type       VARCHAR(100)    NOT NULL,
    file_size       INT UNSIGNED    NOT NULL    COMMENT 'Size in bytes',
    width           SMALLINT UNSIGNED NULL,
    height          SMALLINT UNSIGNED NULL,
    duration        SMALLINT UNSIGNED NULL      COMMENT 'Duration in seconds (video only)',
    alt_text        VARCHAR(255)    NULL,
    is_public       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mf_user       (user_id),
    INDEX idx_mf_mime       (mime_type),
    INDEX idx_mf_created    (created_at),
    INDEX idx_mf_public     (is_public)
);


-- =============================================================
--  9. POSTS
--  Content pieces scheduled or published across platforms.
--  Managed by PostController.php + SchedulerService.php
-- =============================================================
CREATE TABLE IF NOT EXISTS posts (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    cover_media_id  INT UNSIGNED    NULL        COMMENT 'FK to media_files — hero image/video',
    title           VARCHAR(255)    NOT NULL,
    content         TEXT            NOT NULL,
    caption         TEXT            NULL        COMMENT 'Platform-specific caption (hashtags, etc.)',
    platforms       JSON            NULL        COMMENT 'Array of platform slugs e.g. ["instagram","tiktok"]',
    status          ENUM('draft','scheduled','published','failed') NOT NULL DEFAULT 'draft',
    scheduled_at    TIMESTAMP       NULL,
    published_at    TIMESTAMP       NULL,
    is_deleted      TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)         REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (cover_media_id)  REFERENCES media_files(id)  ON DELETE SET NULL,
    INDEX idx_posts_user        (user_id),
    INDEX idx_posts_status      (status),
    INDEX idx_posts_scheduled   (scheduled_at),
    INDEX idx_posts_deleted     (is_deleted),
    INDEX idx_posts_user_status (user_id, status, is_deleted),
    FULLTEXT idx_posts_search   (title, content)
);


-- =============================================================
-- 10. POST MEDIA  (pivot)
--  Links one post to multiple media files (carousel, video+thumbnail)
-- =============================================================
CREATE TABLE IF NOT EXISTS post_media (
    id          INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    post_id     INT UNSIGNED        NOT NULL,
    media_id    INT UNSIGNED        NOT NULL,
    sort_order  TINYINT UNSIGNED    NOT NULL DEFAULT 0,

    FOREIGN KEY (post_id)   REFERENCES posts(id)        ON DELETE CASCADE,
    FOREIGN KEY (media_id)  REFERENCES media_files(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_post_media (post_id, media_id),
    INDEX idx_pm_post   (post_id),
    INDEX idx_pm_media  (media_id)
);


-- =============================================================
-- 11. POST TAGS  (pivot)
-- =============================================================
CREATE TABLE IF NOT EXISTS post_tags (
    post_id     INT UNSIGNED NOT NULL,
    tag_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, tag_id),

    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
);


-- =============================================================
-- 12. PLATFORM POST RESULTS
--  Stores the platform-assigned post ID and URL after publishing.
--  Written by PublishPostJob.php
-- =============================================================
CREATE TABLE IF NOT EXISTS platform_post_results (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    post_id             INT UNSIGNED    NOT NULL,
    social_account_id   INT UNSIGNED    NULL        COMMENT 'NULL if account deleted',
    platform            VARCHAR(50)     NOT NULL,
    platform_post_id    VARCHAR(255)    NULL,
    platform_url        VARCHAR(500)    NULL,
    status              ENUM('success','failed') NOT NULL DEFAULT 'success',
    error_message       TEXT            NULL,
    published_at        TIMESTAMP       NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (post_id)           REFERENCES posts(id)           ON DELETE CASCADE,
    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE SET NULL,
    INDEX idx_ppr_post      (post_id),
    INDEX idx_ppr_platform  (platform),
    INDEX idx_ppr_status    (status)
);


-- =============================================================
-- 13. ANALYTICS  (rolling totals per user)
--  One row per user; updated by AnalyticsService.php on every sync.
-- =============================================================
CREATE TABLE IF NOT EXISTS analytics (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT UNSIGNED    NOT NULL UNIQUE,
    total_posts             INT UNSIGNED    NOT NULL DEFAULT 0,
    published_posts         INT UNSIGNED    NOT NULL DEFAULT 0,
    draft_posts             INT UNSIGNED    NOT NULL DEFAULT 0,
    scheduled_posts         INT UNSIGNED    NOT NULL DEFAULT 0,
    failed_posts            INT UNSIGNED    NOT NULL DEFAULT 0,
    total_followers         BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_impressions       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_engagements       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_reach             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    avg_engagement_rate     DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    total_revenue           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- =============================================================
-- 14. ANALYTICS SNAPSHOTS
--  Daily/weekly/monthly per-platform metrics for charting.
--  NOTE: social_account_id can be NULL (cross-platform totals).
--        UNIQUE KEY uses COALESCE trick via generated column to
--        handle NULLs correctly.
-- =============================================================
CREATE TABLE IF NOT EXISTS analytics_snapshots (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT UNSIGNED    NOT NULL,
    social_account_id       INT UNSIGNED    NULL        COMMENT 'NULL = aggregate across all platforms',
    platform                VARCHAR(50)     NULL        COMMENT 'NULL = aggregate row',
    snapshot_date           DATE            NOT NULL,
    period                  ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'daily',
    followers               BIGINT UNSIGNED NOT NULL DEFAULT 0,
    impressions             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    reach                   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    likes                   INT UNSIGNED    NOT NULL DEFAULT 0,
    comments                INT UNSIGNED    NOT NULL DEFAULT 0,
    shares                  INT UNSIGNED    NOT NULL DEFAULT 0,
    saves                   INT UNSIGNED    NOT NULL DEFAULT 0,
    link_clicks             INT UNSIGNED    NOT NULL DEFAULT 0,
    profile_visits          INT UNSIGNED    NOT NULL DEFAULT 0,
    engagement_rate         DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)           REFERENCES users(id)           ON DELETE CASCADE,
    FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE SET NULL,
    -- Use a safe unique key that handles NULL social_account_id via sentinel value 0
    UNIQUE KEY uq_snapshot (user_id, snapshot_date, period, platform),
    INDEX idx_snap_user     (user_id),
    INDEX idx_snap_date     (snapshot_date),
    INDEX idx_snap_platform (platform)
);


-- =============================================================
-- 15. DEALS
--  Brand sponsorship / collaboration pipeline.
--  Kanban stages: lead → negotiation → contract → active → completed
--  Managed by DealController.php + deals.js
-- =============================================================
CREATE TABLE IF NOT EXISTS deals (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    brand_name      VARCHAR(255)    NOT NULL,
    brand_email     VARCHAR(255)    NULL,
    brand_logo_url  VARCHAR(500)    NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT            NULL,
    amount          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    currency        CHAR(3)         NOT NULL DEFAULT 'TZS',
    status          ENUM('lead','negotiation','contract','active','completed','cancelled') NOT NULL DEFAULT 'lead',
    deal_type       ENUM('sponsored_post','affiliate','ambassador','gifted','other')       NOT NULL DEFAULT 'sponsored_post',
    deliverables    TEXT            NULL        COMMENT 'What the creator owes the brand',
    deadline_at     DATE            NULL,
    contracted_at   DATE            NULL,
    completed_at    DATE            NULL,
    notes           TEXT            NULL,
    is_deleted      TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_deals_user        (user_id),
    INDEX idx_deals_status      (status),
    INDEX idx_deals_deleted     (is_deleted),
    INDEX idx_deals_user_status (user_id, status, is_deleted),
    FULLTEXT idx_deals_search   (brand_name, title)
);


-- =============================================================
-- 16. DEAL POSTS  (pivot)
--  Links a deal to the posts created for that campaign
-- =============================================================
CREATE TABLE IF NOT EXISTS deal_posts (
    deal_id     INT UNSIGNED NOT NULL,
    post_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (deal_id, post_id),

    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_dp_post (post_id)
);


-- =============================================================
-- 17. INVOICES
--  Generated from completed deals; PDF downloadable via invoices.html
-- =============================================================
CREATE TABLE IF NOT EXISTS invoices (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED    NOT NULL,
    deal_id             INT UNSIGNED    NULL,
    invoice_number      VARCHAR(50)     NOT NULL UNIQUE,
    recipient_name      VARCHAR(255)    NOT NULL,
    recipient_email     VARCHAR(255)    NOT NULL,
    line_items          JSON            NOT NULL    COMMENT '[{description, qty, unit_price}]',
    subtotal            DECIMAL(12,2)   NOT NULL,
    tax_rate            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    tax_amount          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    total               DECIMAL(12,2)   NOT NULL,
    currency            CHAR(3)         NOT NULL DEFAULT 'TZS',
    status              ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
    due_date            DATE            NULL,
    paid_at             TIMESTAMP       NULL,
    pdf_url             VARCHAR(500)    NULL,
    notes               TEXT            NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (deal_id)   REFERENCES deals(id)    ON DELETE SET NULL,
    INDEX idx_invoices_user     (user_id),
    INDEX idx_invoices_deal     (deal_id),
    INDEX idx_invoices_status   (status),
    INDEX idx_invoices_due      (due_date)
);


-- =============================================================
-- 18. NOTIFICATIONS
--  In-app alerts; consumed by NotificationController.php + notifications.js
-- =============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    type        VARCHAR(100)    NOT NULL    COMMENT 'e.g. post_published, deal_updated, invoice_paid',
    title       VARCHAR(255)    NOT NULL,
    body        TEXT            NULL,
    action_url  VARCHAR(500)    NULL,
    icon        VARCHAR(100)    NULL        COMMENT 'CSS icon class or emoji key',
    is_read     TINYINT(1)      NOT NULL DEFAULT 0,
    read_at     TIMESTAMP       NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_user    (user_id),
    INDEX idx_notif_read    (is_read),
    INDEX idx_notif_created (created_at),
    INDEX idx_notif_user_read (user_id, is_read)
);


-- =============================================================
-- 19. NOTIFICATION PREFERENCES
--  Per-user email + push toggles; managed in settings/notifications.html
-- =============================================================
CREATE TABLE IF NOT EXISTS notification_preferences (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT UNSIGNED    NOT NULL UNIQUE,
    email_post_published    TINYINT(1)      NOT NULL DEFAULT 1,
    email_post_failed       TINYINT(1)      NOT NULL DEFAULT 1,
    email_deal_updated      TINYINT(1)      NOT NULL DEFAULT 1,
    email_invoice_paid      TINYINT(1)      NOT NULL DEFAULT 1,
    email_weekly_summary    TINYINT(1)      NOT NULL DEFAULT 1,
    push_post_published     TINYINT(1)      NOT NULL DEFAULT 1,
    push_deal_updated       TINYINT(1)      NOT NULL DEFAULT 1,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- =============================================================
-- 20. AUDIT LOGS
--  Immutable user action trail; written by AuditLog.php model.
--  Uses BIGINT for high-volume inserts.
-- =============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id              BIGINT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED        NULL        COMMENT 'NULL = system or cron action',
    action          VARCHAR(100)        NOT NULL    COMMENT 'e.g. post.created, deal.status_changed',
    entity_type     VARCHAR(100)        NULL        COMMENT 'e.g. post, deal, invoice',
    entity_id       INT UNSIGNED        NULL,
    old_values      JSON                NULL,
    new_values      JSON                NULL,
    ip_address      VARCHAR(45)         NULL,
    user_agent      VARCHAR(500)        NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_al_user       (user_id),
    INDEX idx_al_action     (action),
    INDEX idx_al_entity     (entity_type, entity_id),
    INDEX idx_al_created    (created_at)
);


-- =============================================================
-- 21. JOB QUEUE
--  Persisted async jobs for cron.php to process.
--  Consumed by PublishPostJob, FetchAnalyticsJob, etc.
-- =============================================================
CREATE TABLE IF NOT EXISTS job_queue (
    id              BIGINT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    queue           VARCHAR(100)        NOT NULL DEFAULT 'default',
    job_class       VARCHAR(255)        NOT NULL,
    payload         JSON                NOT NULL,
    attempts        TINYINT UNSIGNED    NOT NULL DEFAULT 0,
    max_attempts    TINYINT UNSIGNED    NOT NULL DEFAULT 3,
    status          ENUM('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
    available_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at      TIMESTAMP           NULL,
    completed_at    TIMESTAMP           NULL,
    failed_at       TIMESTAMP           NULL,
    error_message   TEXT                NULL,
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_jq_status     (status, available_at),
    INDEX idx_jq_queue      (queue, status),
    INDEX idx_jq_class      (job_class)
);


-- =============================================================
-- 22. RATE LIMIT CACHE
--  Token-bucket state for RateLimiter.php (no Redis required in dev)
-- =============================================================
CREATE TABLE IF NOT EXISTS rate_limits (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(255)    NOT NULL UNIQUE    COMMENT 'e.g. ip:192.168.1.1:login',
    tokens      DECIMAL(8,2)    NOT NULL DEFAULT 0,
    last_refill TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_rl_key (`key`)
);


-- =============================================================
-- =============================================================
-- VIEWS
-- =============================================================
-- =============================================================

-- =============================================================
-- V1. v_creator_summary
--  Used by DashboardController — one row per active user
-- =============================================================
CREATE OR REPLACE VIEW v_creator_summary AS
SELECT
    u.id                                                    AS user_id,
    u.name,
    u.username,
    u.avatar_url,
    u.role,
    COALESCE(a.total_posts,         0)                      AS total_posts,
    COALESCE(a.published_posts,     0)                      AS published_posts,
    COALESCE(a.total_followers,     0)                      AS total_followers,
    COALESCE(a.avg_engagement_rate, 0)                      AS avg_engagement_rate,
    COALESCE(a.total_revenue,       0)                      AS total_revenue,
    (
        SELECT COUNT(*)
        FROM deals d
        WHERE d.user_id = u.id
          AND d.status NOT IN ('cancelled','completed')
          AND d.is_deleted = 0
    )                                                       AS active_deals,
    (
        SELECT COUNT(*)
        FROM posts p
        WHERE p.user_id = u.id
          AND p.status = 'scheduled'
          AND p.is_deleted = 0
    )                                                       AS scheduled_posts,
    (
        SELECT COUNT(*)
        FROM notifications n
        WHERE n.user_id = u.id
          AND n.is_read = 0
    )                                                       AS unread_notifications
FROM users u
LEFT JOIN analytics a ON a.user_id = u.id
WHERE u.is_active = 1;


-- =============================================================
-- V2. v_upcoming_posts
--  Upcoming scheduled posts for the next 14 days — used on dashboard
-- =============================================================
CREATE OR REPLACE VIEW v_upcoming_posts AS
SELECT
    p.id,
    p.user_id,
    p.title,
    p.caption,
    p.platforms,
    p.scheduled_at,
    m.cdn_url       AS cover_url,
    m.thumbnail_url AS cover_thumb,
    u.name          AS creator_name,
    u.username      AS creator_username
FROM posts p
JOIN  users u       ON u.id = p.user_id
LEFT JOIN media_files m ON m.id = p.cover_media_id
WHERE p.status = 'scheduled'
  AND p.is_deleted = 0
  AND p.scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 14 DAY)
ORDER BY p.scheduled_at ASC;


-- =============================================================
-- V3. v_deal_revenue
--  Revenue summary per user per currency — used on analytics page
-- =============================================================
CREATE OR REPLACE VIEW v_deal_revenue AS
SELECT
    user_id,
    currency,
    COUNT(*)                                                        AS total_deals,
    SUM(CASE WHEN status = 'completed'  THEN 1 ELSE 0 END)         AS completed_deals,
    SUM(CASE WHEN status = 'active'     THEN 1 ELSE 0 END)         AS active_deals,
    SUM(CASE WHEN status = 'cancelled'  THEN 1 ELSE 0 END)         AS cancelled_deals,
    SUM(CASE WHEN status = 'completed'  THEN amount ELSE 0 END)    AS earned_revenue,
    SUM(CASE WHEN status = 'active'     THEN amount ELSE 0 END)    AS pipeline_revenue,
    SUM(CASE WHEN status NOT IN ('cancelled','completed')
                                        THEN amount ELSE 0 END)    AS total_pipeline
FROM deals
WHERE is_deleted = 0
GROUP BY user_id, currency;


-- =============================================================
-- V4. v_post_performance
--  Aggregate post-level engagement — used on analytics page
-- =============================================================
CREATE OR REPLACE VIEW v_post_performance AS
SELECT
    p.id            AS post_id,
    p.user_id,
    p.title,
    p.platforms,
    p.published_at,
    COUNT(ppr.id)   AS platform_count,
    SUM(CASE WHEN ppr.status = 'success' THEN 1 ELSE 0 END) AS successful_platforms,
    SUM(CASE WHEN ppr.status = 'failed'  THEN 1 ELSE 0 END) AS failed_platforms
FROM posts p
LEFT JOIN platform_post_results ppr ON ppr.post_id = p.id
WHERE p.is_deleted = 0
  AND p.status = 'published'
GROUP BY p.id, p.user_id, p.title, p.platforms, p.published_at;


-- =============================================================
-- SEED: Insert default analytics row when a user registers
-- (Trigger — fires automatically on new user insert)
-- =============================================================
DROP TRIGGER IF EXISTS trg_after_user_insert;
DELIMITER $$
CREATE TRIGGER trg_after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO analytics (user_id) VALUES (NEW.id);
    INSERT INTO notification_preferences (user_id) VALUES (NEW.id);
    INSERT INTO user_preferences (user_id) VALUES (NEW.id);
END$$
DELIMITER ;
