# tests/bootstrap.php — Explained

**File:** `tests/bootstrap.php`

---

## Purpose

PHPUnit bootstrap file. Sets up the test environment before any test runs: defines the `CREATORZHIVE_PHPUNIT` constant, loads the app, and optionally switches to a test database.

---

## Execution Order

1. Defines `CREATORZHIVE_PHPUNIT = true` — tells `response_json()` / `JsonResponder` to throw `TestResponseException` instead of calling `exit()`
2. Starts output buffering so any accidental `echo` during bootstrap doesn't interfere with test output
3. Loads `vendor/autoload.php` (Composer PSR-4)
4. Loads `backend/helpers/functions.php` for `load_env()` and `base_path()`
5. Reads `.env` via `load_env()`
6. If `DB_DATABASE_TEST` env var is set, overrides `DB_DATABASE` so all DB calls go to the test database
7. Requires `backend/bootstrap-oop.php` and `backend/bootstrap-procedural.php` to fully boot the app

---

## Test Database Isolation

Set `DB_DATABASE_TEST=creatorz_hive_test` in `.env` or `phpunit.xml` to run tests against a dedicated database. Without this, tests run against the main database.

---

## `CREATORZHIVE_PHPUNIT` Constant

When this constant is `true`, response functions (`response_json`, `response_redirect`, `response_view`) throw typed exceptions instead of sending HTTP responses and exiting. This lets integration tests catch and inspect the response.

---

## Related Files

| File | Relationship |
|------|-------------|
| `tests/Support/TestResponseException.php` | The exception thrown by response functions in test mode |
| `tests/Support/IntegrationTestCase.php` | Base class for integration tests |
| `phpunit.xml` | Declares this file as the bootstrap |
