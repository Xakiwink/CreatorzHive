# Integration Tests — Explained

**Files:**
- `tests/integration/AuthControllerTest.php`
- `tests/integration/PostControllerTest.php`
- `tests/integration/DealControllerTest.php`
- `tests/integration/InvoiceControllerTest.php`
- `tests/integration/MediaControllerTest.php`
- `tests/integration/NotificationControllerTest.php`
- `tests/integration/SettingsControllerTest.php`
- `tests/integration/AdminUserControllerTest.php`
- `tests/integration/AnalyticsControllerTest.php`
- `tests/integration/ApiMetaControllerTest.php`
- `tests/integration/TagControllerTest.php`

---

## Overview

Full HTTP integration tests. Each test dispatches a real route through the router, using `dispatchRoute()` from `IntegrationTestCase`. Requires a live database (seeded with `david@creatorzhive.com` / `Creator@1234` and `admin@creatorzhive.com`).

---

## Seed User Convention

Most tests log in as seeded users before testing authenticated routes:

| User | Email | Password | Role |
|------|-------|----------|------|
| David (creator) | `david@creatorzhive.com` | `Creator@1234` | creator |
| Admin | `admin@creatorzhive.com` | *(varies)* | admin |

Login is done by calling `dispatchRoute('POST', 'login', [...])` which sets session state.

---

## Test Files

### `AuthControllerTest`
- Register with valid data → 200
- Register with duplicate email → 422
- Register with weak password → 422
- Login with correct credentials → 200
- Login with wrong password → 401
- Rate limiting triggers after multiple failed logins

### `PostControllerTest`
- Create post as authenticated user → 200
- Create post as unauthenticated user → 401
- Load planner list → includes created post
- Update/delete post cleanup after each test (`db_delete` to prevent pollution)

### `DealControllerTest`
- Create deal → 200
- Get Kanban → returns all 6 status columns
- Update status → status changes in DB
- Delete deal → removed from Kanban

### `InvoiceControllerTest`
- Create invoice with line items → 200
- Generated invoice number format `INV-{YEAR}-{NNNN}`
- Mark as paid → status changes

### `MediaControllerTest`
- Upload file (multipart) → 200 with file URL
- Delete media → removes file and DB record
- Access other user's media → 403

### `NotificationControllerTest`
- Load notifications → returns paginated list
- Mark single as read → `is_read = 1`
- Mark all as read → all notifications read
- Delete read → removed from list

### `SettingsControllerTest`
- Update profile → name/bio changes
- Change password → new password accepted
- Avatar upload → resized to 200×200

### `AdminUserControllerTest`
- Creator cannot access admin routes → 403
- Admin can list users → 200
- Admin can update user fields inline

### `AnalyticsControllerTest`
- Load analytics report → 200 with chart data
- Period filter (7d/30d/90d) → changes data range

### `ApiMetaControllerTest`
- `api_me` → returns authenticated user + CSRF
- `api_catalog` → returns route list filtered by role

### `TagControllerTest`
- Create tag → 200
- List tags → includes created tag
- Idempotent store (same name → same tag ID)

---

## Test Cleanup

Tests that create DB rows clean up in the same test method using `db_delete()`. Tests do not use database transactions + rollback — each test commits real data and removes it explicitly.

---

## Related Files

| File | Relationship |
|------|-------------|
| `tests/Support/IntegrationTestCase.php` | Base class providing `dispatchRoute()` |
| `tests/bootstrap.php` | Boots app + sets `CREATORZHIVE_PHPUNIT` constant |
| `database/seeds/` | Must be applied before running integration tests |
