# CreatorzHive JSON API

All JSON endpoints use the same front controller as the web UI: **`?route=<name>`** on your app base URL (see `APP_URL` / `APP_BASE_PATH` in `.env`). Send **`Accept: application/json`** so errors return JSON bodies.

## Response envelope

| Field | Type | Meaning |
|--------|------|--------|
| `success` | boolean | `true` on success |
| `message` | string | Human-readable summary |
| `data` | mixed | Payload on success; often `null` |
| `errors` | object | On validation failure, field keys → arrays of messages |

HTTP status codes follow semantics (401 unauthorized, 403 forbidden/CSRF, 404, 422 validation, 500).

## Authentication

- **Session cookie** — After `login`, the browser session applies to `GET`/`POST` with cookies (typical in-app `fetch` from the same origin).
- **CSRF** — `POST` requests must include **`_token`** (same value as session CSRF). Retrieve it from any authenticated page (`window.__CSRF__`) or call **`api_me`** (`GET`, auth) which returns `csrf_token`.
- **Bearer token** — If `Authorization: Bearer …` is present, CSRF validation is skipped (see `backend/middleware/csrf.php`). Use only for trusted machine clients.

## Discovery (authenticated)

| Route | Method | Description |
|--------|--------|-------------|
| `api_me` | GET | Current user, `base_url_path`, CSRF token, posting hints. |
| `api_catalog` | GET | Routes available to **your** role (`method`, `route`, `middleware`). |

## Health

| Route | Method | Auth | Description |
|--------|--------|------|-------------|
| `ping` | GET | No | Liveness; `data` includes `app`, `version`, `environment`, `time`. |
| `db-test` | GET | **Admin** | MySQL version check (locked down; not public). |

## CORS (optional)

For browser SPAs on another origin, set in `.env`:

```env
API_CORS_ORIGINS=https://your-spa.example.com,http://localhost:5173
```

Values must **exactly** match the browser’s `Origin` header. Preflight **`OPTIONS`** is answered with `204` when `Origin` is allowed. JSON responses include `Access-Control-Allow-Origin` when configured.

Credentials: `Access-Control-Allow-Credentials: true` is set; your SPA must use `fetch(..., { credentials: 'include' })` and cannot use `*` origins.

## Posting bodies

Most API endpoints expect **`application/x-www-form-urlencoded`** (same as HTML forms). Some routes also merge `request_json_body()` — see `backend/routes/api.php` and the matching `src/Controllers/*` class.

## Admin vs creator

- Middleware **`non_admin`** — creators and brands only; admins receive **403 JSON** on these API routes.
- Middleware **`role:admin`** — admins only; hidden from creator’s **`api_catalog`** output.

## Route table source

Authoritative registration is **`backend/routes/api.php`**. `api_catalog` returns only routes registered **in API mode** (JSON handlers from `api.php`), not HTML page routes from `web.php`.
