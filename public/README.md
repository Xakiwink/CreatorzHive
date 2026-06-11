# `public/` — Web Document Root

## 1. Folder Purpose

Only directory that should be exposed to the internet. Front controller and public uploads.

## 2. Files

| Path | Purpose |
|------|---------|
| `index.php` | Requires `backend/index.php` |
| `.htaccess` | Rewrite to index.php |
| `uploads/` | User media and avatars (writable) |
| `assets/` | Copied/symlinked static assets if used |

## 3. Security

- Do not move `backend/` or `.env` under `public/`.
- Restrict execution in `uploads/` (no PHP).

## 4. Improvement suggestions

- CDN in front of `uploads/` for production.
- Separate subdomain for static assets.
