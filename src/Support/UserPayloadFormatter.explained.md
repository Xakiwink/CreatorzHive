# UserPayloadFormatter.php — Explained

**File:** `src/Support/UserPayloadFormatter.php`
**Namespace:** `CreatorzHive\Support`

---

## Purpose

Normalizes a raw database user row into a safe, consistent shape for API responses and the frontend `window.__USER__` global. Strips password, normalizes avatar URL, ensures type consistency.

---

## Method: `forApi(array $user): array`

**Input:** A raw `users` table row (may contain `password`, `google_id`, etc.)

**Output:**
```php
[
    'id'         => int,
    'name'       => string,
    'username'   => string,
    'email'      => string,
    'role'       => string,  // creator|brand|admin (defaults to 'creator')
    'avatar_url' => string,  // absolute URL if relative path
]
```

**Avatar URL handling:**
- If avatar_url starts with `http://` or `https://` → kept as-is (Google OAuth photo URL)
- If avatar_url is a relative path (e.g., `uploads/2026/05/abc.jpg`) → converted to absolute via `upload_url()`
- If avatar_url is empty → returns empty string

---

## Notes

- This is called by the global `frontend_user_payload()` function in `backend/helpers/functions.php` when the DI container is available
- Used in `frontend/pages/partials/app_script_globals.php` to set `window.__USER__`

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/helpers/functions.php` | `frontend_user_payload()` delegates to this class |
| `frontend/pages/partials/app_script_globals.php` | Consumes this payload for JS global |
