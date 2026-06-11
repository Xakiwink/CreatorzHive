# SystemController.php — Explained

**File:** `src/Controllers/SystemController.php`
**Namespace:** `CreatorzHive\Controllers`
**Extends:** `AbstractController`

---

## Purpose

Two diagnostic/health-check endpoints with no business logic. Minimal controller with no extra dependencies beyond what `AbstractController` provides.

---

## Methods

### `ping()` — GET api/ping

Health check. Returns app name, version, environment, and current UTC timestamp. No auth required.

```json
{
  "ok": true,
  "app": "CreatorzHive",
  "version": "1.0.0",
  "environment": "development",
  "time": "2026-06-10T12:00:00+00:00"
}
```

**Use:** Load balancer health checks, uptime monitors, deployment verification.

### `dbTest()` — GET api/db-test

Runs `SELECT VERSION()` and returns MySQL version string. Returns 500 with error message if DB connection fails. Intended for admin use only (no auth middleware, but in practice called from admin tools).

---

## Notes

- Despite no explicit auth middleware on `db-test`, this endpoint is documented as admin-only in `api.explained.md`
- Both routes listed under "System Routes (no auth)" in `backend/routes/api.php` — consider adding auth to `db-test` in production

---

## Related Files

| File | Relationship |
|------|-------------|
| `backend/routes/api.php` | Route definitions |
