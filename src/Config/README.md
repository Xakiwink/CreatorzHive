# `src/Config/` — Application Configuration

## 1. Folder Purpose

Typed configuration classes that read environment variables and expose them as typed properties. Provides a single, testable source of truth for app settings.

## 2. Files Overview

| File | Purpose | Used By |
|------|---------|---------|
| `AppConfig.php` | Core app settings (env, debug, timezone, upload limits) | `AppServiceProvider`, `Application` |

## 3. Design Notes

- `AppConfig` is bound as a singleton in `AppServiceProvider`
- All values sourced from environment variables with typed defaults
- Mirrors `backend/config/app.php` PHP constants — both exist for backwards compatibility during the OOP migration
- Prefer `AppConfig` over PHP constants in new OOP code

## 4. Key Properties

| Property | Env Var | Default |
|----------|---------|---------|
| `env` | `APP_ENV` | `production` |
| `debug` | `APP_DEBUG` | `false` |
| `timezone` | `APP_TIMEZONE` | `Africa/Dar_es_Salaam` |
| `uploadMaxSize` | `UPLOAD_MAX_SIZE` | 10 MB |
| `allowedImageTypes` | `ALLOWED_IMAGE_TYPES` | jpeg, png, gif, webp |
| `allowedVideoTypes` | `ALLOWED_VIDEO_TYPES` | mp4, mov, avi |
