# TestResponseException.php — Explained

**File:** `tests/Support/TestResponseException.php`

---

## Purpose

Defines three exception types that replace actual HTTP responses during PHPUnit tests. When `CREATORZHIVE_PHPUNIT = true`, response functions throw these instead of sending headers and calling `exit()`.

---

## Exception Classes

### `TestResponseException`
Thrown by `response_json()` / `JsonResponder::send()`.

```php
$e->payload     // array — the decoded JSON payload
$e->httpStatus  // int — the HTTP status code (e.g. 200, 422, 401)
```

### `TestHtmlResponseException`
Thrown by `response_view()` / `ViewRenderer::render()`.

```php
$e->html        // string — rendered HTML content
$e->httpStatus  // int
```

### `TestRedirectException`
Thrown by `response_redirect()`.

```php
$e->url         // string — redirect target URL
$e->httpStatus  // int — typically 302
```

---

## How It Works

Integration tests call `dispatchRoute()` which calls `router_dispatch()`. The router calls a controller which eventually calls a response function. That function checks `defined('CREATORZHIVE_PHPUNIT')` and throws one of these exceptions instead of outputting. `IntegrationTestCase::dispatchRoute()` catches `TestResponseException` and returns it.

---

## Related Files

| File | Relationship |
|------|-------------|
| `tests/Support/IntegrationTestCase.php` | Catches `TestResponseException` in `dispatchRoute()` |
| `tests/bootstrap.php` | Defines `CREATORZHIVE_PHPUNIT = true` |
| `src/Core/Http/JsonResponder.php` | Throws `TestResponseException` when constant is set |
| `backend/core/response.php` | Procedural equivalent — same check |
