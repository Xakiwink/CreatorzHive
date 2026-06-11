# database.php — Explained

**File:** `backend/core/database.php`

---

## Purpose

Procedural database access layer. Wraps PDO with named helper functions used throughout the codebase. Bridges between procedural code and the OOP `Connection` class — always prefers the OOP connection when the DI container is available.

---

## Connection: `db_get_pdo()`

Resolution order:
1. Returns cached PDO from `$GLOBALS['_cz_pdo']` if already connected
2. Pulls `Connection` from DI container (`$GLOBALS['cz_container']`) and caches its PDO
3. Creates a raw PDO directly from environment variables as last resort

PDO options set on direct creation:
- `ERRMODE_EXCEPTION` — all errors throw exceptions
- `FETCH_ASSOC` — all results are associative arrays
- `EMULATE_PREPARES => false` — real prepared statements

---

## Query Safety Utilities

### `db_quote_column(string $column): string`
Wraps in backticks, escaping embedded backticks by doubling them. Prevents SQL injection in dynamic column names.

### `db_quote_table(string $table): string`
Same as `db_quote_column` but supports `database.table` dot notation — each part quoted separately.

### `db_sql_sort_direction(string $dir): string`
Returns `'ASC'` or `'DESC'` only — prevents ORDER BY injection when direction comes from user input.

---

## Pagination Helpers

### `db_pagination(int $limit, int $offset, int $maxLimit = 500): array`
Clamps limit to `[1, maxLimit]` and offset to `[0, ∞)`.

### `db_bind_limit(array $params, int $limit): array`
Adds `limit` key to a params array.

### `db_bind_limit_offset(array $params, int $limit, int $offset): array`
Adds both `limit` and `offset` keys.

---

## IN Clause Helper

### `db_in_int_placeholders(array $ids, string $prefix = 'in'): array`
Safely builds parameterized `IN (...)` SQL:
- Deduplicates, filters non-positive values, casts to int
- Returns `['sql' => ':in_0, :in_1, ...', 'params' => ['in_0' => 1, ...]]`
- Returns `['sql' => 'NULL', 'params' => []]` for empty input (query matches nothing)

---

## CRUD Functions

All CRUD functions prefer the OOP `Connection` when available; fall back to direct PDO otherwise.

| Function | Description |
|----------|-------------|
| `db_query(string $sql, array $params)` | Prepare + execute, returns PDOStatement |
| `db_fetch(string $sql, array $params)` | Fetch single row or `null` |
| `db_fetch_all(string $sql, array $params)` | Fetch all rows |
| `db_insert(string $table, array $data)` | INSERT and return last insert ID |
| `db_update(string $table, array $data, string $where, array $params)` | UPDATE, returns row count |
| `db_delete(string $table, string $where, array $params)` | DELETE, returns row count |

Column names in `db_insert` and `db_update` are auto-quoted via `db_quote_column()`.

---

## Transaction Functions

| Function | Description |
|----------|-------------|
| `db_begin_transaction()` | Starts a transaction |
| `db_commit()` | Commits |
| `db_rollback()` | Rolls back |
| `db_last_insert_id()` | Returns last auto-increment ID as string |

---

## Deprecated Functions

| Function | Replacement |
|----------|-------------|
| `db_fetchAll()` | `db_fetch_all()` |
| `db_connection()` | `db_get_pdo()` — kept for `scripts/migrate.php` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Database/Connection.php` | OOP PDO wrapper; preferred when container is available |
| `backend/core/validator.php` | Uses `db_fetch()` for `unique:` rule |
| `src/Repositories/*.php` | OOP repositories use `Connection`, not these functions |
| `scripts/migrate.php` | Uses `db_connection()` (deprecated alias) |
