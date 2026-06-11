# `tests/Support/` — Test Harness

## 1. Folder Purpose

Helper classes shared by all integration tests. Provides the HTTP dispatch mechanism and the exception types that replace real HTTP responses during testing.

## 2. Files Overview

| File | Purpose |
|------|---------|
| `IntegrationTestCase.php` | Abstract base: `dispatchRoute()`, session reset, rate limit cleanup |
| `TestResponseException.php` | `TestResponseException`, `TestHtmlResponseException`, `TestRedirectException` |

## 3. How Integration Tests Work

1. `CREATORZHIVE_PHPUNIT = true` (set in `bootstrap.php`) causes response functions to throw instead of exiting
2. Tests call `dispatchRoute(method, route, post)` which runs the real router
3. The router dispatches to a real controller, which calls real services and repositories
4. The controller eventually calls `response_json()` → `TestResponseException` is thrown
5. `dispatchRoute()` catches it and returns it for assertion

## 4. Related Files

| File | Relationship |
|------|-------------|
| `tests/bootstrap.php` | Sets `CREATORZHIVE_PHPUNIT` constant |
| `tests/integration/` | All integration tests extend `IntegrationTestCase` |
