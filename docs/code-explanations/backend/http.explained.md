# http.php — Explained

**File:** `backend/http.php`

---

## Purpose

Thin HTTP convenience wrapper. Provides named shorthand functions over the lower-level response/session functions. Used throughout route handlers and controllers to send responses and render views.

---

## Functions

### `http_json(array $data, int $status = 200): void`
Alias for `response_json()`. Terminates execution.

### `http_json_success($data = null, string $message = 'Success'): void`
Sends a standardized success envelope:
```json
{ "success": true, "message": "Success", "data": <data> }
```

### `http_json_error(string $message, int $status = 400, array $errors = []): void`
Sends a standardized error envelope:
```json
{ "success": false, "message": "...", "errors": [] }
```

### `http_view(string $template, array $data = []): void`
Renders a frontend view template:
1. Looks for `frontend/pages/{template}.php` — extracts `$data` into scope, captures output, sends HTML response
2. Falls back to `frontend/pages/{template}.html` — sends file content as HTML
3. If neither exists, calls `error_handler_not_found()`

Uses `EXTR_SKIP` to prevent template data from overwriting existing variables.

### `http_redirect(string $url): void`
Alias for `response_redirect()`.

### `http_is_post(): bool`
Returns `true` if the request method is POST.

### `http_is_get(): bool`
Returns `true` if the request method is GET.

### `http_current_user(): ?array`
Returns the current session user via `session_get_user()`. Returns `null` if not logged in.

---

## Notes

- All response-sending functions terminate execution — no code runs after them.
- `http_view()` is the canonical way to render PHP templates. The OOP `ViewRenderer` delegates to this function.
- Template path is `frontend/pages/` (relative to project root via `base_path()`).

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/response.php` | `response_json()`, `response_html()`, `response_redirect()` |
| `backend/core/session.php` | `session_get_user()` |
| `src/Core/Http/ViewRenderer.php` | OOP class that calls `http_view()` |
| `frontend/pages/` | Template files rendered by `http_view()` |
