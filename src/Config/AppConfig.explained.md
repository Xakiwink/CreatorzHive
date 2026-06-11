# AppConfig.php — Explained

**File:** `src/Config/AppConfig.php`
**Namespace:** `CreatorzHive\Config`

---

## Purpose

Typed configuration container. Reads app and database settings from environment variables at construction time. Registered in the DI container as a singleton so all classes that need config values get a consistent, type-safe interface.

---

## Hardcoded Values

| Property | Value | Notes |
|----------|-------|-------|
| `$name` | `'CreatorzHive'` | Not env-configurable |
| `$version` | `'1.0.0'` | Not env-configurable |
| `$timezone` | `'Africa/Dar_es_Salaam'` | Hardcoded to East African Time |
| `$uploadMaxSize` | `10485760` (10MB) | Hardcoded; admin panel has separate `max_upload_mb` setting |

## Environment-Mapped Values

| Method | Env Key | Default |
|--------|---------|---------|
| `url()` | `APP_URL` | `http://localhost/creatorzhive` |
| `env()` | `APP_ENV` | `development` |
| `isDebug()` | `APP_DEBUG` | `true` |
| `dbHost()` | `DB_HOST` | `127.0.0.1` |
| `dbPort()` | `DB_PORT` | `3306` |
| `dbDatabase()` | `DB_DATABASE` | `creatorz_hive` |
| `dbUsername()` | `DB_USERNAME` | `root` |
| `dbPassword()` | `DB_PASSWORD` | `''` |
| `appSecret()` | `APP_SECRET` | `''` |

---

## Usage

`AppConfig` is injected into `Connection` (for DB credentials). Other classes can get it from the container but most read env values directly via the `env()` global function.

---

## Notes

- `$allowedImageTypes` and `$allowedVideoTypes` duplicate what `MediaUploadHelper` defines. The `AppConfig` values aren't actively used by the upload flow, which reads from `MediaUploadHelper::mimeExtensions()` instead.

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Database/Connection.php` | Reads DB credentials from this |
| `src/Providers/AppServiceProvider.php` | Registers and wires this into DI |
| `backend/config/app.php` | Defines PHP constants (parallel system) |
