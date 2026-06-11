# DashboardRepository.php — Explained

**File:** `src/Repositories/DashboardRepository.php`

---

## Purpose

Lightweight read-only repository for dashboard-specific queries. Follows SRP — exists to isolate dashboard persistence from the `DashboardService`.

---

## Methods

### `findCreatorSummary(int $userId): array`

Reads from `v_creator_summary` — a MySQL view that aggregates:
- `total_posts`, `published_posts`, `scheduled_posts`
- `total_followers`, `avg_engagement_rate`, `total_revenue`
- `active_deals`, `unread_notifications`

If no row exists for the user (view returns nothing), falls back to an array of zeroes. This ensures the dashboard always has a valid structure regardless of DB state.

### `findActiveSocialAccounts(int $userId): array`

Returns only `platform`, `username`, `is_active` columns from `social_accounts` for active accounts (`is_active = 1`). Deliberately excludes token columns.

---

## Notes

- `v_creator_summary` is a database view — the actual aggregation SQL lives in the schema, not in PHP.
- This repository is intentionally thin. More complex dashboard data (recent posts, upcoming posts) is assembled in `DashboardService` using other repositories and compat bridge functions.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/DashboardService.php` | Calls both methods to build the dashboard payload |
