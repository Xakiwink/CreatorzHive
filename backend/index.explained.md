# index.php — Explained

**File:** `backend/index.php`

---

## Purpose

The application entry point for all HTTP requests. Loaded by `public/index.php`. Boots the entire stack, sets security headers, and dispatches the request to the router.

---

## Execution Flow

```
backend/index.php
  1. require helpers/functions.php          ← load_env(), base_path(), storage_path()
  2. load_env('.env')                        ← populate $_ENV from .env file
  3. require config/app.php                  ← define APP_NAME, APP_URL, APP_ENV, etc.
  4. date_default_timezone_set('Africa/Dar_es_Salaam')
  5. Emit security headers (non-PHPUnit only):
       X-Content-Type-Options: nosniff
       X-Frame-Options: SAMEORIGIN
       X-XSS-Protection: 1; mode=block
       Referrer-Policy: strict-origin-when-cross-origin
  6. require vendor/autoload.php (if present)
  7. require bootstrap-oop.php               ← Application::boot() → DI container
  8. require bootstrap-procedural.php        ← all procedural functions + compat bridges
  9. error_handler_register()               ← PHP error + exception handler
  10. session_start_safe()                   ← httponly, samesite=Strict session
  11. csrf_generate_token()                  ← generate CSRF token once per session
  12. router_reset()                         ← clear any pre-registered routes
  13. api_cors_handle_preflight()            ← respond to OPTIONS requests and exit
  14. require routes/web.php                 ← HTML page routes
  15. router_api_mode(true)
  16. require routes/api.php                 ← JSON API routes (85+)
  17. router_dispatch()                      ← match current request and call handler
```

---

## Security Headers

Headers are suppressed in PHPUnit mode (`CREATORZHIVE_PHPUNIT`) to avoid "headers already sent" errors in tests.

---

## Router Mode

`router_api_mode(true)` is set before loading `routes/api.php`. This causes unmatched routes to return a 404 JSON response instead of an HTML 404 page.

---

## Related Files

| File | Relationship |
|------|-------------|
| `public/index.php` | Calls this file |
| `backend/bootstrap-oop.php` | Boots OOP layer |
| `backend/bootstrap-procedural.php` | Boots procedural layer |
| `backend/routes/web.php` | Web (HTML) routes |
| `backend/routes/api.php` | API (JSON) routes |
| `backend/core/router.php` | Router dispatch |
