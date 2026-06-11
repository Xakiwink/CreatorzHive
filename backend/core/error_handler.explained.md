# error_handler.php — Explained

**File:** `backend/core/error_handler.php`

---

## Purpose

Global error and exception handling. Converts PHP errors into exceptions, handles unhandled exceptions with appropriate JSON or HTML responses, and logs errors to daily log files with secret redaction.

---

## Functions

### `error_handler_register(): void`
Installs two global handlers:
- `set_error_handler`: converts PHP errors (`E_WARNING`, etc.) into `\ErrorException` — ensures all errors are catchable
- `set_exception_handler`: calls `error_handler_server_error_throwable()` for any unhandled exception

Called from `backend/index.php` after the full stack is loaded.

### `error_handler_wants_json(): bool`
Determines if the current request expects a JSON response by checking:
1. `Accept: application/json` header
2. `?route=api/...` query parameter
3. `/api/` in the request URI
4. Any `?route=` parameter present

### `error_handler_redact_secrets(string $text): string`
Regex-redacts sensitive values from log messages — replaces `password=...`, `token=...`, `secret=...` patterns with `[REDACTED]`.

### `error_handler_log(\Throwable $e): void`
Writes to `backend/storage/logs/error-YYYY-MM-DD.log`:
- Format: `[timestamp] message in file:line`
- Secret values redacted before writing
- Silently fails if log directory cannot be created

### `error_handler_not_found(string $message = '404 Not Found'): void`
Sends a 404 response:
- JSON if `error_handler_wants_json()` → `{ success: false, message: "..." }`
- HTML from `frontend/pages/errors/404.html` if file exists
- Inline `<h1>404 Not Found</h1>` as fallback

### `error_handler_server_error_throwable(\Throwable $e): void`
Sends a 500 response:
- **PHPUnit mode**: re-throws if the exception is a test exception (`TestResponseException`, `TestHtmlResponseException`, `TestRedirectException`)
- Logs the exception
- JSON response if `error_handler_wants_json()`:
  - `APP_DEBUG=true`: includes message + stack trace
  - `APP_DEBUG=false`: generic "Internal Server Error"
- HTML: loads `frontend/pages/errors/500.html` in production, or renders `<pre>` stacktrace in debug mode

---

## Debug vs Production Behavior

| Mode | JSON Response | HTML Response |
|------|--------------|---------------|
| `APP_DEBUG=true` | Full exception message + trace | Inline `<pre>` stacktrace |
| `APP_DEBUG=false` | "Internal Server Error" | `frontend/pages/errors/500.html` |

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Calls `error_handler_register()` |
| `backend/storage/logs/` | Error log files written here |
| `frontend/pages/errors/404.html` | Custom 404 page |
| `frontend/pages/errors/500.html` | Custom 500 page (production only) |
| `tests/Support/TestResponseException.php` | Re-thrown in PHPUnit mode |
