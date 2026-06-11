# Connection.php — Explained

**File:** `src/Core/Database/Connection.php`
**Namespace:** `CreatorzHive\Core\Database`

---

## Purpose

A **PDO wrapper** that provides safe, prepared-statement-only database access. This is the single database access point for all repository classes. It prevents SQL injection by design — there is no way to execute a raw string query through the public API.

---

## Imports

| Import | Why |
|--------|-----|
| `AppConfig` | Reads DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD |
| `PDO` | PHP Data Objects — database extension |
| `PDOStatement` | Type hint for query results |
| `RuntimeException` | Thrown on prepare failure |

---

## Class: Connection

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$pdo` | `?PDO` | Lazy-initialized PDO instance (null until first use) |
| `$config` | `AppConfig` | Configuration object |

### Methods

#### `pdo(): PDO`
Returns the PDO instance, creating it on first call (lazy initialization).

Configuration:
- DSN: `mysql:host={host};port={port};dbname={db};charset=utf8mb4`
- `PDO::ATTR_ERRMODE` = `ERRMODE_EXCEPTION` — throws `PDOException` on errors
- `PDO::ATTR_DEFAULT_FETCH_MODE` = `FETCH_ASSOC` — returns arrays by default
- `PDO::ATTR_EMULATE_PREPARES` = `false` — uses real prepared statements (not emulated)

#### `query(string $sql, array $params = []): PDOStatement`
Prepares and executes a SQL statement with named parameters.

**Input:** SQL string with `:named` placeholders + parameter array
**Output:** Executed `PDOStatement`

**Example:**
```php
$stmt = $db->query('SELECT * FROM users WHERE id = :id', ['id' => 42]);
```

#### `fetchOne(string $sql, array $params = []): ?array`
Executes query and returns first row as associative array, or `null` if no results.

#### `fetchAll(string $sql, array $params = []): array`
Executes query and returns all rows as array of associative arrays.

#### `insert(string $table, array $data): string`
Builds and executes an `INSERT` statement from an associative array. Returns the `lastInsertId()`.

**Safety:** Column names are backtick-quoted via `quoteColumn()`. Values are bound as named parameters.

#### `update(string $table, array $data, string $where, array $params = []): int`
Builds and executes an `UPDATE` statement. Returns number of affected rows.

**Safety:** Set values use `:set_{column}` prefix to avoid naming conflicts with WHERE params.

#### `delete(string $table, string $where, array $params = []): int`
Executes `DELETE FROM {table} WHERE {where}`. Returns affected rows.

#### `beginTransaction() / commit() / rollBack()`
Transaction control — delegates to PDO.

#### `quoteColumn(string $column): string`
Wraps column name in backticks: `` `column` ``. Escapes embedded backticks.

#### `quoteTable(string $table): string`
Wraps table name (supports `schema.table` dot notation).

#### `pagination(int $limit, int $offset, int $maxLimit = 500): array`
Sanitizes pagination parameters. Clamps `limit` to `[1, maxLimit]` and `offset` to `[0, ∞)`.

#### `bindLimit/bindLimitOffset`
Convenience methods that add `limit` and `offset` to a params array.

#### `inIntPlaceholders(array $ids, string $prefix = 'in'): array`
Generates safe IN clause placeholders for a list of IDs. Filters invalid IDs, deduplicates.

**Returns:** `['sql' => ':in_0, :in_1, :in_2', 'params' => ['in_0' => 1, 'in_1' => 2, 'in_2' => 3]]`

#### `sortDirection(string $dir): string`
Whitelists sort direction to `'ASC'` or `'DESC'` (defaults to DESC). Prevents ORDER BY injection.

---

## Security Implications

- **SQL Injection Prevention**: `PDO::ATTR_EMULATE_PREPARES = false` forces real server-side prepared statements. Even with `fetchOne()` / `fetchAll()`, parameters are never concatenated into SQL.
- **Column/Table quoting**: `quoteColumn()` and `quoteTable()` prevent injection through dynamic column names.
- **No raw query exposure**: There is no `raw()` method. All queries go through `prepare()`.
- **Risk**: The `$where` parameter in `update()` and `delete()` is passed as a raw SQL fragment. Callers must ensure this comes from trusted application code, not user input.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Config/AppConfig.php` | Provides DB credentials |
| `src/Providers/AppServiceProvider.php` | Instantiates Connection, injects AppConfig |
| All `src/Repositories/*.php` | Receive Connection via constructor |
| `backend/core/database.php` | Procedural wrappers that use `$GLOBALS['_cz_pdo']` |
