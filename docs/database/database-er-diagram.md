# CreatorzHive — Entity Relationship Diagram

> **Format:** Text-based ER diagram using crow's foot notation

---

## Complete Entity Relationships

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          CREATORZ HIVE DATABASE ER                      │
└─────────────────────────────────────────────────────────────────────────┘

╔══════════════════╗
║      USERS       ║
╠══════════════════╣
║ PK id            ║
║    name          ║
║    username      ║──unique
║    email         ║──unique
║    password      ║──bcrypt
║    google_id     ║──unique,null
║    role          ║──enum[creator,brand,admin]
║    avatar_url    ║
║    bio           ║
║    website_url   ║
║    timezone      ║──default:Africa/Dar_es_Salaam
║    email_verified║
║    is_active     ║
║    last_login_at ║
║    created_at    ║
║    updated_at    ║
╚══════════════════╝
         │
         │ 1:1 (auto-created by trigger)
         ├──────────────────────────────────────────────────────┐
         │                                                      │
         ▼                                                      ▼
╔═══════════════════╗                               ╔═══════════════════════╗
║  USER_PREFERENCES ║                               ║ NOTIFICATION_PREFS    ║
╠═══════════════════╣                               ╠═══════════════════════╣
║ PK id             ║                               ║ PK id                 ║
║ FK user_id        ║                               ║ FK user_id            ║
║    theme          ║                               ║    email_post_published║
║    language       ║                               ║    email_post_failed  ║
║    default_curr.  ║                               ║    email_deal_updated ║
║    date_format    ║                               ║    email_invoice_paid ║
║    time_format    ║                               ║    email_weekly_sum.  ║
║    week_starts_on ║                               ║    push_post_published║
║    sidebar_coll.  ║                               ║    push_deal_updated  ║
╚═══════════════════╝                               ╚═══════════════════════╝

         │ 1:1 (auto-created by trigger)
         ▼
╔══════════════════╗
║    ANALYTICS     ║
╠══════════════════╣
║ PK id            ║
║ FK user_id       ║──unique
║    total_posts   ║
║    published_    ║
║    draft_posts   ║
║    scheduled_    ║
║    failed_posts  ║
║    total_follow. ║
║    total_impress.║
║    total_engage. ║
║    total_reach   ║
║    avg_engage_rt ║
║    total_revenue ║
║    updated_at    ║
╚══════════════════╝

         │ 1:many
         ▼
╔═══════════════════════╗
║  ANALYTICS_SNAPSHOTS  ║
╠═══════════════════════╣
║ PK id                 ║
║ FK user_id            ║
║ FK social_account_id  ║──null (aggregate row)
║    platform           ║──null (aggregate row)
║    snapshot_date      ║
║    period             ║──enum[daily,weekly,monthly]
║    followers          ║
║    impressions        ║
║    reach              ║
║    likes              ║
║    comments           ║
║    shares             ║
║    saves              ║
║    link_clicks        ║
║    profile_visits     ║
║    engagement_rate    ║
╚═══════════════════════╝

USERS (1) ─────────── SOCIAL_ACCOUNTS (many)
                ╔═══════════════════╗
                ║  SOCIAL_ACCOUNTS  ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║ FK user_id        ║
                ║    platform       ║──enum[instagram,tiktok,youtube,twitter,facebook]
                ║    platform_user_ ║
                ║    username       ║
                ║    display_name   ║
                ║    avatar_url     ║
                ║    access_token   ║──AES-256-CBC encrypted
                ║    refresh_token  ║──AES-256-CBC encrypted,null
                ║    token_expires_ ║
                ║    follower_count ║
                ║    is_active      ║
                ║    connected_at   ║
                ╚═══════════════════╝
                         │
                         │ 1:many (SET NULL on delete)
                         ▼
                ╔══════════════════════════╗
                ║  PLATFORM_POST_RESULTS   ║
                ╠══════════════════════════╣
                ║ PK id                    ║
                ║ FK post_id               ║
                ║ FK social_account_id     ║──null
                ║    platform              ║
                ║    platform_post_id      ║
                ║    platform_url          ║
                ║    status                ║──enum[success,failed]
                ║    error_message         ║
                ║    published_at          ║
                ╚══════════════════════════╝

USERS (1) ─────────── MEDIA_FILES (many)
                ╔═══════════════════╗
                ║   MEDIA_FILES     ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║ FK user_id        ║
                ║    file_name      ║
                ║    original_name  ║
                ║    file_path      ║
                ║    cdn_url        ║
                ║    thumbnail_url  ║
                ║    mime_type      ║
                ║    file_size      ║
                ║    width          ║
                ║    height         ║
                ║    duration       ║
                ║    alt_text       ║
                ║    is_public      ║
                ╚═══════════════════╝
                         │
                         │ many:many via POST_MEDIA pivot
                         │

USERS (1) ─────────── POSTS (many)
                ╔═══════════════════╗
                ║      POSTS        ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║ FK user_id        ║
                ║ FK cover_media_id ║──null (SET NULL)
                ║    title          ║
                ║    content        ║
                ║    caption        ║
                ║    platforms      ║──JSON array
                ║    status         ║──enum[draft,scheduled,published,failed]
                ║    scheduled_at   ║
                ║    published_at   ║
                ║    is_deleted     ║──soft delete
                ╚═══════════════════╝
                         │         │
              ┌──────────┘         └──────────────────────┐
              │                                           │
              ▼                                           ▼
  ╔═══════════════════╗                     ╔════════════════════╗
  ║    POST_MEDIA     ║ (pivot)             ║    POST_TAGS       ║ (pivot)
  ╠═══════════════════╣                     ╠════════════════════╣
  ║ FK post_id        ║                     ║ FK post_id         ║
  ║ FK media_id       ║──→ MEDIA_FILES      ║ FK tag_id          ║──→ TAGS
  ║    sort_order     ║                     ╚════════════════════╝
  ╚═══════════════════╝

USERS (1) ─────────── TAGS (many)
                ╔═══════════════════╗
                ║      TAGS         ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║ FK user_id        ║
                ║    name           ║
                ║    color          ║──hex color
                ╚═══════════════════╝

USERS (1) ─────────── DEALS (many)
                ╔═══════════════════╗
                ║      DEALS        ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║ FK user_id        ║
                ║    brand_name     ║
                ║    brand_email    ║
                ║    brand_logo_url ║
                ║    title          ║
                ║    description    ║
                ║    amount         ║
                ║    currency       ║──default TZS
                ║    status         ║──enum[lead,negotiation,contract,active,completed,cancelled]
                ║    deal_type      ║──enum[sponsored_post,affiliate,ambassador,gifted,other]
                ║    deliverables   ║
                ║    deadline_at    ║
                ║    contracted_at  ║
                ║    completed_at   ║
                ║    notes          ║
                ║    is_deleted     ║──soft delete
                ╚═══════════════════╝
                         │         │
              ┌──────────┘         └──────────────────────┐
              │                                           │
              ▼                                           ▼
  ╔═══════════════════╗                     ╔════════════════════╗
  ║    DEAL_POSTS     ║ (pivot)             ║     INVOICES       ║
  ╠═══════════════════╣                     ╠════════════════════╣
  ║ FK deal_id        ║                     ║ PK id              ║
  ║ FK post_id        ║──→ POSTS            ║ FK user_id         ║
  ╚═══════════════════╝                     ║ FK deal_id         ║──null (SET NULL)
                                            ║    invoice_number  ║──unique
                                            ║    recipient_name  ║
                                            ║    recipient_email ║
                                            ║    line_items      ║──JSON
                                            ║    subtotal        ║
                                            ║    tax_rate        ║
                                            ║    tax_amount      ║
                                            ║    total           ║
                                            ║    currency        ║
                                            ║    status          ║──enum[draft,sent,paid,overdue,cancelled]
                                            ║    due_date        ║
                                            ║    paid_at         ║
                                            ║    pdf_url         ║
                                            ╚════════════════════╝

USERS (1) ─────────── NOTIFICATIONS (many)
                ╔═══════════════════╗
                ║  NOTIFICATIONS    ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║ FK user_id        ║
                ║    type           ║──varchar: post_published, deal_updated, etc.
                ║    title          ║
                ║    body           ║
                ║    action_url     ║
                ║    icon           ║
                ║    is_read        ║
                ║    read_at        ║
                ╚═══════════════════╝

USERS (1) ─────────── AUDIT_LOGS (many, nullable)
                ╔═══════════════════╗
                ║   AUDIT_LOGS      ║
                ╠═══════════════════╣
                ║ PK id (BIGINT)    ║
                ║ FK user_id        ║──null (SET NULL on user delete)
                ║    action         ║
                ║    entity_type    ║
                ║    entity_id      ║
                ║    old_values     ║──JSON
                ║    new_values     ║──JSON
                ║    ip_address     ║
                ║    user_agent     ║
                ╚═══════════════════╝

                ╔═══════════════════╗
                ║    JOB_QUEUE      ║
                ╠═══════════════════╣
                ║ PK id (BIGINT)    ║
                ║    queue          ║──default,analytics,cleanup
                ║    job_class      ║──publish_post,fetch_analytics,etc.
                ║    payload        ║──JSON
                ║    attempts       ║
                ║    max_attempts   ║
                ║    status         ║──enum[pending,running,completed,failed]
                ║    available_at   ║
                ║    started_at     ║
                ║    completed_at   ║
                ║    failed_at      ║
                ║    error_message  ║
                ╚═══════════════════╝

                ╔═══════════════════╗
                ║   RATE_LIMITS     ║
                ╠═══════════════════╣
                ║ PK id             ║
                ║    key (unique)   ║──ip:{ip}:{action}
                ║    tokens         ║──token bucket count
                ║    last_refill    ║
                ╚═══════════════════╝

AUTH TABLES:
USERS (1) ─── EMAIL_VERIFICATIONS (many)
USERS (1) ─── PASSWORD_RESETS (many)
USERS (1) ─── SESSIONS (many)
```

---

## Cardinality Summary

| Relationship | Type | Delete Behavior |
|---|---|---|
| users → user_preferences | 1:1 | CASCADE |
| users → analytics | 1:1 | CASCADE |
| users → notification_preferences | 1:1 | CASCADE |
| users → social_accounts | 1:many | CASCADE |
| users → tags | 1:many | CASCADE |
| users → media_files | 1:many | CASCADE |
| users → posts | 1:many | CASCADE |
| users → deals | 1:many | CASCADE |
| users → invoices | 1:many | CASCADE |
| users → notifications | 1:many | CASCADE |
| users → audit_logs | 1:many | SET NULL |
| users → analytics_snapshots | 1:many | CASCADE |
| users → sessions | 1:many | CASCADE |
| posts → post_media | 1:many | CASCADE |
| posts → post_tags | 1:many | CASCADE |
| posts → platform_post_results | 1:many | CASCADE |
| posts → deal_posts | 1:many | CASCADE |
| media_files → post_media | 1:many | CASCADE |
| media_files → posts (cover) | 1:many | SET NULL |
| tags → post_tags | 1:many | CASCADE |
| deals → deal_posts | 1:many | CASCADE |
| deals → invoices | 1:many | SET NULL |
| social_accounts → platform_post_results | 1:many | SET NULL |
| social_accounts → analytics_snapshots | 1:many | SET NULL |
