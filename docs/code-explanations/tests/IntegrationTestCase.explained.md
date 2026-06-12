# IntegrationTestCase.php — Explained

**File:** `tests/Support/IntegrationTestCase.php`

---

## Purpose

Abstract base class for HTTP integration tests. Simulates the full request-dispatch-response cycle by manipulating PHP superglobals and calling `router_dispatch()`, then catching the `TestResponseException` to inspect the JSON response.

---

## `setUp()` / `tearDown()`

Each test starts with a clean slate:
- Destroys any active session; resets `$_SESSION`, `$_GET`, `$_POST`
- Resets `$_SERVER` to a default GET request from `127.0.0.1` with `Accept: application/json`
- Clears `rate_limits` rows (ip:*, login_identifier:*, login_alert:*) so auth tests don't get blocked by previous test runs

---

## `dispatchRoute(string $method, string $route, array $post): TestResponseException`

The main test helper. Steps:

1. Sets timezone from `APP_TIMEZONE` constant
2. Registers error handler
3. Starts session safely, generates a fresh CSRF token
4. Sets `$_SERVER['REQUEST_METHOD']`, `$_GET['route']`
5. Merges `$post` with `['_token' => $csrfToken]`
6. Calls `router_reset()`, re-requires both route files (`web.php`, `api.php`)
7. Enables API mode (`router_api_mode(true)`) so all routes return JSON
8. Calls `router_dispatch()` — expects it to throw `TestResponseException`

> Route files are re-required each test (not just once) because `router_reset()` clears the global route table.

---

## `uniqueClientIp(): string`

Returns a random `10.x.y.z` IP address. Use this for each test that exercises rate limiting — prevents tests from sharing rate limit counters and failing spuriously.

---

## `requireDatabase(): void`

Calls `db_fetch('SELECT 1')`. If it fails, marks the test as skipped with a message. Call this at the top of any test that needs the DB.

---

## Usage Pattern in Tests

```php
class FooControllerTest extends IntegrationTestCase {
    public function testCreateFoo(): void {
        $this->requireDatabase();
        $_SERVER['REMOTE_ADDR'] = $this->uniqueClientIp();
        
        $res = $this->dispatchRoute('POST', 'create_foo', ['name' => 'bar']);
        
        $this->assertSame(200, $res->httpStatus);
        $this->assertTrue($res->payload['success']);
    }
}
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `tests/Support/TestResponseException.php` | Exception type returned by `dispatchRoute()` |
| `tests/bootstrap.php` | Boots the full app before any test |
| `backend/routes/web.php`, `api.php` | Re-required on each dispatch |
