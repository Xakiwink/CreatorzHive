# `backend/helpers/` — Global Helper Functions

## 1. Folder Purpose

Supplementary global functions loaded late in the bootstrap sequence. Unlike `backend/core/`, these helpers are either cross-cutting utilities, API-specific setup, or CLI bootstrapping.

## 2. Files Overview

| File | Global Functions | Purpose |
|------|-----------------|---------|
| `functions.php` | `env`, `base_path`, `public_path`, `storage_path`, `config`, `encrypt`, `decrypt`, `now`, `str_slug`, etc. | General-purpose utility functions |
| `api_cors.php` | `api_cors_setup`, `api_cors_handle_preflight` | CORS headers + preflight 204 response |
| `platforms.php` | `platform_slugs`, `platform_normalize` | Bridge to `PlatformHelper` OOP class |
| `cli_bootstrap.php` | `cli_load_env`, `cli_boot_oop`, `cli_pdo` | CLI entry point helpers |

## 3. Design Notes

- `functions.php` is the equivalent of Laravel's `helpers.php` — loaded for every request
- `api_cors_handle_preflight()` exits with 204 before any routing; must be called at the start of `index.php`
- `cli_bootstrap.php` has a static `$booted` guard so `cli_boot_oop()` is idempotent
- `platforms.php` functions delegate directly to `PlatformHelper::slugs()` and `PlatformHelper::normalize()`

## 4. Improvement suggestions

- Move `env()` and path helpers into `AppConfig` or a dedicated `Path` utility class
- `api_cors.php` could be replaced by `CorsMiddleware` in the OOP router when that becomes active
