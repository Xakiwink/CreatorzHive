# `backend/core/` — Procedural Core Functions

## 1. Folder Purpose

The procedural foundation of the application. Each file exposes a set of global functions that handle a specific concern. These functions are loaded by `backend/bootstrap-procedural.php` and used directly by routes and compat bridges.

## 2. Files Overview

| File | Global Functions | Purpose |
|------|-----------------|---------|
| `database.php` | `db_fetch`, `db_fetch_all`, `db_insert`, `db_update`, `db_paginate`, etc. | PDO wrapper with connection caching |
| `router.php` | `router_register`, `router_dispatch`, `router_resolve_route`, etc. | Route registry and HTTP dispatch |
| `session.php` | `session_start_secure`, `session_get`, `session_set`, `session_destroy` | Fingerprinted secure sessions |
| `request.php` | `request_input`, `request_json`, `request_file`, `request_ip` | Request abstraction |
| `response.php` | `response_json`, `response_redirect`, `response_view`, `response_abort` | Response helpers |
| `validator.php` | `validate`, `validate_rules` | Input validation with rule DSL |
| `mailer.php` | `mailer_send`, `mailer_render_template` | PHPMailer wrapper with `{{var}}` templates |
| `error_handler.php` | `error_handler_register`, `error_handler_wants_json` | PHP error → exception + 500 handler |
| `token_crypto.php` | `token_crypto_encrypt_db`, `token_crypto_decrypt_db` | Procedural bridge to `TokenCrypto` |
| `job_runner.php` | `job_runner_dispatch`, `job_runner_run`, `job_runner_*` | Job queue: dispatch, run, maintenance |

## 3. Design Notes

- All functions are global (no namespacing)
- `database.php` dual-paths: uses `Core\Database\Connection` if OOP is booted, otherwise creates a direct PDO
- `router.php` stores routes in `$GLOBALS['cz_router_routes']`
- `session.php` uses SHA-256 fingerprinting (IP subnet + lowercased UA)
- `error_handler.php` redacts `password=`, `token=`, `secret=` from error logs

## 4. Improvement suggestions

- Replace global functions with namespaced functions or static class methods as the OOP migration completes
- `job_runner.php` could be moved to `src/` once all cron callers are updated
