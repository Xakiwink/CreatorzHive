# TagRepository.php — Explained

**File:** `src/Repositories/TagRepository.php`

---

## Purpose

All database queries for the `tags` table. Tags are user-scoped labels (name + color) that can be attached to posts.

---

## Methods

### `getByUser(int $userId): array`
Returns all tags for a user, ordered by `name ASC`.

### `findById(int $id): ?array`
Fetches a tag by ID (no ownership check).

### `findByName(int $userId, string $name): ?array`
Finds a tag by name for a specific user. Used by `TagController::store()` to check for duplicates before inserting.

### `create(int $userId, string $name, string $color): int`
Inserts a new tag and returns the new ID.

### `delete(int $id, int $userId): bool`
Hard-deletes the tag. Ownership-scoped (`id` + `user_id`). Returns `true` if a row was deleted, `false` if not found.

---

## Notes

- `TagController::store()` is idempotent — it calls `findByName()` first and returns the existing tag if the name is already taken for the user, rather than inserting a duplicate.
- Tag names are not globally unique — different users can have tags with the same name.
- Deleting a tag does not cascade-delete `post_tags` associations (this is handled at the DB level by foreign key cascade or by the caller).

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/TagController.php` | Calls all methods |
| `src/Support/PostInputNormalizer.php` | Uses tag ownership for sync validation |
| `backend/compat/models.php` | `tag_*` global function wrappers |
