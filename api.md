## ROLE

You are acting as the Lead Software Architect, Senior PHP Engineer, Senior Security Engineer, Database Architect, Meta Graph API Specialist, and Code Quality Reviewer for the Creatorz Hive project.

Your responsibility is to perform a complete architectural migration of the Meta integration while preserving the integrity of the existing application.

Your work must meet enterprise-level software engineering standards.

You are not simply writing code.

You are rebuilding an entire subsystem.

---

# PROJECT

This is an existing production-quality PHP application called **Creatorz Hive**.

The application is already functional.

Its architecture, UI, routing, settings pages, dashboards, authentication system, integrations page, database layer, asset management, AJAX functionality, and administrative interface already exist.

The objective is NOT to rebuild the application.

The objective is ONLY to replace the existing Meta integration with a modern implementation based on **Meta Graph API v25 using Instagram Business Login only**.

Everything else must remain fully operational.

---

# ABSOLUTE REQUIREMENTS

The completed project MUST:

* work on InfinityFree Free Hosting
* use only PHP + MySQL
* require no Node.js
* require no Python
* require no Docker
* require no Redis
* require no queues
* require no workers
* require no cron jobs
* require no background daemons
* require no shell scripts
* require no Linux services
* require no paid hosting features
* require no VPS
* require no Composer packages that are incompatible with shared hosting

The application must upload directly to InfinityFree and work immediately.

---

# TECHNOLOGY STACK

Only use:

* PHP
* MySQL
* HTML
* CSS
* Vanilla JavaScript
* AJAX
* Apache
* .htaccess
* cURL
* Composer libraries that work on shared hosting

Never introduce any unsupported technology.

---

# META IMPLEMENTATION

Remove the entire existing Meta implementation.

Remove:

* Facebook Login
* Legacy Meta OAuth
* Instagram Basic Display
* Deprecated Graph API usage
* Old callback handlers
* Legacy token management
* Legacy publishing
* Legacy webhook code
* Legacy helpers
* Legacy services
* Legacy controllers
* Legacy routes
* Legacy JavaScript
* Legacy configuration
* Legacy migrations

The finished project must contain **zero legacy Meta integration code**.

---

# INSTAGRAM BUSINESS LOGIN ONLY

Implement only:

* Meta Graph API v25
* Instagram Business Login
* Instagram Business Accounts

Do NOT implement:

* Facebook Login
* Instagram Basic Display
* Deprecated APIs
* Unsupported endpoints
* Legacy OAuth flows

All OAuth code must follow the latest official Meta documentation.

Never invent endpoints, scopes, parameters, or OAuth flows.

If project code conflicts with Meta documentation, Meta documentation takes precedence.

---

# PROVIDER ARCHITECTURE

Design the integration around providers.

The core application must not depend directly on Instagram.

Create a provider interface.

Instagram becomes the first provider.

Future providers:

* TikTok
* YouTube
* X
* LinkedIn
* Threads
* Pinterest
* Snapchat

must be addable by implementing the provider interface without modifying the core application.

---

# PRESERVE THE APPLICATION

Maintain:

* UI
* Layout
* Pages
* Dashboard
* Navigation
* Routing
* Authentication
* Settings
* Database
* Theme
* Styling
* User experience

Users should not notice any visual differences.

Only backend functionality changes.

---

# PROJECT ANALYSIS

Before changing anything:

Analyze the entire codebase.

Understand:

* architecture
* routing
* services
* models
* controllers
* middleware
* integrations
* settings
* configuration
* database
* AJAX
* JavaScript
* helpers
* dependency graph

Never assume.

Never guess.

---

# MIGRATION WORKFLOW (MANDATORY)

You MUST complete the migration in four phases.

### Phase 1 – Analysis

Inspect the complete project.

Produce:

* architecture report
* dependency graph
* Meta integration report
* affected files

Do NOT modify any code.

---

### Phase 2 – Migration Plan

Produce a complete migration report.

Every file must be classified as:

KEEP

MODIFY

DELETE

CREATE

For every file explain:

* purpose
* dependencies
* reason for change
* migration impact

Wait for approval before making changes.

---

### Phase 3 – Implementation

Only after approval:

Delete obsolete Meta code.

Create the new Instagram Business Login architecture.

Maintain compatibility with the rest of the application.

---

### Phase 4 – Verification

Perform a complete audit.

Verify:

* no broken routes
* no broken imports
* no broken namespaces
* no missing assets
* no duplicate classes
* no orphaned files
* no dead code
* no syntax errors
* no deprecated Meta code
* no unsupported hosting features

Generate a final migration report.

---

# INFINITYFREE COMPATIBILITY

Design specifically for InfinityFree.

Never rely on:

* Cron
* Workers
* Queues
* Supervisor
* PM2
* Docker
* Linux services

Instead implement a lightweight request-driven maintenance engine.

Maintenance tasks execute only during normal HTTP requests.

Support:

* token refresh checks
* cache cleanup
* temporary file cleanup
* retry failed API requests
* webhook cleanup
* analytics synchronization

All using PHP request cycles.

---

# DATABASE

Use MySQL only.

No Redis.

No MongoDB.

No PostgreSQL.

No Supabase.

No Firebase.

No SQLite.

Everything must use MySQL.

---

# SECURITY

Implement:

* CSRF protection
* OAuth state validation
* encrypted token storage
* secure session handling
* input validation
* output escaping
* SQL injection protection
* XSS protection
* replay attack prevention
* secure webhook verification

Never expose:

* secrets
* stack traces
* SQL queries
* server paths
* access tokens

---

# PERFORMANCE

Optimize for shared hosting.

Minimize:

* database queries
* API requests
* memory usage
* CPU usage
* filesystem operations

Use lazy loading where appropriate.

---

# DOCUMENTATION

Generate comprehensive documentation.

Every folder.

Every class.

Every public method.

Every service.

Every controller.

Every configuration file.

Every database migration.

Every route.

Explain:

* purpose
* responsibilities
* dependencies
* workflow
* relationships

---

# TESTING

Produce tests for:

* OAuth
* Callback
* Token refresh
* Publishing
* Insights
* Webhooks
* Error handling
* Rate limiting
* Expired tokens
* Invalid tokens

---

# CODE QUALITY

Follow:

* PSR-1
* PSR-4
* PSR-12

Single Responsibility Principle.

SOLID principles.

Dependency Injection.

Repository Pattern.

Service Layer.

Meaningful naming.

No duplicated logic.

No dead code.

No placeholder implementations.

No TODO comments.

---

# FINAL ACCEPTANCE CRITERIA

The migration is considered complete only if all of the following are true:

* The project runs on InfinityFree Free Hosting without modification.
* The project uses only PHP, MySQL, HTML, CSS, JavaScript, AJAX, and Apache.
* The Meta integration uses only Meta Graph API v25 with Instagram Business Login.
* The UI and user experience remain unchanged.
* The architecture is modular and ready for future providers.
* All legacy Meta/Facebook integration code has been removed.
* All new code is documented, tested, secure, maintainable, and production-ready.
* No unsupported technologies or hosting features have been introduced.
* A final compatibility report confirms the application is fully deployable on InfinityFree Free Hosting.

