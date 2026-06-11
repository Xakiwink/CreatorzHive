# App Page Templates — Explained

**Files:**
- `frontend/pages/dashboard/index.php`
- `frontend/pages/planner/index.php`
- `frontend/pages/analytics/index.php`
- `frontend/pages/monetization/index.php` (deals redirect/hub)
- `frontend/pages/monetization/deals.php`
- `frontend/pages/monetization/invoices.php`
- `frontend/pages/media/index.php`
- `frontend/pages/notifications/notifications.php`
- `frontend/pages/settings/profile.php` (also `settings/index.php` — stub that requires profile.php)
- `frontend/pages/settings/admin-users.php`
- `frontend/pages/errors/403-admin.php`

---

## Common Structure

All authenticated app pages follow the same shell pattern:

```php
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <!-- CSS links using asset_url() -->
</head>
<body>
<div class="app-shell">
  <div id="sidebar-container"></div>      <!-- loaded by app.js -->
  <div class="main-content">
    <div id="header-container"></div>     <!-- loaded by app.js -->
    <main class="page-content">
      <!-- HTML fragment or inline HTML -->
    </main>
  </div>
</div>
<div id="modal-container"></div>
<div id="toast-container"></div>
<?php require 'partials/app_script_globals.php'; ?>  <!-- window.__USER__, __CSRF__, __BASE_PATH__ -->
<script src="utils.js"></script>
<script src="app.js"></script>
<script src="page-specific.js"></script>
</body>
```

The sidebar, header, and modal/toast containers are all populated dynamically by `app.js` on load.

---

## HTML Fragment Pattern

Most page templates do not contain their own HTML body — instead they load a static `.html` file:

```php
echo file_get_contents(__DIR__ . '/dashboard.html');
```

Some templates do string replacement on route URLs before echoing:

```php
echo str_replace(
    '__CHIVE_ROUTE_PLANNER__',
    htmlspecialchars(route_url('planner'), ENT_QUOTES, 'UTF-8'),
    $fragment
);
```

This allows `.html` files to contain `__CHIVE_ROUTE_*` placeholders that are replaced at render time with correct URLs.

---

## Page-Specific Notes

### `dashboard/index.php`
- Loads `dashboard.html` with `__CHIVE_ROUTE_PLANNER__` replacement
- Scripts: `utils.js`, `app.js`, `chart.umd.min.js`, `dashboard.js`

### `planner/index.php`
- Loads `planner.html` directly (no placeholder replacement needed)
- Scripts: `utils.js`, `app.js`, `chart.umd.min.js`, `media.js`, `planner.js`

### `analytics/index.php`
- Loads `analytics.html` with `__CHIVE_ROUTE_SETTINGS__` replacement
- Injects `window.__APP_ENV__` (used by `analytics.js` to show/hide the demo seed button)
- Scripts: `utils.js`, `app.js`, `chart.umd.min.js`, `analytics.js`

### `monetization/deals.php`
- Loads `deals.html` fragment
- Scripts: `utils.js`, `app.js`, `deals.js`

### `monetization/invoices.php`
- Loads `invoices.html` fragment
- Scripts: `utils.js`, `app.js`, `invoices.js`

### `media/index.php`
- Loads `media.html` fragment
- Scripts: `utils.js`, `app.js`, `media.js`, `media-library.js`

### `notifications/notifications.php`
- Loads `notifications.html` fragment
- Scripts: `utils.js`, `app.js`, `notifications.js`

### `settings/profile.php` (also `settings/index.php`)
- Inline HTML (no fragment file) — the full settings form is in the PHP template
- Checks `session_user_is_admin()` to conditionally show admin-only UI sections
- Scripts: `utils.js`, `app.js`, `settings.js`

### `settings/admin-users.php`
- Admin-only page
- Scripts: `utils.js`, `app.js`, `admin-users.js`, `admin-platform-credentials.js`

### `errors/403-admin.php`
- Shown when an admin attempts to access a creator-only page (e.g. dashboard, analytics)
- Static error page — no JavaScript required

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/pages/partials/app_script_globals.php` | Injects `window.__USER__`, `__CSRF__`, `__BASE_PATH__` |
| `frontend/js/app.js` | Loads sidebar, header, components on boot |
| `src/Core/Http/ViewRenderer.php` | Renders these PHP templates |
| `backend/http.php` | `http_view()` procedural equivalent |
