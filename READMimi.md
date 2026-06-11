# CreatorzHive — Claude Code Build Guide

> A web-based content management platform for social media influencers.
> Built with PHP 8.1, MySQL 8.0, HTML/CSS/Vanilla JS.

---

## How to Use These Prompts

Each `.md` file in this folder is a **self-contained Claude Code prompt** for one phase of the build.
Feed them to Claude Code **in order** — each phase assumes the previous one is complete.

---

## Build Phases

| File | Phase | What Gets Built |
|------|-------|-----------------|
| `schema.sql` | — | Fixed, production-ready database schema (run this first manually) |
| `phase-00-scaffold.md` | 0 | Project structure, config files, `.env`, Composer setup |
| `phase-01-core-framework.md` | 1 | Router, Controller, Database, Session, Validator, Middleware |
| `phase-02-authentication.md` | 2 | Register, Login, Logout, Email Verify, Password Reset (backend + frontend) |
| `phase-03-ui-layout.md` | 3 | Design system, CSS, Sidebar, Header, Toast, Modal, Dark mode |
| `phase-04-dashboard.md` | 4 | KPI cards, Recent posts, Upcoming posts, Quick-post modal, Chart.js donut |
| `phase-05-content-planner.md` | 5 | Calendar view, List view, Post create/edit/delete, Media upload, Tags |
| `phase-06-analytics.md` | 6 | Analytics charts (follower growth, engagement, posting frequency), seed data |
| `phase-07-deals-monetization.md` | 7 | Kanban deal pipeline, Deal CRUD, Invoices, Revenue summary |
| `phase-08-notifications-settings.md` | 8 | Notification inbox, Bell badge, Profile/Security/Integrations/Preferences settings |
| `phase-09-background-jobs.md` | 9 | Job queue engine, PublishPostJob, FetchAnalyticsJob, CleanupJob, Cron script |
| `phase-10-testing-polish.md` | 10 | PHPUnit tests, full seed data, security hardening, landing page, deployment docs |

---

## Quick Start

```bash
# 1. Create the database
mysql -u root -p -e "CREATE DATABASE creatorz_hive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Run the schema
mysql -u root -p creatorz_hive < schema.sql

# 3. Feed phase-00 to Claude Code
# (Then continue phase by phase)

# 4. After all phases, seed demo data
php scripts/seed.php --fresh

# 5. Set up cron
* * * * * php /path/to/creatorz-hive/scripts/cron.php

# 6. Visit http://localhost
# Login: david@creatorzhive.com / Creator@1234
```

---

## Demo Credentials (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Creator | david@creatorzhive.com | Creator@1234 |
| Admin | admin@creatorzhive.com | Admin@1234 |
| Brand | brand@creatorzhive.com | Brand@1234 |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.1 (MVC, no framework) |
| Database | MySQL 8.0 |
| Frontend | HTML5 + CSS3 + Vanilla JavaScript |
| Charts | Chart.js 4.x (CDN) |
| Email | PHPMailer (Composer) |
| Icons | Heroicons (inline SVG) |
| Fonts | Poppins + Inter (Google Fonts) |

---

## Design System

| Token | Value |
|-------|-------|
| Primary | `#1A1F36` |
| Accent | `#6C5CE7` |
| Secondary | `#00C9A7` |
| Background | `#F8F9FC` |
| Danger | `#EF4444` |
| Success | `#10B981` |
| Font (headings) | Poppins |
| Font (body) | Inter |

---

## Project Team

- David T. Mposo
- Daines O. Myoka
- Abiba H. Rubanga

Supervisor: Mr. Nurdin Mleli
Institution: College of Business Education (CBE) — Department of ICT
Academic Year: 2025/2026
