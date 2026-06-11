# `backend/config/` — Procedural Configuration

## 1. Folder Purpose

Defines PHP constants used by the procedural layer and loads the database connection. These files are required early in the bootstrap sequence.

## 2. Files Overview

| File | Purpose |
|------|---------|
| `app.php` | Defines `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`, `UPLOAD_MAX_SIZE`, `ALLOWED_IMAGE_TYPES`, `ALLOWED_VIDEO_TYPES` |
| `database.php` | Thin shim — `require_once`s `backend/core/database.php` |

## 3. Design Notes

- All constants in `app.php` are read from environment variables via `env()` with typed defaults
- `database.php` exists for historical reasons (some projects split DB config from core DB functions) — it adds no logic
- The OOP equivalent is `src/Config/AppConfig.php` — prefer that in new OOP code

## 4. Constants Defined

| Constant | Env Var | Default |
|----------|---------|---------|
| `APP_NAME` | `APP_NAME` | `CreatorzHive` |
| `APP_URL` | `APP_URL` | `http://localhost` |
| `APP_ENV` | `APP_ENV` | `production` |
| `APP_DEBUG` | `APP_DEBUG` | `false` |
| `APP_TIMEZONE` | `APP_TIMEZONE` | `Africa/Dar_es_Salaam` |
| `UPLOAD_MAX_SIZE` | `UPLOAD_MAX_SIZE` | 10485760 (10 MB) |
| `ALLOWED_IMAGE_TYPES` | `ALLOWED_IMAGE_TYPES` | `jpeg,png,gif,webp` |
| `ALLOWED_VIDEO_TYPES` | `ALLOWED_VIDEO_TYPES` | `mp4,mov,avi` |
