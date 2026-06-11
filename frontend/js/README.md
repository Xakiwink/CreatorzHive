# `frontend/js/` — Client-Side JavaScript

## 1. Folder Purpose

Vanilla JavaScript modules for each page/feature. No build step, no bundler — each file is loaded directly via `<script>` tags in the corresponding PHP page template.

## 2. Files Overview

| File | Page / Purpose |
|------|----------------|
| `utils.js` | `window.Utils` — shared utilities: formatDate, formatCurrency, timeAgo, debounce, escapeHtml, etc. |
| `app.js` | App shell — `window.api()`, routing, nav, theme, notification badge, component loading |
| `auth.js` | Login, register, forgot password, verify email pages |
| `dashboard.js` | Dashboard page — stat cards, recent/upcoming posts, platform status, donut chart |
| `planner.js` | Content planner — calendar + list views, post composer, bulk ops, tag management |
| `analytics.js` | Analytics page — Chart.js charts, period/platform filters, demo seed |
| `deals.js` | Deals Kanban — drag-and-drop, deal drawer, activity log, revenue summary |
| `invoices.js` | Invoices page — status filter, dynamic line items, overdue display |
| `media.js` | `window.Media` utility library — upload, drop zone, media grid, delete |
| `media-library.js` | Media library page — renders grid using `window.Media`, pagination, copy URL |
| `notifications.js` | Notifications page — tabs, load-more, mark-read, bulk actions |
| `settings.js` | Settings page — 5 panels, avatar upload, theme sync, OAuth connections |
| `admin-users.js` | Admin page — user table, audit log, integration status, summary cards |
| `admin-platform-credentials.js` | Admin credential forms — rendered inside the admin page |

## 3. Global Namespace

Key globals set by `app.js` and `utils.js`, consumed by all page scripts:

| Global | Set By | Purpose |
|--------|--------|---------|
| `window.api(route, opts)` | `app.js` | Authenticated fetch with CSRF |
| `window.Utils` | `utils.js` | Formatting and utility functions |
| `window.Modal` | `app.js` | Modal open/close |
| `window.Toast` | `app.js` | Toast notifications |
| `window.Media` | `media.js` | File upload and media grid helpers |
| `window.__BASE_PATH__` | PHP template | Base URL for routing |
| `window.__USER__` | PHP template | Authenticated user object |
| `window.__CSRF__` | PHP template | CSRF token |

## 4. Design Notes

- `auth.js` uses ES5 syntax (no arrow functions) for compatibility with older mobile browsers
- All other files use modern JS (arrow functions, `async/await`, template literals)
- Page scripts are self-contained — they initialize on `DOMContentLoaded` or equivalent
- `media.js` is a library, not a page script; it does not attach to DOM on load

## 5. Improvement suggestions

- Move to ES modules (`<script type="module">`) to eliminate global namespace pollution
- Bundle with esbuild or Vite for tree-shaking and cache-busting
