# response.php — Explained

**File:** `backend/core/response.php`

---

## Purpose

Procedural HTTP response functions. Terminates request execution after sending response. Includes test-mode hooks that throw exceptions instead of sending real HTTP responses (for PHPUnit compatibility).

---

## Functions

### `response_json(array $data, int $status): void`

Sends a JSON response:
1. In PHPUnit mode: throws `TestResponseException` (tests catch this to inspect response)
2. Sets HTTP status code
3. Emits CORS headers if `api_cors_emit_headers()` is available
4. Sets `Content-Type: application/json; charset=utf-8`
5. Encodes with `JSON_UNESCAPED_SLASHES` + `JSON_INVALID_UTF8_IGNORE` (if available)
6. Falls back to encoding error message if `json_encode()` fails
7. `exit` after output

### `response_html(string $content, int $status): void`
Sends HTML response. In PHPUnit mode: throws `TestHtmlResponseException`.

### `response_redirect(string $url, int $status): void`
Issues HTTP redirect (default 302). In PHPUnit mode: throws `TestRedirectException` with URL.

### Convenience Functions

| Function | Status | Response |
|----------|--------|----------|
| `response_not_found_json(string $message)` | 404 | JSON error |
| `response_forbidden_json(string $message)` | 403 | JSON error |
| `response_server_error_json(string $message)` | 500 | JSON error |

---

## Testing Integration

All three response functions check `CREATORZHIVE_PHPUNIT` constant. When defined and true, they throw typed exceptions instead of sending headers. Tests can use try/catch to inspect what the code "would have sent" without needing HTTP infrastructure.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Http/JsonResponder.php` | OOP wrapper that delegates to `response_json()` |
| `backend/helpers/api_cors.php` | `api_cors_emit_headers()` function |
| `tests/Support/TestResponseException.php` | PHPUnit exception class |
