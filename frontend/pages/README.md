# `frontend/pages/` — PHP View Templates

## 1. Folder Purpose

PHP template files that produce HTML for each page. Rendered by `ViewRenderer::render()` (OOP) or `http_view()` (procedural), which passes controller-provided variables into the template scope.

## 2. Structure

```
pages/
├── auth/           Login, register, forgot-password, reset-password, verify-email
├── dashboard/      Main dashboard (index.php)
├── planner/        Content planner (index.php)
├── analytics/      Analytics (index.php)
├── monetization/   Deals (deals.php), Invoices (invoices.php, index.php)
├── media/          Media library (index.php)
├── notifications/  Notifications (notifications.php)
├── settings/       Settings (index.php, profile.php, admin-users.php)
├── errors/         Error pages (403-admin.php)
└── partials/       Shared PHP partials (app_script_globals.php)
```

## 3. Key Partial: `partials/app_script_globals.php`

Injects PHP data into `window.*` JavaScript globals:
- `window.__BASE_PATH__` — resolved app URL
- `window.__USER__` — authenticated user object (JSON)
- `window.__CSRF__` — CSRF token for API requests
- `window.__ALLOW_SEED__` — whether analytics demo seed is enabled

Included in page templates that need JS API access.

## 4. Template Variables

Templates receive variables set by the controller before calling `$renderer->render('path/to/template', $data)`. Variable names are not typed — check the corresponding controller method for what each template receives.

## 5. Auth Pages (`auth/`)

See [`auth/README.md`](auth/README.md) for details on the auth flow.

## 6. Improvement suggestions

- Introduce a base layout template to eliminate nav/sidebar duplication
- Add typed view data objects to replace raw associative arrays
