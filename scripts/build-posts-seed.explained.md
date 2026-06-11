# build-posts-seed.php — Explained

**File:** `scripts/build-posts-seed.php`

---

## Purpose

Code generator (not a runtime script). Outputs SQL `INSERT` statements for 25 demo posts to stdout. Redirect the output to create or regenerate `database/seeds/posts.sql`.

---

## Usage

```bash
php scripts/build-posts-seed.php > database/seeds/posts.sql
```

---

## Generated Data

All posts belong to `david@creatorzhive.com` (looked up at seed-time via `@uid` MySQL variable).

| Count | Status | Dates |
|-------|--------|-------|
| 10 | `published` | `published_at` = random within last 29 days |
| 5 | `scheduled` | `scheduled_at` = random within next 14 days |
| 7 | `draft` | No dates |
| 3 | `failed` | No dates |

Platform sets cycle across 5 combinations: `["instagram","tiktok"]`, `["youtube","facebook"]`, `["instagram"]`, `["tiktok","youtube"]`, `["facebook","instagram","tiktok"]`.

---

## Notes

- Uses `rand()` for dates — regenerating the file produces different relative dates each time
- `cover_media_id` is always `NULL` (no media attached to seed posts)
- Platforms stored as `CAST('...' AS JSON)` for MySQL JSON column compatibility

---

## Related Files

| File | Relationship |
|------|-------------|
| `database/seeds/posts.sql` | Output target |
| `scripts/seed.php` | Consumes `posts.sql` |
