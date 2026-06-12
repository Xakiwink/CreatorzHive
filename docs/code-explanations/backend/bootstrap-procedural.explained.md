# bootstrap-procedural.php — Explained

**File:** `backend/bootstrap-procedural.php`

---

## Purpose

Loads every procedural PHP file in the correct dependency order. This is the legacy stack that bridges the OOP layer (`src/`) with the router, middleware, and helper functions. Called from `backend/index.php` after `bootstrap-oop.php`.

---

## Load Order

```
backend/bootstrap-procedural.php
  ├── backend/config/app.php          ← APP_NAME, APP_URL, APP_ENV, UPLOAD_MAX_SIZE constants
  ├── backend/compat/models.php       ← Procedural wrappers → OOP Repositories
  ├── backend/compat/services.php     ← Procedural wrappers → OOP Services
  ├── backend/compat/auth.php         ← Procedural wrappers → AuthService
  ├── backend/helpers/platforms.php   ← platform_slugs_list(), platform_normalize_slug()
  ├── backend/core/database.php       ← db_get_pdo(), db_query(), db_insert(), etc.
  ├── backend/core/session.php        ← session_start_safe(), session fingerprinting
  ├── backend/core/response.php       ← response_json(), response_html(), response_redirect()
  ├── backend/helpers/api_cors.php    ← CORS header emission
  ├── backend/core/request.php        ← request_get(), request_post(), request_json_body()
  ├── backend/core/validator.php      ← validator_validate()
  ├── backend/core/token_crypto.php   ← token_crypto_encrypt_db(), token_crypto_decrypt_db()
  ├── backend/core/error_handler.php  ← error_handler_register(), 404/500 handlers
  ├── backend/core/mailer.php         ← mailer_send(), email templates
  ├── backend/middleware/csrf.php     ← csrf_generate_token(), csrf_validate_post()
  ├── backend/middleware/auth.php     ← auth_middleware_handle()
  ├── backend/middleware/role.php     ← role_middleware_require()
  ├── backend/http.php                ← http_json(), http_view(), http_redirect()
  ├── backend/core/router.php         ← router_get(), router_post(), router_dispatch()
  └── backend/core/job_runner.php     ← job_runner_run_next()
```

---

## Notes

- The compat bridge files (`models.php`, `services.php`, `auth.php`) must load after `bootstrap-oop.php` (in `index.php`) since they call `Application::instance()`.
- `config/app.php` is loaded here again even though `index.php` already loaded it — `require_once` prevents double execution.
- `job_runner.php` is loaded last; it defines the job execution engine but does not run any jobs on load.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/index.php` | Calls this file |
| `backend/bootstrap-oop.php` | Must run before this (loaded first in index.php) |
| `backend/compat/models.php` | First compat bridge loaded |
