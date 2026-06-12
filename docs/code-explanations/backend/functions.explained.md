# functions.php — Explained

**File:** `backend/helpers/functions.php`
**Auto-loaded via:** Composer `autoload.files` (always available)

---

## Purpose

Global utility functions used throughout the entire application (both OOP and procedural layers). Loaded unconditionally by Composer autoload, making these functions always available. All functions check `function_exists()` before defining to prevent redeclaration errors.

---

## Functions

### `load_env(?string $envPath = null): void`
Parses the `.env` file and populates `$_ENV` and `putenv()`.

**Logic:**
- Static `$loaded` flag prevents double-loading
- Skips empty lines and `#` comments
- Splits on first `=` only (values can contain `=`)
- Strips surrounding quotes (`'` or `"`)
- Sets both `$_ENV[$key]` and `putenv('KEY=value')`

**Called by:** `backend/index.php` at startup

### `env(string $key, $default = null): mixed`
Reads an environment variable. Performs type casting:
- `'true'` / `'(true)'` → `true`
- `'false'` / `'(false)'` → `false`
- `'null'` / `'(null)'` → `null`
- `'empty'` / `'(empty)'` → `''`
- Otherwise: returns string value

**Checks both** `$_ENV` and `getenv()`. Returns `$default` if not set or empty.

### `base_path(string $path = ''): string`
Returns the absolute project root path. Calculated as `dirname(__FILE__, 2)` (two levels up from `helpers/`).

**Example:** `base_path('public/uploads')` → `/var/www/html/creatorzhive/public/uploads`

### `public_path(string $path = ''): string`
Returns the absolute path to the `public/` directory.

### `base_url_path(): string`
Returns the URL path prefix for subdirectory installs. Cached with static variable.

**Priority:**
1. `APP_BASE_PATH` env variable
2. Parse path from `APP_URL` (e.g., `http://localhost/creatorzhive` → `/creatorzhive`)
3. Empty string for root installs

**Used by:** `route_url()`, `asset_url()`, `upload_url()`

### `asset_url(string $path): string`
Returns full web URL for a frontend asset.

**Example:** `asset_url('frontend/css/main.css')` → `/creatorzhive/frontend/css/main.css`

### `upload_url_needs_public_segment(): bool`
Determines if uploaded file URLs need a `/public/` segment. Uses `DOCUMENT_ROOT` comparison.

**If document root is `project/public/`:** URL = `/uploads/...`
**If document root is `project/` (wrong):** URL = `/public/uploads/...`

### `upload_url(string $relativePath): string`
Returns the public URL for an uploaded file.

**Example:** `upload_url('uploads/2026/05/abc.jpg')` → `/creatorzhive/uploads/2026/05/abc.jpg`

### `frontend_user_payload(array $user): array`
Normalizes a user array for the frontend `window.__USER__` global. Delegates to `UserPayloadFormatter::forApi()` if container is available.

**Returns:** `['id' => int, 'name' => string, 'username' => string, 'email' => string, 'role' => string, 'avatar_url' => string]`

### `frontend_session_user_payload(): array`
Convenience wrapper: `frontend_user_payload(session_get_user() ?? [])`.

### `route_url(string $route, array $query = []): string`
Builds a full front-controller URL with `?route=` parameter.

**Example:** `route_url('dashboard')` → `/creatorzhive/?route=dashboard`
**Example:** `route_url('login', ['error' => 'invalid'])` → `/creatorzhive/?route=login&error=invalid`

### `storage_path(string $path = ''): string`
Returns path to `backend/storage/`. Used for logs, uploads, cron state.

### `dd(...$vars): void`
Debug dump — `var_dump()` all args and exit. Development only.

### `now(): string`
Returns current datetime as MySQL-formatted string: `date('Y-m-d H:i:s')`.

### `slugify(string $text): string`
Converts text to URL-safe slug: lowercase, non-alphanumeric → `-`, trim dashes.

### `sanitize(string $input): string`
`trim(strip_tags($input))` — removes HTML tags and whitespace.

### `generateToken(int $length = 64): string`
Generates a cryptographically secure hex token.
`bin2hex(random_bytes(ceil($length/2)))` truncated to `$length`.

### `google_auth_is_configured(): bool`
Returns `true` if `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are set.
Also checks via container if available.

### `google_auth_start_url(string $role = 'creator'): string`
Returns the URL for Google Sign-In redirect.

### `app_connection(): ?Connection`
Returns the OOP `Connection` from the DI container if available. Used by compat code to get a typed connection.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Calls `load_env()` |
| `backend/core/*.php` | All use `env()`, `base_path()`, `storage_path()` |
| `frontend/pages/partials/app_script_globals.php` | Uses `frontend_session_user_payload()`, `route_url()` |
| `src/Core/Security/TokenCrypto.php` | Calls `env('APP_SECRET')` |
