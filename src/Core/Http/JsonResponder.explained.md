# JsonResponder.php — Explained

**File:** `src/Core/Http/JsonResponder.php`
**Namespace:** `CreatorzHive\Core\Http`

---

## Purpose

Sends structured JSON API responses. Enforces a consistent response envelope across all API endpoints. Injected into all controllers as `$this->json`.

---

## Response Envelope

All API responses follow this structure:
```json
{
  "success": true|false,
  "message": "Human-readable string",
  "data": { ... } | null,
  "errors": { "field": ["message"] }
}
```

- `data` is present on success responses
- `errors` is present on error responses (especially validation failures)

---

## Methods

### `success(?array $data, string $message, int $status): void`
Sends a success response. Status defaults to 200.

```php
$this->json->success(['id' => $id], 'Post created successfully');
```

### `error(string $message, int $status, array $errors): void`
Sends an error response. Commonly used with:
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Unprocessable Entity (validation)
- 429 Too Many Requests
- 500 Internal Server Error

```php
$this->json->error('Validation failed', 422, ['email' => ['Invalid email address']]);
```

### `send(array $payload, int $status): void`
Low-level send. In production delegates to `response_json()` (defined in `backend/core/response.php`). Fallback: sets status code, Content-Type header, echoes JSON, exits.

---

## Note

Calling any of these methods **terminates execution** (`exit` is called after output). This is intentional — controllers return early by calling `$this->json->error()` and don't need explicit `return` statements after.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/core/response.php` | `response_json()` production implementation |
| `src/Controllers/Support/AbstractController.php` | `$this->json` property |
| `frontend/js/utils.js` | Parses this envelope format |
