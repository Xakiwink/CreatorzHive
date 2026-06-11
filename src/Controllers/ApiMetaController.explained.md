# ApiMetaController.php — Explained

**File:** `src/Controllers/ApiMetaController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Provides two meta-API endpoints that describe the API itself rather than application data: the current authenticated user + CSRF token, and a catalog of available routes by role.

---

## Constructor Dependencies

| Parameter | Type | Purpose |
|-----------|------|---------|
| `$apiMeta` | `ApiMetaService` | Builds `api_me` payload and filters route catalog |

---

## Methods

### `systemApiMe()` — GET api/api_me

Returns the current user's identity and a fresh CSRF token. Used by frontend on page load to bootstrap state before making mutations.

Response (via `ApiMetaService::authenticatedClientMeta()`):
```json
{
  "user": { "id", "name", "username", "email", "role", "avatar_url" },
  "csrf_token": "...",
  "app": "CreatorzHive",
  "version": "1.0.0"
}
```

### `systemApiCatalog()` — GET api/api_catalog

Returns a machine-readable catalog of all API routes visible to the current user's role.

Response includes:
- `api_version`: 1
- `app` / `app_version` constants
- `envelope`: describes response structure
- `routes`: filtered list from `ApiMetaService::catalogRoutesForRole($role)` — admin users see admin routes, creators see creator routes

---

## Use Cases

- **SPA bootstrap**: `api_me` is fetched once on load to get user identity and CSRF token without a full page render
- **Developer tools**: `api_catalog` provides a self-documenting API list for SDK consumers or debugging

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Services/ApiMetaService.php` | Data assembly and route catalog logic |
| `backend/routes/api.php` | Routes `api_me` and `api_catalog` |
