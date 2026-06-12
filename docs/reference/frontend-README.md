# `frontend/` — User Interface

## 1. Folder Purpose

HTML/CSS/JS and PHP page templates for the web UI. No build step; assets served from `public/` or referenced via `asset_url()`.

## 2. Structure

| Path | Purpose |
|------|---------|
| `pages/` | PHP/HTML views per feature (auth, dashboard, planner, …) |
| `js/` | Client logic (`auth.js`, dashboard modules) |
| `css/` | Styles including `auth.css`, `dark-mode.css` |
| `components/` | Reusable HTML fragments |
| `fonts/`, `assets/` | Self-hosted fonts and Chart.js |

## 3. Frontend ↔ backend

- Pages load data via `fetch('?route=dashboard_data')` with session cookie + CSRF.
- Auth forms POST to API routes (`login`, `register`).
- Google sign-in: link to `?route=google-auth` (full redirect flow).

## 4. Related

- [pages/auth/README.md](pages/auth/README.md)

## 5. Improvement suggestions

- Shared JS module for `routeQueryUrl` + error handling.
- Component includes for nav/sidebar to reduce duplication.
