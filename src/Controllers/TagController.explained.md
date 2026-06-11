# TagController.php — Explained

**File:** `src/Controllers/TagController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Minimal CRUD for post tags. Tags are per-user, have a name and hex color, and are used to categorize posts in the planner.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$tags` | `TagRepository` | Tag DB queries |

---

## Methods

### `index()` — GET api/tags
Returns all tags for the authenticated user. Used to populate tag selector in post editor.

### `store()` — POST api/create_tag

1. Validate: name (required, max 100), color (max 7)
2. Validate color format: must match `#RRGGBB` hex pattern; defaults to `#6C5CE7` (purple) if invalid
3. Check if tag with same name already exists for user → return existing tag (idempotent)
4. Create new tag, return full row

**Idempotency:** Creates only if not exists, returns existing if found — safe to call from autocomplete/tag-on-the-fly UI.

---

## Notes

- No delete endpoint currently — tags are created and reused, not deleted
- The `post_tags` pivot table links posts to tags; `syncPostTags()` in `PostInputNormalizer` manages the pivot

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Repositories/TagRepository.php` | Tag DB queries |
| `src/Support/PostInputNormalizer.php` | `syncPostTags()` for pivot management |
| `backend/routes/api.php` | Route definitions |
