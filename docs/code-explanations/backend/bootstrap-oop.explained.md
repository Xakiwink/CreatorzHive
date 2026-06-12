# bootstrap-oop.php — Explained

**File:** `backend/bootstrap-oop.php`

---

## Purpose

Loads Composer autoloader and boots the OOP application layer. Called from `backend/index.php` before the procedural bootstrap.

---

## What It Does

1. Resolves path to `vendor/autoload.php`
2. Returns silently if autoload file missing (graceful degradation — system still runs in procedural-only mode)
3. Calls `Application::boot()` if the class exists

`Application::boot()` creates the DI Container, registers all bindings via `AppServiceProvider`, and stores the container in `$GLOBALS['cz_container']`.

---

## Execution Order

```
backend/index.php
  ├── load_env()
  ├── require backend/bootstrap-oop.php     ← HERE
  │   ├── require vendor/autoload.php
  │   └── Application::boot()
  │       └── AppServiceProvider::register()
  └── require backend/bootstrap-procedural.php
```

---

## Related Files

| File | Relationship |
|------|-------------|
| `src/Core/Application.php` | Booted here |
| `backend/index.php` | Calls this file |
| `vendor/autoload.php` | Required here |
