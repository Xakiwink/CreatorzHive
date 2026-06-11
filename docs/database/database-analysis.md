# CreatorzHive — Database Analysis

> **Version:** 1.0 | **Date:** 2026-06-10 | **Engine:** MySQL 8.0 | **Charset:** utf8mb4_unicode_ci

---

## Overview

The database `creatorz_hive` contains **22 tables**, **4 views**, and **1 trigger**. All tables use `InnoDB` with proper foreign key constraints. The schema uses soft deletes on `posts` and `deals` (`is_deleted` flag). All monetary columns default to `TZS` (Tanzanian Shilling).

---

## Tables

### 1. `users`
Core account table for all system users.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | Auto-increment |
| `name` | VARCHAR(255) | Display name |
| `username` | VARCHAR(100) UNIQUE | Login handle |
| `email` | VARCHAR(255) UNIQUE | Login email |
| `password` | VARCHAR(255) | bcrypt hash (cost 12) |
| `google_id` | VARCHAR(64) UNIQUE NULL | Set when Google OAuth used |
| `role` | ENUM('creator','brand','admin') | Default: creator |
| `avatar_url` | VARCHAR(500) NULL | Profile picture URL |
| `bio` | TEXT NULL | Creator bio |
| `website_url` | VARCHAR(500) NULL | Creator website |
| `timezone` | VARCHAR(100) | Default: Africa/Dar_es_Salaam |
| `email_verified` | TINYINT(1) | 0 = unverified |
| `is_active` | TINYINT(1) | 0 = banned/disabled |
| `last_login_at` | TIMESTAMP NULL | Updated on login |
| `created_at` | TIMESTAMP | Auto |
| `updated_at` | TIMESTAMP | Auto-update |

**Indexes:** email, username, role, is_active

**Used by:** All features — every row in the system belongs to a user.

---

### 2. `email_verifications`
Tokens sent on registration; consumed by `?route=verify`.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `token` | VARCHAR(255) UNIQUE | 64-char hex token |
| `expires_at` | TIMESTAMP | 24 hours after creation |
| `used_at` | TIMESTAMP NULL | NULL = not yet used |
| `created_at` | TIMESTAMP | |

---

### 3. `password_resets`
Tokens (and OTP codes) for password reset flow.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `token` | VARCHAR(255) UNIQUE | Either 64-char token OR `{6-digit-OTP}:{24-char-suffix}` |
| `expires_at` | TIMESTAMP | 1 hour (token) or 10 min (OTP) |
| `used_at` | TIMESTAMP NULL | NULL = not yet consumed |
| `created_at` | TIMESTAMP | |

**Note:** OTP mode stores `123456:randomstring`; validated via `LIKE '{otp}:%'` query.

---

### 4. `sessions`
Server-side session store. Provides session persistence across servers.

| Column | Type | Notes |
|--------|------|-------|
| `id` | VARCHAR(128) PK | PHP session ID |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `ip_address` | VARCHAR(45) NULL | |
| `user_agent` | TEXT NULL | |
| `payload` | TEXT | Serialized session data |
| `last_active` | TIMESTAMP | Auto-update |
| `created_at` | TIMESTAMP | |

**Note:** Currently the app uses PHP's default file-based sessions; this table provides a DB-backed store for future horizontal scaling.

---

### 5. `user_preferences`
Per-user UI/app settings.

| Column | Type | Default | Notes |
|--------|------|---------|-------|
| `id` | INT UNSIGNED PK | | |
| `user_id` | INT UNSIGNED UNIQUE FK → users | | 1:1 with users |
| `theme` | ENUM('light','dark','system') | system | |
| `language` | VARCHAR(10) | en | |
| `default_currency` | CHAR(3) | TZS | |
| `date_format` | VARCHAR(20) | Y-m-d | PHP date format |
| `time_format` | ENUM('12h','24h') | 24h | |
| `week_starts_on` | TINYINT(1) | 1 | 0=Sun, 1=Mon |
| `sidebar_collapsed` | TINYINT(1) | 0 | |
| `updated_at` | TIMESTAMP | | Auto-update |

**Auto-created by trigger** `trg_after_user_insert`.

---

### 6. `social_accounts`
Connected platform accounts per user. Tokens stored AES-256-CBC encrypted.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `platform` | ENUM('instagram','tiktok','youtube','twitter','facebook') | |
| `platform_user_id` | VARCHAR(255) | ID assigned by the platform |
| `username` | VARCHAR(255) | Platform @handle |
| `display_name` | VARCHAR(255) NULL | Full name on platform |
| `avatar_url` | VARCHAR(500) NULL | |
| `access_token` | TEXT | AES-256-CBC encrypted (`czenc1:` prefix) |
| `refresh_token` | TEXT NULL | AES-256-CBC encrypted when set |
| `token_expires_at` | TIMESTAMP NULL | |
| `follower_count` | INT UNSIGNED | Synced by FetchAnalyticsJob |
| `is_active` | TINYINT(1) | |
| `connected_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Unique constraint:** `(user_id, platform)` — one account per platform per user.

---

### 7. `tags`
User-defined labels for posts. Color-coded.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `name` | VARCHAR(100) | |
| `color` | VARCHAR(7) | Hex color, default `#6C5CE7` |
| `created_at` | TIMESTAMP | |

**Unique constraint:** `(user_id, name)` — no duplicate tag names per user.

---

### 8. `media_files`
All uploaded images and videos.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `file_name` | VARCHAR(255) | Storage filename (MD5 hash) |
| `original_name` | VARCHAR(255) | Original upload filename |
| `file_path` | VARCHAR(500) | Relative path under `public/uploads/` |
| `cdn_url` | VARCHAR(500) NULL | CDN URL when available |
| `thumbnail_url` | VARCHAR(500) NULL | Auto-generated thumb |
| `mime_type` | VARCHAR(100) | image/jpeg, video/mp4, etc. |
| `file_size` | INT UNSIGNED | Bytes |
| `width` | SMALLINT UNSIGNED NULL | Pixels |
| `height` | SMALLINT UNSIGNED NULL | Pixels |
| `duration` | SMALLINT UNSIGNED NULL | Seconds (video) |
| `alt_text` | VARCHAR(255) NULL | Accessibility |
| `is_public` | TINYINT(1) | Default 1 |
| `created_at` | TIMESTAMP | |

---

### 9. `posts`
The central content piece. Scheduled or published across platforms.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `cover_media_id` | INT UNSIGNED FK → media_files NULL | SET NULL on delete |
| `title` | VARCHAR(255) | |
| `content` | TEXT | Body text |
| `caption` | TEXT NULL | Platform caption (hashtags, etc.) |
| `platforms` | JSON NULL | `["instagram","tiktok"]` |
| `status` | ENUM('draft','scheduled','published','failed') | Default: draft |
| `scheduled_at` | TIMESTAMP NULL | When to publish |
| `published_at` | TIMESTAMP NULL | When actually published |
| `is_deleted` | TINYINT(1) | Soft delete flag |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indexes:** user_id, status, scheduled_at, is_deleted, (user_id, status, is_deleted) compound, FULLTEXT(title, content)

---

### 10. `post_media` (pivot)
Links posts to multiple media files (carousel support).

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `post_id` | INT UNSIGNED FK → posts | CASCADE DELETE |
| `media_id` | INT UNSIGNED FK → media_files | CASCADE DELETE |
| `sort_order` | TINYINT UNSIGNED | 0-based ordering |

**Unique constraint:** `(post_id, media_id)`

---

### 11. `post_tags` (pivot)
Links posts to tags.

| Column | Type | Notes |
|--------|------|-------|
| `post_id` | INT UNSIGNED FK → posts | CASCADE DELETE |
| `tag_id` | INT UNSIGNED FK → tags | CASCADE DELETE |
| PRIMARY KEY | `(post_id, tag_id)` | |

---

### 12. `platform_post_results`
Per-platform publish outcome written by `PublishPostJob`.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `post_id` | INT UNSIGNED FK → posts | CASCADE DELETE |
| `social_account_id` | INT UNSIGNED FK → social_accounts NULL | SET NULL on account delete |
| `platform` | VARCHAR(50) | Platform slug |
| `platform_post_id` | VARCHAR(255) NULL | ID from platform API |
| `platform_url` | VARCHAR(500) NULL | Link to published post |
| `status` | ENUM('success','failed') | |
| `error_message` | TEXT NULL | Failure reason |
| `published_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP | |

---

### 13. `analytics`
Rolling aggregate totals per user. One row per user.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED UNIQUE FK → users | |
| `total_posts` | INT UNSIGNED | |
| `published_posts` | INT UNSIGNED | |
| `draft_posts` | INT UNSIGNED | |
| `scheduled_posts` | INT UNSIGNED | |
| `failed_posts` | INT UNSIGNED | |
| `total_followers` | BIGINT UNSIGNED | Sum across platforms |
| `total_impressions` | BIGINT UNSIGNED | |
| `total_engagements` | BIGINT UNSIGNED | |
| `total_reach` | BIGINT UNSIGNED | |
| `avg_engagement_rate` | DECIMAL(5,2) | |
| `total_revenue` | DECIMAL(12,2) | From completed deals |
| `updated_at` | TIMESTAMP | |

**Auto-created by trigger** `trg_after_user_insert`.

---

### 14. `analytics_snapshots`
Time-series metrics for charting. Daily/weekly/monthly per platform.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `social_account_id` | INT UNSIGNED FK → social_accounts NULL | NULL = aggregate row |
| `platform` | VARCHAR(50) NULL | NULL = cross-platform aggregate |
| `snapshot_date` | DATE | |
| `period` | ENUM('daily','weekly','monthly') | |
| `followers` | BIGINT UNSIGNED | |
| `impressions` | BIGINT UNSIGNED | |
| `reach` | BIGINT UNSIGNED | |
| `likes` | INT UNSIGNED | |
| `comments` | INT UNSIGNED | |
| `shares` | INT UNSIGNED | |
| `saves` | INT UNSIGNED | |
| `link_clicks` | INT UNSIGNED | |
| `profile_visits` | INT UNSIGNED | |
| `engagement_rate` | DECIMAL(5,2) | |
| `created_at` | TIMESTAMP | |

**Unique constraint:** `(user_id, snapshot_date, period, platform)` — handles NULLs via string comparison.

---

### 15. `deals`
Brand sponsorship pipeline (Kanban-style CRM).

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `brand_name` | VARCHAR(255) | Brand/company name |
| `brand_email` | VARCHAR(255) NULL | |
| `brand_logo_url` | VARCHAR(500) NULL | |
| `title` | VARCHAR(255) | Deal title |
| `description` | TEXT NULL | |
| `amount` | DECIMAL(12,2) | Deal value |
| `currency` | CHAR(3) | Default TZS |
| `status` | ENUM('lead','negotiation','contract','active','completed','cancelled') | Kanban stage |
| `deal_type` | ENUM('sponsored_post','affiliate','ambassador','gifted','other') | |
| `deliverables` | TEXT NULL | What creator owes brand |
| `deadline_at` | DATE NULL | |
| `contracted_at` | DATE NULL | |
| `completed_at` | DATE NULL | |
| `notes` | TEXT NULL | |
| `is_deleted` | TINYINT(1) | Soft delete |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indexes:** user_id, status, is_deleted, (user_id, status, is_deleted) compound, FULLTEXT(brand_name, title)

---

### 16. `deal_posts` (pivot)
Links deals to their associated content posts.

| Column | Type | Notes |
|--------|------|-------|
| `deal_id` | INT UNSIGNED FK → deals | CASCADE DELETE |
| `post_id` | INT UNSIGNED FK → posts | CASCADE DELETE |
| PRIMARY KEY | `(deal_id, post_id)` | |

---

### 17. `invoices`
Financial documents generated from completed deals.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `deal_id` | INT UNSIGNED FK → deals NULL | SET NULL on deal delete |
| `invoice_number` | VARCHAR(50) UNIQUE | e.g., INV-2026-001 |
| `recipient_name` | VARCHAR(255) | Brand/client name |
| `recipient_email` | VARCHAR(255) | |
| `line_items` | JSON | `[{description, qty, unit_price}]` |
| `subtotal` | DECIMAL(12,2) | |
| `tax_rate` | DECIMAL(5,2) | Default 0 |
| `tax_amount` | DECIMAL(12,2) | |
| `total` | DECIMAL(12,2) | |
| `currency` | CHAR(3) | Default TZS |
| `status` | ENUM('draft','sent','paid','overdue','cancelled') | |
| `due_date` | DATE NULL | |
| `paid_at` | TIMESTAMP NULL | |
| `pdf_url` | VARCHAR(500) NULL | Future: generated PDF |
| `notes` | TEXT NULL | |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

---

### 18. `notifications`
In-app alert messages per user.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | INT UNSIGNED FK → users | CASCADE DELETE |
| `type` | VARCHAR(100) | e.g., `post_published`, `deal_updated` |
| `title` | VARCHAR(255) | Notification heading |
| `body` | TEXT NULL | Notification body |
| `action_url` | VARCHAR(500) NULL | Link to relevant resource |
| `icon` | VARCHAR(100) NULL | CSS icon class or emoji key |
| `is_read` | TINYINT(1) | 0 = unread |
| `read_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP | |

**Compound index:** `(user_id, is_read)` for unread count queries.

---

### 19. `notification_preferences`
Per-user toggles for email and push notification types.

| Column | Type | Default |
|--------|------|---------|
| `user_id` | INT UNSIGNED UNIQUE FK → users | — |
| `email_post_published` | TINYINT(1) | 1 |
| `email_post_failed` | TINYINT(1) | 1 |
| `email_deal_updated` | TINYINT(1) | 1 |
| `email_invoice_paid` | TINYINT(1) | 1 |
| `email_weekly_summary` | TINYINT(1) | 1 |
| `push_post_published` | TINYINT(1) | 1 |
| `push_deal_updated` | TINYINT(1) | 1 |

**Auto-created by trigger** `trg_after_user_insert`.

---

### 20. `audit_logs`
Immutable action trail for compliance and debugging.

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | High-volume BIGINT |
| `user_id` | INT UNSIGNED FK → users NULL | NULL = system/cron |
| `action` | VARCHAR(100) | e.g., `post.created`, `deal.status_changed` |
| `entity_type` | VARCHAR(100) NULL | e.g., `post`, `deal` |
| `entity_id` | INT UNSIGNED NULL | |
| `old_values` | JSON NULL | Before state |
| `new_values` | JSON NULL | After state |
| `ip_address` | VARCHAR(45) NULL | |
| `user_agent` | VARCHAR(500) NULL | |
| `created_at` | TIMESTAMP | |

---

### 21. `job_queue`
Persisted async job queue consumed by `cron.php`.

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `queue` | VARCHAR(100) | 'default', 'analytics', 'cleanup' |
| `job_class` | VARCHAR(255) | e.g., `publish_post`, `fetch_analytics` |
| `payload` | JSON | Job parameters |
| `attempts` | TINYINT UNSIGNED | Current attempt count |
| `max_attempts` | TINYINT UNSIGNED | Default 3 |
| `status` | ENUM('pending','running','completed','failed') | |
| `available_at` | TIMESTAMP | Delay support |
| `started_at` | TIMESTAMP NULL | |
| `completed_at` | TIMESTAMP NULL | |
| `failed_at` | TIMESTAMP NULL | |
| `error_message` | TEXT NULL | |
| `created_at` | TIMESTAMP | |

**Compound index:** `(status, available_at)` — primary polling index.

---

### 22. `rate_limits`
Token-bucket state for login rate limiting (no Redis required).

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `key` | VARCHAR(255) UNIQUE | e.g., `ip:192.168.1.1:login` |
| `tokens` | DECIMAL(8,2) | Current token count |
| `last_refill` | TIMESTAMP | Auto-update |

---

## Views

### `v_creator_summary`
One row per active user. Used by DashboardController.

Columns: `user_id`, `name`, `username`, `avatar_url`, `role`, `total_posts`, `published_posts`, `total_followers`, `avg_engagement_rate`, `total_revenue`, `active_deals`, `scheduled_posts`, `unread_notifications`

JOINs: `users LEFT JOIN analytics`. Subqueries for active_deals, scheduled_posts, unread_notifications.

---

### `v_upcoming_posts`
Scheduled posts in the next 14 days. Used on dashboard.

Columns: `id`, `user_id`, `title`, `caption`, `platforms`, `scheduled_at`, `cover_url`, `cover_thumb`, `creator_name`, `creator_username`

---

### `v_deal_revenue`
Revenue summary per user per currency. Used on analytics page.

Columns: `user_id`, `currency`, `total_deals`, `completed_deals`, `active_deals`, `cancelled_deals`, `earned_revenue`, `pipeline_revenue`, `total_pipeline`

---

### `v_post_performance`
Aggregate per-post engagement from platform_post_results. Used on analytics page.

Columns: `post_id`, `user_id`, `title`, `platforms`, `published_at`, `platform_count`, `successful_platforms`, `failed_platforms`

---

## Triggers

### `trg_after_user_insert`
Fires after every INSERT on `users`. Auto-creates:
- `analytics` row (zero-filled)
- `notification_preferences` row (all defaults)
- `user_preferences` row (all defaults)

This ensures every user has companion rows without requiring application-level logic.

---

## Foreign Key Relationships

```
users (1) ─────────────────────────────── email_verifications (many)
users (1) ─────────────────────────────── password_resets (many)
users (1) ─────────────────────────────── sessions (many)
users (1:1) ───────────────────────────── user_preferences
users (1:1) ───────────────────────────── analytics
users (1:1) ───────────────────────────── notification_preferences
users (1) ─────────────────────────────── social_accounts (many) [max 5 - one per platform]
users (1) ─────────────────────────────── tags (many)
users (1) ─────────────────────────────── media_files (many)
users (1) ─────────────────────────────── posts (many)
users (1) ─────────────────────────────── deals (many)
users (1) ─────────────────────────────── invoices (many)
users (1) ─────────────────────────────── notifications (many)
users (1) ─────────────────────────────── audit_logs (many) [nullable]
users (1) ─────────────────────────────── analytics_snapshots (many)

posts (1) ─────────────────────────────── post_media (many) [pivot]
posts (1) ─────────────────────────────── post_tags (many) [pivot]
posts (1) ─────────────────────────────── platform_post_results (many)
posts (1) ─────────────────────────────── deal_posts (many) [pivot]

media_files (many) ────────────────────── post_media (pivot)
media_files (1) SET NULL ──────────────── posts.cover_media_id

tags (many) ───────────────────────────── post_tags (pivot)

deals (1) ─────────────────────────────── deal_posts (many) [pivot]
deals (1) SET NULL ────────────────────── invoices.deal_id

social_accounts (1) SET NULL ──────────── platform_post_results.social_account_id
social_accounts (1) SET NULL ──────────── analytics_snapshots.social_account_id
```

---

## Missing Indexes / Optimization Opportunities

| Issue | Table | Recommendation |
|-------|-------|----------------|
| No index on `posts.published_at` | posts | Add for analytics queries sorting by publish date |
| No index on `invoices.paid_at` | invoices | Add for revenue reporting |
| No index on `audit_logs.entity_type + entity_id` | audit_logs | Compound index already exists; verify usage |
| `analytics_snapshots.platform` is VARCHAR(50) | analytics_snapshots | Consider ENUM to match social_accounts.platform |
| `job_queue` grows without bound | job_queue | cron.php purges completed > 30 days; add partition or archive strategy |
| `notifications` can grow large | notifications | Add archival/purge strategy after N days |
| `rate_limits` stale rows | rate_limits | Add scheduled cleanup of rows with `last_refill` > 24h |

---

## Optimization Opportunities

1. **Connection pooling**: Consider PgBouncer or MySQL ProxySQL for high concurrency.
2. **Read replicas**: `analytics_snapshots` and `audit_logs` are read-heavy; route to replica.
3. **JSON column indexing**: `posts.platforms` and `invoices.line_items` use JSON; add generated columns + indexes if filtering by platform becomes a bottleneck.
4. **Full-text search**: Already in place for `posts(title, content)` and `deals(brand_name, title)`.
5. **Partitioning**: `analytics_snapshots` and `audit_logs` are candidates for date-range partitioning as they grow.
