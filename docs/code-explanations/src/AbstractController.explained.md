# AbstractController.php — Explained

**File:** `src/Controllers/Support/AbstractController.php`
**Namespace:** `CreatorzHive\Controllers\Support`
**Type:** Abstract base class

---

## Purpose

The base class that all controllers extend. Provides the three shared dependencies (`ViewRenderer`, `JsonResponder`, `Connection`) and three protected helper methods available to every controller.

---

## Properties

| Property | Type | Purpose |
|----------|------|---------|
| `$views` | `ViewRenderer` | Renders PHP templates to HTML |
| `$json` | `JsonResponder` | Sends JSON responses (success/error) |
| `$db` | `Connection` | Direct database access for simple ad-hoc queries |

## Methods

### `redirect(string $url): void`
Issues an HTTP redirect. Prefers `http_redirect()` (procedural compat layer) if available; falls back to `header('Location: ...')` + exit.

### `sessionUser(): ?array`
Returns the current authenticated user from PHP session, or `null` if not logged in. Calls `session_get_user()` if function exists.

### `requireAuth(): array`
Returns session user or immediately sends a 401 JSON error and exits. Shorthand for the "require login" check that most API methods need.

---

## Usage Pattern

```php
final class DashboardController extends AbstractController
{
    public function data(): void
    {
        $user = $this->requireAuth();   // exits with 401 if not logged in
        $this->json->success($payload, 'OK');
    }
}
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Controllers/*.php` | All controllers extend this class |
| `src/Core/Http/ViewRenderer.php` | `$this->views` instance |
| `src/Core/Http/JsonResponder.php` | `$this->json` instance |
| `src/Core/Database/Connection.php` | `$this->db` instance |
