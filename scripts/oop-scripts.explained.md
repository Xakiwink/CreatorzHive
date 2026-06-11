# OOP Migration Scripts — Explained

**Files:**
- `scripts/oop-migrate-backend.php`
- `scripts/oop-add-use-function.php`
- `scripts/oop-qualify-global-calls.php`
- `scripts/oop-generate-services-compat.php`
- `scripts/oop-fix-internal-calls.php`
- `scripts/oop-fix-controller-methods.php`
- `scripts/oop-generate-routes.php`
- `scripts/oop-generate-compat.php`

---

## Purpose

These are **one-time code generation tools** used during the OOP migration of CreatorzHive. They are not part of normal runtime or maintenance workflows. They remain in the repo as reference and in case a re-generation is ever needed.

---

## What They Did

| Script | Purpose |
|--------|---------|
| `oop-migrate-backend.php` | Scaffold OOP repositories/services/controllers from procedural backend files |
| `oop-generate-compat.php` | Generated `backend/compat/models.php` — the global→repository bridge |
| `oop-generate-services-compat.php` | Generated `backend/compat/services.php` — the global→service bridge |
| `oop-generate-routes.php` | Generated OOP route definitions from procedural route registrations |
| `oop-qualify-global-calls.php` | Added `\function_exists()` / `\is_array()` etc. global qualifications to namespaced files |
| `oop-add-use-function.php` | Inserted `use function` declarations into namespaced files |
| `oop-fix-internal-calls.php` | Fixed `$this->method()` vs `self::method()` mismatches after generation |
| `oop-fix-controller-methods.php` | Fixed generated controller method signatures after scaffolding |

---

## Current Status

The OOP migration is **complete**. These scripts are frozen — they are not run as part of any ongoing workflow. The compat bridge files they generated (`backend/compat/models.php`, `backend/compat/services.php`) are now maintained manually.

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/compat/models.php` | Output of `oop-generate-compat.php` |
| `backend/compat/services.php` | Output of `oop-generate-services-compat.php` |
| `src/` | The OOP layer these scripts helped create |
