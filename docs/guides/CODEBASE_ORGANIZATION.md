# CreatorzHive Codebase Organization Guide

This guide explains how the CreatorzHive codebase is organized and where to find different types of files.

---

## 📂 Directory Structure

### Root Level (`/creatorzhive/`)

```
creatorzhive/
├── src/                          # OOP business logic (production code)
├── backend/                       # Procedural legacy compatibility layer
├── frontend/                      # JavaScript modules and UI
├── public/                        # Web-accessible files (entry point)
├── database/                      # Schema and migrations
├── scripts/                       # CLI commands
├── tests/                         # Test suite
├── vendor/                        # Composer dependencies
├── docs/                          # All documentation
├── config/                        # Configuration files
├── .env.example                   # Environment template
├── composer.json                  # PHP dependencies
├── phpunit.xml                    # Test configuration
└── [deployment files]             # See below
```

---

## 📚 Documentation Organization

### Deployment & Operations (Root Level)

These critical deployment files stay at the project root:

| File | Purpose |
|------|---------|
| **[INFINITYFREE_SETUP.md](INFINITYFREE_SETUP.md)** | 🚀 Step-by-step InfinityFree deployment guide |
| **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** | 🌐 Multi-platform deployment (local, shared hosting, VPS) |
| **[FINAL_DEPLOYMENT_AUDIT.md](FINAL_DEPLOYMENT_AUDIT.md)** | ✅ Phase 1 completion checklist |

### Complete Documentation (`/docs/`)

#### Code Explanations ([docs/code-explanations/](docs/code-explanations/))

**124 detailed explanations of every major code file** — Organized by component type:

- `backend/` — 27 files covering bootstrap, routing, middleware
- `frontend/` — 17 files covering JavaScript modules
- `scripts/` — 9 files covering CLI utilities
- `src/` — 66 files covering controllers, services, repositories
- `tests/` — 5 files covering test infrastructure

👉 **Start here**: [docs/code-explanations/INDEX.md](docs/code-explanations/INDEX.md)

#### System Documentation

- **[docs/DOCUMENTATION_INDEX.md](docs/DOCUMENTATION_INDEX.md)** — Master index of all docs
- **[docs/MASTER_PROJECT_GUIDE.md](docs/MASTER_PROJECT_GUIDE.md)** — Complete project reference
- **[docs/system-analysis.md](docs/system-analysis.md)** — Architecture, features, design
- **[docs/infinityfree-compatibility-report.md](docs/infinityfree-compatibility-report.md)** — Shared hosting compatibility

#### Knowledge Base ([docs/knowledge-base/](docs/knowledge-base/))

Quick guides for developers:

- `deployment-guide.md` — How to deploy
- `feature-map.md` — Features by module
- `integration-map.md` — External APIs
- `workflow-map.md` — User workflows
- `glossary.md` — Terminology
- `troubleshooting-guide.md` — Debugging

#### Analysis & Architecture ([docs/](docs/))

Deep technical analysis:

- `architecture/` — Project structure and dependency maps
- `database/` — Schema analysis and ER diagrams
- `apis/` — API endpoint analysis
- `business/` — Business logic analysis
- `code-quality/` — Code review findings
- `security/` — Security audit
- `roadmap/` — Feature roadmap

---

## 🏗️ Codebase Structure

### Source Code (`/src/`)

```
src/
├── Controllers/         (10+ files) - HTTP request handlers
├── Services/            (10+ files) - Business logic
├── Repositories/        (15+ files) - Database access
├── Middleware/          (4 files)   - Request middleware
├── Jobs/                (4 files)   - Background jobs
├── Core/                (6+ files)  - Core utilities
├── Support/             (8+ files)  - Helper classes
└── Providers/           (2 files)   - Service provider
```

**Each folder has a README.md** explaining its purpose.

### Legacy Layer (`/backend/`)

```
backend/
├── index.php                      - Entry point with APP_SECRET enforcement
├── bootstrap-oop.php              - OOP dependency injection setup
├── bootstrap-procedural.php       - Legacy compatibility setup
├── http.php                       - HTTP abstraction
├── middleware/                    - Middleware classes
├── routes/                        - Web & API routes
├── compat/                        - Compatibility bridges
├── core/                          - Core utilities
└── storage/                       - Logs, cache, sessions
```

### Frontend (`/frontend/`)

```
frontend/
├── js/                   - JavaScript modules
│   ├── app.js           - Main app entry
│   ├── dashboard.js     - Dashboard logic
│   ├── planner.js       - Post planner
│   ├── analytics.js     - Analytics viewer
│   ├── media.js         - Media upload
│   ├── settings.js      - Settings UI
│   └── [more modules]
├── pages/                - HTML templates
├── css/                  - Stylesheets
└── assets/               - Images, icons
```

### Database (`/database/`)

```
database/
├── schema.sql           - Complete schema definition
├── migrations/          - Schema change scripts
└── seeders/             - Demo data
```

### Entry Point (`/public/`)

```
public/
├── index.php            - Web server entry point
├── setup.php            - One-time deployment setup
├── webhook/
│   └── process-jobs.php - Background job webhook
├── uploads/             - User-uploaded files
└── .htaccess            - URL rewriting & security
```

### CLI Scripts (`/scripts/`)

```
scripts/
├── migrate.php          - Database migration runner
├── seed.php             - Demo data seeder
├── cron.php             - Background job processor
└── [utility scripts]
```

---

## 📝 File Naming Conventions

### Production Code Files
- No special suffix
- Example: `PostController.php`, `UserRepository.php`, `UserService.php`

### AI-Generated Code Explanations
- Suffix: `.explained.md`
- Location: `docs/code-explanations/[category]/`
- Example: `docs/code-explanations/src/PostController.explained.md`

### Configuration Files
- Prefix/suffix: `.example` or `.local`
- Example: `.env.example`, `.env.local`

### Migration Files
- Format: `YYYYMMDD_description.sql`
- Example: `20260101_add_users_table.sql`

---

## 🔍 Where to Find What

| Need | Location | File |
|------|----------|------|
| **How to deploy?** | Root | [INFINITYFREE_SETUP.md](INFINITYFREE_SETUP.md) |
| **Want deployment checklist?** | Root | [FINAL_DEPLOYMENT_AUDIT.md](FINAL_DEPLOYMENT_AUDIT.md) |
| **Multi-platform deploy?** | Root | [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) |
| **Understand a feature?** | docs/code-explanations | Find the controller/service |
| **Learn architecture?** | Root + docs/ | [SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md) |
| **API documentation?** | Root + docs/ | [OOP.md](OOP.md) or [docs/apis/](docs/apis/) |
| **See all code explanations?** | docs/ | [docs/code-explanations/INDEX.md](docs/code-explanations/INDEX.md) |
| **Browse all documentation?** | docs/ | [docs/DOCUMENTATION_INDEX.md](docs/DOCUMENTATION_INDEX.md) |
| **Troubleshoot a problem?** | docs/ | [docs/knowledge-base/troubleshooting-guide.md](docs/knowledge-base/troubleshooting-guide.md) |
| **Check security?** | docs/ | [docs/security/security-audit.md](docs/security/security-audit.md) |
| **See the roadmap?** | docs/ | [docs/roadmap/project-roadmap.md](docs/roadmap/project-roadmap.md) |

---

## 📋 Quick Navigation

### For Project Managers
- Start: [FINAL_DEPLOYMENT_AUDIT.md](FINAL_DEPLOYMENT_AUDIT.md)
- Then: [docs/knowledge-base/feature-map.md](docs/knowledge-base/feature-map.md)
- Then: [docs/roadmap/project-roadmap.md](docs/roadmap/project-roadmap.md)

### For Developers
- Start: [SYSTEM_OVERVIEW.md](SYSTEM_OVERVIEW.md)
- Then: [docs/code-explanations/INDEX.md](docs/code-explanations/INDEX.md)
- Then: Read explanations for the module you're working on

### For DevOps
- Start: [INFINITYFREE_SETUP.md](INFINITYFREE_SETUP.md) or [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- Then: [docs/knowledge-base/troubleshooting-guide.md](docs/knowledge-base/troubleshooting-guide.md)
- Then: [docs/knowledge-base/deployment-guide.md](docs/knowledge-base/deployment-guide.md)

### For Security Review
- Start: [docs/security/security-audit.md](docs/security/security-audit.md)
- Then: [FINAL_DEPLOYMENT_AUDIT.md](FINAL_DEPLOYMENT_AUDIT.md) (security section)
- Then: Search `docs/code-explanations/` for specific modules

---

## ✅ Organization Checklist

- ✅ All code explanations (124 files) moved to `docs/code-explanations/`
- ✅ Code explanations organized by type: backend, frontend, scripts, src, tests
- ✅ Code explanations have comprehensive INDEX.md
- ✅ Deployment guides at root level for easy access
- ✅ System analysis and audits in `docs/`
- ✅ Knowledge base guides for operations
- ✅ Architecture and API analysis in `docs/`
- ✅ Clear navigation and organization guide (this file)

---

## 🚀 Clean Codebase Status

**Production Code**: Clean, no AI-generated content  
**Documentation**: All AI-generated explanations organized in `docs/code-explanations/`  
**Deployment Info**: Easy-to-find at root level  
**Knowledge Base**: Comprehensive in `docs/knowledge-base/`

---

**Last Updated**: 2026-06-12  
**Status**: ✅ Complete and organized
