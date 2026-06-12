# app.js — Explained

**File:** `frontend/js/app.js`

---

## Purpose

The global application shell. Runs on every authenticated page. Loads shared UI components (sidebar, navbar, modal, toast), initializes the theme system, sidebar state, navigation highlighting, and the notification badge. Also exposes the `window.api()` function used by all page-specific JS files to make API calls.

---

## Global State Read

| Variable | Source | Description |
|----------|--------|-------------|
| `window.__BASE_PATH__` | PHP template | App URL path prefix (e.g. `'/creatorzhive'`) |
| `window.__USER__` | PHP template | Current session user (from API `/system/me`) |
| `window.__CSRF__` | PHP template | CSRF token for POST requests |

---

## Key Functions

### `api(routeName, method, data): Promise<object>`
The single API call function used by all other JS files:
1. Builds URL via `routeQuery(routeName, data)` → `/?route=<name>&...`
2. For non-GET: sends body as `application/x-www-form-urlencoded` with `_token` (CSRF)
3. For GET: data is appended as query params
4. Parses JSON response; throws if not valid JSON (includes hint for debugging)
5. Throws if `response.success === false`
6. Returns the full response object on success

### `routeQuery(route, extra): string`
Builds a URL with `?route=<name>` plus optional extra query params. Handles subdirectory installs via `apiPathPrefix()`.

### `assetPath(rel): string`
Prepends `__BASE_PATH__` to a relative path. Used for loading component HTML files.

---

## Theme System

- `syncThemeFromStorage()`: reads `localStorage.theme` → sets `<html data-theme="light|dark">`
- `toggleTheme()`: switches and saves preference
- Resolves `system` → actual light/dark via `window.matchMedia('(prefers-color-scheme: dark)')`
- Syncs across tabs via `storage` event listener
- Re-syncs on bfcache restore (`pageshow` event)

---

## Sidebar

- `initSidebarState()`: restores collapsed state from `localStorage.sidebarCollapsed`
- `toggleSidebar()`: on mobile (< 768px), opens/closes drawer; on desktop, toggles collapsed class
- `closeMobileDrawer()`: closes mobile drawer

---

## Role-Based Navigation

### `applyRoleNavigation()`
For admin users:
- Shows `.admin-only` elements
- Hides `.creator-only` elements
- Removes creator page nav items (planner, analytics, deals, invoices, media, dashboard, notifications)
- Redirects admin to settings if they land on a creator-only route
- Renames "Settings integrations" nav item to "API configuration"

---

## Notification Badge

### `refreshNotifBadge()`
Calls `api('notifications_count')` and updates `#notifBadge` with unread count. Shows `99+` for large counts.

---

## UI Components

| Class | Global | Description |
|-------|--------|-------------|
| `ModalClass` | `window.Modal` | Opens/closes the shared modal overlay |
| `ToastClass` | `window.Toast` | Shows transient 4-second toast notifications |

---

## Boot Sequence

```
boot()
  1. Load sidebar.html → #sidebar-container
  2. applyRoleNavigation() — remove wrong nav before paint
  3. hydrateSidebarUser() — fill in name/avatar
  4. Load navbar.html → #header-container
  5. Load modal.html → #modal-container
  6. Load toast.html → #toast-container
  7. initSidebarState()
  8. setActiveNav() — highlight current route in nav
  9. initEditorialTicker() — marquee strip sizing
  10. initTheme()
  11. initDropdown() — user menu
  12. initModalClose()
  13. initMobileMenu()
  14. initNotif() — load badge count
```

---

## Editorial Ticker

`initEditorialTicker()` manages the marketing/editorial marquee strip on the login page. Uses `ResizeObserver` to dynamically calculate how many segment copies are needed to fill the viewport, and adjusts the CSS animation duration proportionally.

---

## Related Files

| File | Relationship |
|------|-------------|
| `frontend/js/utils.js` | Loaded before this; `Utils.*` available |
| `frontend/components/sidebar.html` | Loaded by `boot()` |
| All page-specific JS files | Use `window.api()`, `window.Modal`, `window.Toast`, `window.routeQuery` |
