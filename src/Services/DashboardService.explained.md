# DashboardService.php — Explained

**File:** `src/Services/DashboardService.php`
**Namespace:** `CreatorzHive\Services`

---

## Purpose

Assembles the complete dashboard data payload for a user. Aggregates stats, recent posts, upcoming posts, platform connection status, and post status breakdown into one response object. Keeps `DashboardController` thin.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$repository` | `DashboardRepository` | Creator summary and social account queries |

---

## Constants

```php
PLATFORM_SLUGS = ['instagram', 'tiktok', 'youtube', 'facebook', 'twitter']
```

Used to build the platform status list — always shows all 5 platforms even if not connected.

---

## Method: `buildPayload(int $userId): array`

**Returns:**
```json
{
  "stats": {
    "total_posts": 45,
    "published_posts": 32,
    "scheduled_posts": 8,
    "total_followers": 12500,
    "avg_engagement_rate": 3.5,
    "total_revenue": 2500000,
    "active_deals": 3,
    "unread_notifications": 2,
    "trend_posts": 0,
    "trend_published": 0,
    "trend_scheduled": 0,
    "trend_followers": 0
  },
  "recent_posts": [...],
  "upcoming_posts": [...],
  "platform_status": [
    { "platform": "instagram", "connected": true, "username": "my_handle" },
    { "platform": "tiktok", "connected": false, "username": null },
    ...
  ],
  "post_status_breakdown": { "draft": 5, "scheduled": 8, "published": 32 }
}
```

**Data sources:**
- `DashboardRepository::findCreatorSummary()` → aggregated stats
- `post_get_recent_by_user()` → last 5 posts (procedural bridge)
- `post_get_upcoming()` → next 5 scheduled posts (procedural bridge)
- `post_count_by_status()` → status breakdown (procedural bridge)
- `DashboardRepository::findActiveSocialAccounts()` → connected platforms

**Note:** `trend_*` fields are always 0 in the current implementation — a placeholder for future period-over-period comparison.

---

## Known Issue

Uses three procedural function_exists fallback calls (`post_get_recent_by_user`, `post_get_upcoming`, `post_count_by_status`) rather than injecting `PostRepository` directly. These should be replaced with direct repository calls.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/DashboardRepository.php` | Primary data source |
| `src/Controllers/DashboardController.php` | Calls `buildPayload()` |
| `backend/compat/services.php` | Procedural post bridges |
