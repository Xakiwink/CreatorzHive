# `tests/` — Automated Tests

## 1. Folder Purpose

PHPUnit unit and integration tests for services and HTTP controllers.

## 2. Structure

| Path | Purpose |
|------|---------|
| `bootstrap.php` | Loads env, OOP boot, test DB |
| `unit/` | Isolated tests (AuthService, GoogleAuthService, …) |
| `integration/` | Full router dispatch via `IntegrationTestCase` |
| `Support/` | Test harness helpers |

## 3. Running

```bash
./vendor/bin/phpunit
```

Set `DB_DATABASE_TEST` in `.env` for isolated DB.

## 4. Coverage highlights

- Auth password hashing
- Google OAuth URL/redirect configuration
- CRUD flows for posts, deals, media, settings, admin

## 5. Improvement suggestions

- Add integration test for Google callback with mocked HTTP.
- CI workflow running PHPUnit on push.
