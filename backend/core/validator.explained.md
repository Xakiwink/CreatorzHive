# validator.php — Explained

**File:** `backend/core/validator.php`

---

## Purpose

Procedural validation engine for request payloads. Used throughout all controllers via `validator_validate()`. Supports Laravel-style pipe-delimited rule strings or arrays.

---

## Function: `validator_validate(array $data, array $rules): array`

**Input:**
- `$data`: The payload to validate (typically from `request_all()`)
- `$rules`: Rule definitions per field

**Output:**
```php
['valid' => true|false, 'errors' => ['field' => ['error message', ...]]]
```

---

## Supported Rules

| Rule | Description |
|------|-------------|
| `required` | Value must be non-empty |
| `email` | Must pass `FILTER_VALIDATE_EMAIL` |
| `url` | Must pass `FILTER_VALIDATE_URL` |
| `numeric` | Must be `is_numeric()` |
| `min:N` | String length ≥ N (multibyte) |
| `max:N` | String length ≤ N (multibyte) |
| `confirmed` | Value must equal `{field}_confirmation` |
| `in:a,b,c` | Value must be one of the listed options |
| `unique:table,column` | Value must not exist in DB |

---

## Usage Examples

```php
// String form (pipe-separated)
$v = validator_validate($payload, [
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'role' => 'in:creator,brand',
]);

// Array form
$v = validator_validate($data, [
    'amount' => ['required', 'numeric'],
    'currency' => ['in:TZS,USD,EUR'],
]);
```

---

## `unique:` Rule Security

Only allowed tables are accepted for `unique:` checks (allowlist in `validator_unique_tables()`). Table and column names are quoted via `db_quote_table()` / `db_quote_column()` to prevent SQL injection.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/*.php` | All controllers call `validator_validate()` |
| `backend/core/database.php` | `db_fetch()`, `db_quote_table()`, `db_quote_column()` |
