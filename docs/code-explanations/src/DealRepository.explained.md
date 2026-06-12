# DealRepository.php — Explained

**File:** `src/Repositories/DealRepository.php`

---

## Purpose

All database queries for the `deals` table. Implements the 6-stage Kanban pipeline, soft-delete pattern, revenue reporting, audit log access, and deal-post associations.

---

## Enumerations (No DB Lookup)

### `statusesList(): array`
Hardcoded statuses (Kanban order):
`['lead', 'negotiation', 'contract', 'active', 'completed', 'cancelled']`

### `typesList(): array`
Hardcoded deal types:
`['sponsored_post', 'affiliate', 'ambassador', 'gifted', 'other']`

---

## Read Methods

### `getByUser(int $userId, array $filters): array`
Lists all active (non-deleted) deals for a user. Optional filters:
- `status`: validates against `statusesList()` (unknown statuses ignored)
- `deal_type`: validates against `typesList()`
- `search`: LIKE match on `brand_name` and `title`

Results ordered by `updated_at DESC`.

### `getKanban(int $userId): array`
Returns deals grouped by status for Kanban view:
```php
['lead' => [...], 'negotiation' => [...], ...]
```
All status keys always present (empty arrays for empty columns). Each deal is normalized.

### `findById(int $id): ?array`
Fetches a deal by ID, ignoring soft-deleted rows.

### `findForUser(int $id, int $userId): ?array`
Ownership-scoped version of `findById`. Returns `null` if the deal doesn't belong to the user.

### `getRevenueSummary(int $userId): array`
Single query with CASE aggregation. Returns:
- `earned_revenue`: sum of completed deal amounts (TZS only)
- `pipeline_revenue`: sum of active deal amounts (TZS only)
- `total_deals`, `completed_deals`, `active_deals` counts

**Note:** Only TZS currency is included in revenue sums. Multi-currency deals in other currencies are excluded.

### `getRecentActivity(int $userId, int $limit): array`
Reads from `audit_logs` table. Returns audit entries where `entity_type = 'deal'` or `action LIKE 'deal.%'`, ordered by `created_at DESC`.

### `getActivityForDeal(int $dealId, int $userId, int $limit): array`
Deal-specific audit log history.

### `getLinkedPosts(int $dealId, int $userId): array`
Returns posts linked to a deal via the `deal_posts` pivot table. Filters by `user_id` on the posts table (ownership check).

---

## Write Methods

### `prepareRow(array $data, bool $forInsert): array`
Normalizes and validates a row before INSERT/UPDATE:
- Validates status (defaults to `'lead'`)
- Validates deal_type (defaults to `'sponsored_post'`)
- Currency: 3-char uppercase, defaults to `'TZS'`
- Amount: numeric string, defaults to `'0'`
- Nullable fields set to `null` when empty string
- `user_id` excluded from the row when `$forInsert = false` (updates don't change ownership)

### `create(array $data): int`
Inserts a new deal and returns the new ID.

### `update(int $id, array $data): bool`
Partial update — only columns present in `$data` are updated. Column names allowlisted and backtick-quoted. Returns `false` if `$data` has no recognized columns.

### `updateStatus(int $id, string $status, int $userId): bool`
Updates only the status field. Side effects:
- `completed` → sets `completed_at = COALESCE(completed_at, CURDATE())`
- `contract` → sets `contracted_at = COALESCE(contracted_at, CURDATE())`

Timestamps use `COALESCE` to avoid overwriting already-set dates.

### `softDelete(int $id, int $userId): bool`
Sets `is_deleted = 1`. Deal stays in DB; filtered out by all SELECT queries.

### `attachPost(int $dealId, int $postId): void`
Links a post to a deal via `deal_posts`. Uses `INSERT IGNORE` — idempotent, no error if already linked.

---

## normalizeDealRow(array $row): array
Casts `id`, `user_id` to int, `amount` to string, `is_deleted` to int. Applied on all SELECT results.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/DealController.php` | Primary caller |
| `src/Support/DealWorkflowHelper.php` | Calls `updateStatus()` + `logAudit()` |
| `backend/compat/models.php` | `deal_*` global function wrappers |
