COLLEGE OF BUSINESS EDUCATION DAR ES SALAAM CAMPUS

Department of Information and Communication Technology (ICT)

# FINAL YEAR PROJECT REPORT

# CreatorzHive

### A Web-Based Content Management Platform for Social Media Influencers

**Submitted by:**
David T. Mposo
Daines O. Myoka
Abiba H. Rubanga

**Supervised by:**
Mr. Nurdin Mleli

A project report submitted in partial fulfilment of the requirements for the degree of Bachelor of Information Technology

Academic Year 2025/2026

---

## Declaration

We, the undersigned, declare that this project report entitled "CreatorzHive: A Web-Based Content Management Platform for Social Media Influencers" is our own original work and has not been submitted previously, in whole or in part, for any degree or examination at this or any other institution.

We further declare that all sources of information used in this report have been duly acknowledged and referenced in accordance with academic practice. Where other people's work has been consulted, this has been clearly indicated in the text.

We understand that any form of plagiarism or academic dishonesty is a violation of the College of Business Education's academic integrity policy and may result in disciplinary action.

| David T. Mposo | Daines O. Myoka | Abiba H. Rubanga |
|---|---|---|
| Signature & Date | Signature & Date | Signature & Date |

---

## Supervisor Certification

I, Mr. Nurdin Mleli, hereby certify that this project report has been submitted for examination with my approval as the university supervisor.

Mr. Nurdin Mleli
Supervisor, Department of ICT, CBE

Signature & Date: ______________________________

---

## Acknowledgements

We would like to express our sincere gratitude to all those who contributed to the successful completion of this project.

First and foremost, we extend our deepest appreciation to our project supervisor, Mr. Nurdin Mleli, for his invaluable guidance, constructive feedback, and continued support throughout the development of this project. His expertise and encouragement were instrumental in shaping the direction and quality of this work.

We also wish to thank the academic staff of the Department of Information and Communication Technology at the College of Business Education (CBE) for providing us with the knowledge, skills, and resources needed to undertake this final-year project. Their dedication to our academic development has been foundational to everything we have achieved in this programme.

Our gratitude also goes to our fellow students and peers who provided feedback, suggestions, and moral support during the development and testing phases of the system. Their honest input helped us improve the quality and usability of CreatorzHive.

Finally, we are deeply grateful to our families for their unwavering patience, encouragement, and support throughout our studies. Their sacrifices and belief in us have been a constant source of motivation.

David T. Mposo, Daines O. Myoka, and Abiba H. Rubanga
College of Business Education, Dar es Salaam. Academic Year 2025/2026

---

## Abstract

The rapid growth of the social media influencer economy has created a pressing need for tools that help individual content creators manage their digital activities more efficiently. Social media influencers are required to maintain an active presence across multiple platforms — including YouTube, Instagram, TikTok, and Facebook — which involves uploading content, scheduling posts, monitoring engagement, and analysing performance metrics. In the absence of a unified system, this process is fragmented, time-consuming, and difficult to sustain, particularly for independent and beginner creators.

Existing social media management tools, such as Hootsuite, Buffer, and Sprout Social, offer partial solutions but are primarily designed for business and enterprise users. Their high subscription costs, complex interfaces, and inconsistent platform support make them poorly suited to the specific needs of individual influencers and student creators.

This project proposes and develops CreatorzHive, a web-based content management platform designed specifically for social media influencers. The system provides a centralised dashboard through which users can link multiple social media accounts, schedule and publish content across platforms, track audience engagement, and view simplified performance analytics — all within a single, intuitive interface.

The system was developed using a user-centred design approach, following structured systems analysis and design methodology. Requirements were gathered through a review of existing tools and analysis of influencer workflow needs. The resulting system was evaluated through functional testing and usability assessment.

The completed system is implemented as a PHP 7.4+ web application backed by a MySQL 8 database, with a self-hosted, framework-free HTML/CSS/JavaScript frontend, deployed on InfinityFree free shared hosting. It provides working authentication (including Google Sign-In), a content planner with calendar and list views, a real Instagram Business Login (Meta Graph API v25) integration with token-based YouTube and TikTok connections, a historical analytics engine with growth insights and simple statistical predictions, a brand-deal CRM with a Kanban pipeline, an invoice generator, a media library, an in-app notification system, and an administrator panel — all verified through an automated PHPUnit test suite and, separately, a full security review covering SQL injection, cross-site scripting, CSRF, session handling, and OAuth flow integrity.

The findings demonstrate that CreatorzHive successfully addresses the identified gap by providing an affordable, accessible, and creator-focused alternative to existing enterprise tools. The platform has the potential to significantly improve the productivity and content management efficiency of social media influencers, particularly those operating independently or at an early stage of their careers, with particular relevance to creators in East Africa through its native Tanzanian Shilling (TZS) currency support and Africa/Dar es Salaam timezone default.

**Keywords:** social media management, content creator platform, influencer tools, web application, content scheduling, performance analytics, CreatorzHive

---

## Table of Contents

*(Generated automatically in the Word version of this report from the heading styles applied throughout the document.)*

## List of Figures

*(This report describes system diagrams — architecture, data flow, ER relationships, and OAuth sequence flows — in textual/tabular form within the relevant chapters, since the project does not maintain a separate set of exported diagram image files. Each description is positioned as a numbered figure reference at the point of discussion.)*

## List of Tables

*(All tables are numbered sequentially within each chapter — e.g., Table 4.1, Table 4.2 — and are listed at the point of use. Refer to Chapter 4, Chapter 5, and the Appendices for the database schema, API endpoint, and configuration tables.)*
# CHAPTER 1. INTRODUCTION

## 1.1 Background of the Study

The rise of social media over the past decade has fundamentally changed how individuals communicate, share information, and build careers. Platforms such as YouTube, Instagram, TikTok, and Facebook have created an entirely new category of digital professional — the social media influencer. These are individuals who create and publish content regularly, grow dedicated online audiences, and generate income through sponsorships, brand partnerships, and audience monetisation. According to Influencer Marketing Hub (2023), the global influencer marketing industry is valued at over $21 billion, reflecting the scale and commercial significance of content creation as a profession.

Despite the growth of this industry, most influencers — particularly independent creators, beginners, and students — operate without the resources or infrastructure that established businesses enjoy. Managing a presence across multiple social media platforms requires logging into each platform separately to upload content, schedule posts, respond to followers, and review performance metrics. This fragmented workflow is time-consuming, repetitive, and increasingly difficult to sustain as a creator grows their audience across different channels.

Currently, several social media management tools exist, such as Hootsuite, Buffer, and Sprout Social, which attempt to address this challenge by offering centralised dashboards for scheduling and analytics. However, these tools are primarily designed for businesses and marketing teams, making them expensive and overly complex for individual creators. Monthly subscription costs for these platforms range from $49 to over $300, which is prohibitive for students and emerging influencers who are still building their platforms.

This situation reveals a clear gap in the market: there is no affordable, simplified, and creator-centred platform that brings together the core tools an influencer needs — multi-platform account management, content scheduling, performance tracking, and audience engagement — in a single, intuitive system. CreatorzHive is proposed as a direct response to this gap.

## 1.2 Problem Statement

Social media influencers are required to manage their content activities across multiple platforms simultaneously. In the absence of a unified management system, creators must individually log in to each platform to perform tasks such as uploading videos, scheduling posts, replying to comments, and reviewing engagement analytics. This disjointed process results in significant inefficiency, wasted time, and inconsistent content management practices.

Existing tools that attempt to solve this problem are largely inaccessible to independent creators due to their high subscription costs, complex interfaces designed for enterprise users, and incomplete platform support caused by API restrictions. As a result, many influencers — especially those at the beginner or intermediate level — continue to rely on manual, platform-by-platform management, which limits their productivity and overall growth potential.

There is therefore a need for a web-based platform specifically designed for social media influencers — one that is affordable, easy to use, and capable of centralising the key tasks of content scheduling, analytics, and multi-account management in a single interface.

## 1.3 Objectives of the Study

The primary objective of this project is to design and develop CreatorzHive, a web-based integrated content management platform that enables creators to manage, publish, schedule, and analyze content across multiple social media platforms from a single dashboard.

The specific objectives are:

1. To design a user-centred system that provides influencers with a centralised dashboard for managing multiple social media accounts.
2. To develop a content scheduling and posting module that allows creators to plan and publish content from a single interface.
3. To implement a performance analytics module that presents simple, meaningful metrics to help creators understand their audience and content reach.
4. To evaluate the usability and effectiveness of the developed system through structured testing and user feedback.

## 1.4 Scope of the Study

This project focuses on the design and development of a web-based platform targeting individual social media influencers and content creators. The system will support integration with major social media platforms — including YouTube, Instagram, TikTok, and Facebook — subject to the availability and permissions granted by each platform's public API.

The core features included within the scope of this project are: user account registration and authentication, multi-platform social media account linking, a content scheduling and posting dashboard, basic audience engagement tools, and a performance analytics module. Advanced features such as artificial intelligence-driven content recommendations, automated community management, and monetisation tracking are considered outside the scope of this current phase but may be explored in future development.

The system is designed primarily for use by independent influencers, student creators, and micro-influencers who manage their platforms without the support of a professional team or agency. Enterprise-level multi-user team management features are not included in this phase of development.

## 1.5 Significance of the Study

The development of CreatorzHive addresses a practical and commercially relevant gap in the tools currently available to social media influencers. By providing a centralised, affordable, and user-friendly management platform, the system has the potential to significantly reduce the time and effort creators spend on administrative tasks, enabling them to focus more energy on the creative work that drives audience growth and engagement.

From an academic perspective, this project contributes to the growing body of knowledge on user-centred system design, web application development, and the digital creator economy. The project demonstrates the practical application of systems analysis, software engineering principles, and human-computer interaction concepts within a real-world and commercially relevant domain.

For the project team — David T. Mposo, Daines O. Myoka, and Abiba H. Rubanga — this project represents an opportunity to apply skills developed throughout our degree programme in a meaningful final-year capstone project that addresses a genuine problem faced by a growing professional community.

## 1.6 About the Name: CreatorzHive

The name CreatorzHive was deliberately chosen to reflect the values and purpose of the platform. The word "Hive" evokes the image of a beehive — a highly organised, efficient, and productive environment in which all activity is coordinated from a single central location. Just as bees in a hive work purposefully within a structured system to achieve collective productivity, CreatorzHive provides influencers with a central hub from which all content management activities can be coordinated and executed efficiently.

The use of "Creatorz" — with a stylistic variation — reflects the platform's identity as a modern, creator-first tool built for the next generation of digital content professionals. Together, the name communicates the platform's core promise: a productive, organised, and centralised space built specifically for content creators.

## 1.7 Structure of the Report

The remainder of this report is organised as follows. Chapter 2 presents the Literature Review, which examines existing research and tools related to social media management and identifies the gap that CreatorzHive aims to address. Chapter 3 covers System Analysis, including requirements definition and the analytical models used to understand the problem domain. Chapter 4 presents the System Design, detailing the architectural, database, and interface designs developed for the platform. Chapter 5 describes Implementation, documenting the technologies, modules, and code structures used to build the system. Chapter 6 presents Testing, covering the testing methods applied and the results obtained. Chapter 7 covers Deployment, documenting the process of installing and running the system both locally and on InfinityFree shared hosting. Chapter 8 discusses Maintenance and future improvements. The report concludes with a summary of findings, limitations encountered, and recommendations for future development, followed by references and supporting appendices.

---

# CHAPTER 2. LITERATURE REVIEW

The rapid growth of social media platforms such as YouTube, Instagram, TikTok, and Facebook has fundamentally changed how individuals build personal brands and earn income through digital content. Social media influencers — individuals who have built dedicated online followings and generate income through sponsored content, brand partnerships, and audience engagement — now represent a significant and growing segment of the digital economy. According to Influencer Marketing Hub (2023), the global influencer marketing industry was valued at over $21 billion, reflecting the increasing commercial importance of content creators as a professional class.

Despite their growing prominence, influencers face considerable challenges in managing their online presence across multiple platforms simultaneously. Unlike traditional businesses with dedicated marketing teams, most influencers operate independently or with minimal support staff. Managing content calendars, tracking engagement metrics, and maintaining consistent posting schedules across several platforms is time-consuming and operationally demanding. Studies on digital labour and creator economies, such as those by Bishop (2021) and Duffy (2017), highlight that the administrative burden of platform management often detracts from the creative work that drives audience growth.

To address these challenges, a range of social media management tools has emerged, including Hootsuite, Buffer, Sprout Social, and Later. These platforms provide centralised dashboards for scheduling posts, monitoring analytics, and managing multiple accounts from a single interface. Research by Quesenberry (2020) confirms that such tools can meaningfully improve productivity for social media practitioners by streamlining the content publishing workflow. Features such as bulk scheduling, hashtag suggestions, and performance reporting are particularly valued by frequent content creators.

However, a critical review of these existing tools reveals several limitations that make them poorly suited to the specific needs of influencers. First, most platforms are priced primarily for business and enterprise users, with monthly subscription costs that range from $49 to over $300 per month (Hootsuite, 2023; Buffer, 2023). This pricing model creates a significant accessibility barrier for independent influencers, student creators, and micro-influencers who are building their platforms without corporate backing. For creators at the early stages of their careers, these costs are often prohibitive.

Second, the interfaces and feature sets of many existing tools are designed with marketing professionals and social media agencies in mind, rather than individual creators. As a result, these systems tend to offer overly complex workflows, team collaboration features, and enterprise reporting capabilities that are irrelevant to a solo influencer. Research by Norman (2013) on user-centred design emphasises that mismatches between a tool's design assumptions and its actual user base lead to reduced adoption and user frustration. This is a common complaint among influencers who find commercial social media management platforms unnecessarily complicated.

Third, platform integration remains an ongoing challenge. Tools such as Hootsuite and Buffer support major platforms, but coverage is inconsistent — certain content formats (such as TikTok video scheduling or Instagram Reels) are either unsupported or restricted due to API limitations imposed by the platforms themselves. This forces influencers to still access individual platforms manually for certain tasks, defeating the purpose of centralised management (Abidin, 2021).

Furthermore, existing systems do not address the creator-specific features that influencers value most. Influencers require tools that help them track follower growth trends, identify top-performing content types, manage brand collaboration deadlines, and maintain audience engagement — all within a single, intuitive interface. None of the reviewed tools adequately combines these needs in a form tailored to individual creators rather than businesses.

Based on this review of existing literature and currently available systems, a clear gap is evident: there is no affordable, user-friendly, and influencer-focused platform that brings together content scheduling, performance analytics, and creator workflow management in a single solution designed specifically for independent social media influencers. This gap represents the primary motivation for the development of CreatorzHive.

CreatorzHive is proposed as a web-based content management platform designed specifically to meet the needs of social media influencers. By integrating multi-platform account management, simplified content scheduling, and essential performance analytics into a clean, intuitive interface, CreatorzHive aims to reduce the operational burden on independent creators and allow them to focus on producing quality content. The system is positioned as an accessible and creator-centred alternative to the enterprise-focused tools currently dominating the market.

Beyond the general gap identified above, CreatorzHive's actual implementation (detailed from Chapter 3 onward) extends this positioning in two ways not originally anticipated at proposal stage. First, it adds a **brand-deal and invoicing layer** — a Kanban-style deal pipeline and an invoice generator with native Tanzanian Shilling (TZS) support — addressing the monetisation and client-management side of a creator's workflow that Hootsuite, Buffer, and Sprout Social do not attempt to cover at all. Second, it adds a **historical analytics and insights engine** that records periodic snapshots of follower, engagement, and reach metrics per platform and derives statistical growth insights and short-horizon predictions (via moving averages and linear regression) directly from that history, rather than only displaying current point-in-time totals. Both extensions are documented in full in Chapters 4 and 5, with an honest account in Chapter 3 of which literature-identified gaps remain only partially closed in the current version (for example, TikTok's OAuth connection is token-based rather than a full interactive login flow, and PDF invoice export is not yet implemented).
# CHAPTER 3. SYSTEM ANALYSIS

## 3.1 Analysis of the Current (Manual) System

As established in Chapter 2, creators without a unified platform manage each social channel independently: logging into Instagram, TikTok, YouTube, and X/Twitter separately to publish content, checking each platform's own analytics dashboard to gauge performance, and tracking brand sponsorships and invoices in ad-hoc tools such as spreadsheets, WhatsApp threads, or email. This manual approach has four recurring problems that directly motivated CreatorzHive's requirements:

1. **Fragmented publishing** — no single place to schedule content across platforms; each platform's own scheduler (where one exists) is used in isolation.
2. **Siloed analytics** — follower counts, engagement rates, and reach exist per platform with no historical trend or cross-platform comparison.
3. **Unstructured brand-deal management** — sponsorship negotiations, deliverables, and payment status are tracked informally, with no audit trail.
4. **Currency and locale mismatch** — international scheduling tools default to USD and do not account for East African creators' need for TZS-denominated invoicing and the Africa/Dar_es_Salaam timezone.

## 3.2 Requirements Specification

Requirements below reflect what is implemented in the current version of CreatorzHive (verified directly against the source code), not aspirational features. Where a feature was planned but is not present in the codebase, this is stated explicitly rather than assumed.

### 3.2.1 Functional Requirements

| ID | Requirement | Implemented |
|----|-------------|:---:|
| FR1 | Users can register with email/password and verify their email via a tokenised link | Yes |
| FR2 | Users can sign in with email/password or with Google Sign-In (OAuth 2.0) | Yes |
| FR3 | Users can reset a forgotten password via an emailed OTP/token | Yes |
| FR4 | Failed login attempts are rate-limited per account/IP | Yes |
| FR5 | Users can connect an Instagram Business account via Meta's OAuth login flow | Yes |
| FR6 | Users can connect a YouTube channel via Google OAuth with token refresh | Yes |
| FR7 | Users can connect a TikTok account via TikTok's OAuth (PKCE) login flow | Yes |
| FR8 | Users can connect an X/Twitter account | Token-based only (no interactive OAuth flow) |
| FR9 | Users can create, edit, duplicate, and soft-delete posts with a title, content, caption, cover image/video, tags, and target platform(s) | Yes |
| FR10 | Posts can be scheduled for future publication and are queued for background, asynchronous multi-platform publishing | Yes |
| FR11 | Users can view posts in a calendar view and a filterable/sortable list view | Yes |
| FR12 | The system records per-platform publish results (success/failure) for each post | Yes |
| FR13 | The system records periodic (daily/weekly/monthly) analytics snapshots per platform and computes growth deltas | Yes |
| FR14 | The system generates plain-language insights (e.g. "Instagram engagement is increasing faster than follower growth") from historical snapshots | Yes |
| FR15 | The system produces simple statistical predictions (moving average / linear regression) of near-future metric values | Yes |
| FR16 | Users can manage brand sponsorship deals through a six-stage pipeline (lead → negotiation → contract → active → completed / cancelled) | Yes |
| FR17 | Users can generate invoices (with TZS as the default currency) linked to a deal, with line items, tax, and a status workflow | Yes |
| FR18 | Users can upload, browse, and delete image/video media in a shared media library | Yes |
| FR19 | Users receive in-app notifications for key events (post published/failed, deal updated, invoice paid) with configurable email preferences | Yes |
| FR20 | Users can manage their profile, password, active sessions, UI preferences, and platform integrations from a Settings area | Yes |
| FR21 | Administrators can manage user accounts, platform API credentials, and view an audit log and login-security activity | Yes |
| FR22 | Invoices can be exported as a formatted PDF document | **Not implemented in the current version** (a `pdf_url` field exists on the `invoices` table but is not yet populated by any code path) |
| FR23 | Team/agency multi-user accounts with role-based permissions | **Not implemented in the current version** |
| FR24 | AI-driven content or caption suggestions | **Not implemented in the current version** |

### 3.2.2 Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Performance** | Pages should load within 2–3 seconds on shared hosting; database queries use indexes on all frequently filtered columns (user_id, status, platform, dates). |
| **Availability** | The system must degrade gracefully rather than fail outright when a background job or external social API is temporarily unavailable (e.g. mock-data fallback, retry with exponential backoff). |
| **Security** | Passwords hashed with bcrypt; all state-changing requests protected by CSRF tokens; all database access via parameterised queries; social media access tokens encrypted at rest. |
| **Compatibility** | Must run on PHP 7.4+ and MySQL 8 without any PHP extension, background daemon, or SSH access beyond what a typical free shared-hosting account provides. |
| **Usability** | Consistent navigation shell (sidebar/navbar), dark/light/system theme support, and skeleton-loading states across all data-driven pages. |
| **Maintainability** | Business logic organised by responsibility (Controllers/Services/Repositories) with PSR-4 autoloading, so a change to one concern does not require touching unrelated code. |
| **Localisation** | TZS default currency, Africa/Dar_es_Salaam default timezone, per-user date-format and language preference fields. |

## 3.3 User Roles

The system defines three roles, enforced at both the routing (middleware) and application layer:

| Role | Description | Access |
|------|-------------|--------|
| **Creator** | The primary end user — an individual influencer/content creator | Dashboard, Planner, Analytics, Deals, Invoices, Media, Notifications, Settings |
| **Brand** | A secondary account type recognised by the schema and registration flow | Same content-area access as Creator in the current version; no dedicated brand-only workflow (e.g. campaign posting) exists yet |
| **Admin** | Platform operator | User management, platform API credential management, audit log, security activity — and is explicitly *blocked* from the creator-facing content routes (planner, analytics, deals, invoices, media) by a `non_admin` middleware guard, keeping the admin account scoped to operations only |

## 3.4 Use Case Overview

The following narrative use cases summarise the primary interactions supported by the system; each corresponds to one or more routes documented in Chapter 4 and Chapter 5.

- **UC1 — Register and verify an account:** A visitor registers with an email and password, chooses a role (creator/brand), receives a verification email, and clicks the link to activate the account.
- **UC2 — Sign in:** A registered user authenticates with email/password or Google Sign-In and is redirected to their role-appropriate dashboard.
- **UC3 — Connect a social account:** A creator navigates to Settings → Integrations and initiates an OAuth (Instagram, YouTube, TikTok) or token-based (X/Twitter) connection to a social platform.
- **UC4 — Plan and schedule content:** A creator creates a post, attaches media and tags, selects target platforms, and either saves it as a draft or schedules it for future publication.
- **UC5 — Background publish:** At the scheduled time, an asynchronous job publishes the post to each selected platform via that platform's API and records a per-platform result.
- **UC6 — Review analytics:** A creator opens the Analytics page to see follower growth, engagement trends, platform comparisons, generated insights, and short-term predictions derived from historical snapshots.
- **UC7 — Manage a brand deal:** A creator adds a brand deal, moves it through pipeline stages on a Kanban board, and links it to the posts that fulfil its deliverables.
- **UC8 — Generate an invoice:** A creator creates an invoice from a completed deal, sets line items and tax, and marks it paid once payment is received.
- **UC9 — Administer the platform:** An administrator creates/edits/deactivates user accounts, configures platform API credentials, and reviews the audit log and failed-login activity.

## 3.5 System Constraints and InfinityFree Compatibility

CreatorzHive was deliberately constrained from the outset to run on **InfinityFree**, a free shared-hosting provider, which imposes several limitations documented in `docs/reference/infinityfree-compatibility-report.md` and directly reflected in the system design:

| Constraint | Standard Solution | CreatorzHive's Approach |
|---|---|---|
| No SSH access | `composer install`, database migration via CLI | `vendor/` is committed/uploaded pre-built; `public/setup.php` is a one-time, IP-restricted web endpoint that imports `database/schema.sql` and creates the first admin user |
| No reliable cron daemon | `cron` running a worker every minute | A `job_queue` database table holds pending jobs; an external free uptime-monitoring service (UptimeRobot) is configured to call `public/webhook/process-jobs.php` (protected by a shared secret) at a fixed interval, which then processes a small batch of jobs per call |
| Execution time limits (~30–60s) | Long-running worker process | Each webhook call processes only 2–10 jobs (configurable, capped at 50) rather than draining the entire queue, avoiding timeouts |
| Session files potentially readable by other hosting accounts on the same shared server | OS-level session isolation | A custom database-backed session handler (`backend/core/db-session-handler.php`) stores session data in a `sessions` table instead of the filesystem |
| No Redis/Memcached | External cache/rate-limit store | Login rate limiting is implemented as a token-bucket algorithm stored directly in a `rate_limits` MySQL table |
| No control over PHP-FPM worker session affinity across a redirect | Sticky sessions | The Instagram OAuth connect flow (uniquely among the four OAuth integrations) uses a self-contained, HMAC-signed `state` parameter that re-establishes the user's identity from the callback URL itself rather than depending on session continuity across the redirect — a deliberate design decision documented further in Chapter 4 |

## 3.6 Risk Analysis

| Risk | Likelihood | Impact | Mitigation Implemented |
|---|---|---|---|
| Third-party OAuth/API changes (Meta Graph API, TikTok, YouTube Data API) break an integration | Medium | High | Each platform integration is isolated behind a `SocialProviderInterface`; a mock-fallback flag (`SOCIAL_API_MOCK_FALLBACK`) allows the rest of the system to keep functioning during an API outage |
| Background job processing is delayed because it depends on an external uptime-monitor ping rather than a true cron daemon | Medium | Medium | Jobs include attempt counts and error messages for visibility; a manual "reset stuck jobs" and "force refresh" endpoint exists in the webhook script for operator intervention |
| Free-hosting anti-bot/edge layer intercepts non-browser HTTP requests (observed in practice for external verification bots) | Medium | Medium | Documented as a known operational limitation; time-sensitive server-to-server calls are supplemented with manual browser-triggered fallbacks |
| Loss of the `APP_SECRET` value (used to encrypt OAuth tokens and sign CSRF/OAuth-state values) | Low | High | `.env` is excluded from version control; the application refuses to boot in production if `APP_SECRET` is missing or shorter than 16 characters |
| SQL injection / XSS in a fast-moving codebase | Low | High | A single PDO wrapper is used exclusively for all database access (parameterised queries only); a shared client-side escaping helper is used consistently before any untrusted data is inserted into the DOM. Verified by a dedicated security review (Chapter 5, §5.15). |
| Storage growth from uploaded media on a limited shared-hosting disk quota | Medium | Low | 10MB per-file upload cap; images are re-encoded (not just copied) during thumbnail generation |
# CHAPTER 4. SYSTEM DESIGN

## 4.1 Overall Architecture

CreatorzHive is a **server-rendered, single front-controller PHP application**. It is not a REST API consumed by a JavaScript single-page application; instead, each feature area is a thin PHP page that renders an HTML shell, which vanilla JavaScript then populates via `fetch()` calls to a small internal JSON API — a "fragment" architecture rather than a client-side framework.

The codebase contains two coexisting layers that must be understood together:

- **`src/`** — the current, actively developed layer. Organised as PSR-4 classes under the `CreatorzHive\` namespace (Controllers, Services, Repositories, Middleware, Jobs, Core, Providers, Support), loaded via Composer autoloading and wired together by a small dependency-injection container (`CreatorzHive\Core\Container`, populated by `CreatorzHive\Providers\AppServiceProvider`).
- **`backend/`** — a procedural compatibility layer that predates the OOP migration. It defines the front controller (`backend/index.php`), the route tables (`backend/routes/web.php`, `backend/routes/api.php`), and roughly 90 global helper functions (`backend/compat/models.php`, `backend/compat/services.php`, `backend/compat/auth.php`) that act as a bridge, letting older procedural call sites invoke the same OOP services and repositories without being rewritten. New code is expected to use the DI container directly rather than these global-function bridges.

There is also a **third, disused layer**: an `app/` directory (`App\Controllers`, `App\Core`) and a root-level `routes.php`, representing an earlier prototype. Neither is on the live request path — the only front controller that Apache ever routes real traffic to is `public/index.php` → `backend/index.php` — and both are blocked from direct HTTP access at the `.htaccess` level for defence-in-depth (see §4.11).

### Figure 4.1 — Request Life Cycle (described)

```
Browser
   │  HTTP request (?route=<name>)
   ▼
Apache (.htaccess rewrite) ──► public/index.php ──► backend/index.php
                                                        │
                                    1. load_env() / APP_SECRET check
                                    2. security headers set
                                    3. Composer autoload + DI container boot
                                    4. session_start_safe() (DB-backed session)
                                    5. csrf_generate_token()
                                    6. backend/routes/web.php + api.php registered
                                    7. router_dispatch()
                                        │
                                        ├─ middleware: auth / non_admin / role:x / csrf
                                        │
                                        ▼
                              Controller (src/Controllers/*)
                                        │
                                        ▼
                                Service (src/Services/*)  ── business logic
                                        │
                                        ▼
                             Repository (src/Repositories/*) ── SQL via PDO
                                        │
                                        ▼
                                     MySQL
```

## 4.2 Folder Structure

```
creatorzhive/
├── src/            PSR-4 "CreatorzHive\" — Controllers, Services, Repositories,
│                   Middleware, Jobs, Core, Providers, Support, Contracts, Helpers, Config
├── backend/        Procedural front controller, route tables, compat bridges, storage/
├── frontend/       CSS, JS, HTML/PHP page templates, self-hosted fonts, Chart.js
├── public/         Web document root: index.php, setup.php, webhook/, uploads/
├── database/       schema.sql (full structure) + migrations/ (incremental additions)
├── scripts/        CLI utilities (migrate, seed, hash-password, token re-encryption)
├── tests/          PHPUnit unit and integration test suite
├── docs/           Project documentation (guides, business analysis, roadmap)
├── vendor/         Composer dependencies
├── .env / .env.example   Runtime configuration
├── composer.json
└── .htaccess       URL routing and sensitive-path blocking
```

`src/` is further organised by responsibility, one folder per architectural concern (Controllers handle HTTP only; Services hold business logic; Repositories hold SQL; Middleware enforces cross-cutting policy; Jobs are queued background units of work; Support holds narrowly scoped helper classes such as `MediaUploadHelper` and `PostInputNormalizer`).

## 4.3 Database Design

The database contains **25 tables**: 22 defined in the primary `database/schema.sql` export, plus 3 added by later incremental migration files under `database/migrations/` (`insights_cache`, `prediction_cache`, `creator_scores`, `post_performance` — one migration adds two tables). All tables use the InnoDB engine with `utf8mb4_unicode_ci` collation and enforce referential integrity via foreign-key constraints, most with `ON DELETE CASCADE` from the owning `users` row.

### Table 4.1 — Core Identity and Session Tables

| Table | Purpose | Key columns |
|---|---|---|
| `users` | Account record | `id`, `username` (unique), `email` (unique), `password` (bcrypt hash), `google_id` (unique, nullable), `role` (`creator`\|`brand`\|`admin`), `timezone` (default `Africa/Dar_es_Salaam`), `email_verified`, `is_active` |
| `email_verifications` | Single-use email verification tokens | `token` (unique), `expires_at`, `used_at` |
| `password_resets` | Single-use password reset tokens/OTPs | `token` (unique), `expires_at`, `used_at` |
| `sessions` | Database-backed PHP session storage | `id` (session ID, PK), `user_id`, `ip_address`, `user_agent`, `payload`, `last_active` |
| `rate_limits` | Token-bucket login rate limiting | `key` (unique, e.g. `ip:x.x.x.x:login`), `tokens`, `last_refill` |
| `user_preferences` | Per-user UI/locale settings | `theme`, `language`, `default_currency` (default `TZS`), `date_format`, `time_format`, `week_starts_on`, `sidebar_collapsed` |

### Table 4.2 — Content and Social Publishing Tables

| Table | Purpose | Key columns |
|---|---|---|
| `posts` | A piece of content to publish | `title`, `content`, `caption`, `platforms` (JSON array of slugs), `status` (`draft`\|`scheduled`\|`published`\|`failed`), `scheduled_at`, `published_at`, `cover_media_id` (FK → `media_files`), `is_deleted` (soft delete), full-text index on `title`+`content` |
| `media_files` | Uploaded image/video assets | `file_path`, `cdn_url`, `thumbnail_url`, `mime_type`, `file_size`, `width`, `height`, `duration`, `alt_text` |
| `post_media` | Many-to-many: post ↔ attached media, ordered | `post_id`, `media_id`, `sort_order` |
| `tags` | User-defined, colour-coded labels | `name` (unique per user), `color` |
| `post_tags` | Many-to-many: post ↔ tag | `post_id`, `tag_id` |
| `social_accounts` | A connected external platform account | `platform` (enum: `instagram`,`tiktok`,`youtube`,`twitter`,`facebook`\*), `platform_user_id`, `access_token`/`refresh_token` (encrypted), `token_expires_at`, `follower_count`, `is_active` |
| `platform_post_results` | Per-platform outcome of a publish attempt | `post_id`, `social_account_id`, `platform`, `platform_post_id`, `platform_url`, `status` (`success`\|`failed`), `error_message` |
| `post_performance` | Cached real per-post engagement metrics (Instagram/YouTube only) | `platform_post_result_id` (unique), `available` (0 if the platform grants no insights scope), `likes`, `comments`, `shares`, `saves`, `reach`, `engagement_rate` |

\* The `facebook` enum value remains in the schema for backward compatibility, but the application layer's canonical platform list (`PlatformHelper::slugs()`) no longer includes Facebook — it has been withdrawn from active use (confirmed in `docs/guides/CODEBASE_ORGANIZATION.md`: "Facebook has been removed").

### Table 4.3 — Analytics Tables

| Table | Purpose | Key columns |
|---|---|---|
| `analytics` | One row per user: current rolling totals | `total_posts`, `published_posts`, `total_followers`, `total_impressions`, `total_engagements`, `avg_engagement_rate`, `total_revenue` |
| `analytics_snapshots` | Historical time series, one row per user/date/period/platform | `social_account_id` (nullable = aggregate), `platform` (nullable = aggregate), `snapshot_date`, `period` (`daily`\|`weekly`\|`monthly`), `followers`, `impressions`, `reach`, `likes`, `comments`, `shares`, `saves`, `link_clicks`, `profile_visits`, `engagement_rate` — unique on (`user_id`,`snapshot_date`,`period`,`platform`) |
| `insights_cache` | Generated plain-language insight, regenerated in place | `insight_key`, `message`, `severity` (`positive`\|`negative`\|`neutral`), `metric`, `platform` — unique on (`user_id`,`insight_key`) |
| `prediction_cache` | Generated statistical prediction, regenerated in place | `platform` (nullable), `metric`, `horizon` (`next_week`\|`next_month`), `method` (`linear_regression`\|`moving_average`), `predicted_value`, `based_on_snapshots` — unique on (`user_id`,`platform`,`metric`,`horizon`) |
| `creator_scores` | Cached composite creator/growth score | `growth_score`, `engagement_score`, `consistency_score`, `creator_score` — unique on `user_id` |

`insights_cache`, `prediction_cache`, and `creator_scores` are explicitly *caches*, not history — each is regenerated in place via an upsert, while `analytics_snapshots` remains the single source of historical truth from which insights, predictions, and scores are all derived.

### Table 4.4 — Monetisation Tables

| Table | Purpose | Key columns |
|---|---|---|
| `deals` | A brand sponsorship opportunity | `brand_name`, `title`, `amount`, `currency` (default `TZS`), `status` (`lead`→`negotiation`→`contract`→`active`→`completed`/`cancelled`), `deal_type` (`sponsored_post`\|`affiliate`\|`ambassador`\|`gifted`\|`other`), `deadline_at`, `is_deleted`; full-text index on `brand_name`+`title` |
| `deal_posts` | Many-to-many: deal ↔ fulfilling post | `deal_id`, `post_id` |
| `invoices` | A billing document, optionally linked to a deal | `invoice_number` (unique), `line_items` (JSON), `subtotal`, `tax_rate`, `tax_amount`, `total`, `currency` (default `TZS`), `status` (`draft`→`sent`→`paid`/`overdue`/`cancelled`), `due_date`, `pdf_url` (reserved, unused — see §3.2.1 FR22) |

### Table 4.5 — Notifications, Preferences, and Operations Tables

| Table | Purpose | Key columns |
|---|---|---|
| `notifications` | In-app notification feed item | `type`, `title`, `body`, `action_url`, `icon`, `is_read`, `read_at` |
| `notification_preferences` | Per-user email/push toggle per event type | `email_post_published`, `email_post_failed`, `email_deal_updated`, `email_invoice_paid`, `email_weekly_summary`, `push_post_published`, `push_deal_updated` |
| `job_queue` | Asynchronous background job queue | `queue`, `job_class`, `payload` (JSON), `attempts`/`max_attempts`, `status` (`pending`\|`running`\|`completed`\|`failed`), `available_at`, `error_message` |
| `audit_logs` | Immutable record of sensitive actions | `action` (e.g. `post.created`, `deal.status_changed`), `entity_type`, `entity_id`, `old_values`/`new_values` (JSON), `ip_address`, `user_agent` |

### 4.3.1 Entity-Relationship Overview (described)

```
users (1) ──< social_accounts
users (1) ──< posts (1) ──< post_media >── (1) media_files
users (1) ──< posts (1) ──< post_tags  >── (1) tags
posts (1) ──< platform_post_results ──(1)── post_performance
posts (1) ──< deal_posts >── (1) deals
users (1) ──< deals (1) ──< invoices
users (1) ──1:1── analytics
users (1) ──< analytics_snapshots
users (1) ──1:1── creator_scores
users (1) ──1:1── user_preferences
users (1) ──1:1── notification_preferences
users (1) ──< notifications
users (1) ──< sessions
users (1) ──< audit_logs
users (1) ──< email_verifications / password_resets
```

Every child table cascades on delete from `users`, so removing a user account (an administrator action) leaves no orphaned rows; `social_account_id`, `cover_media_id`, and `deal_id` foreign keys instead use `ON DELETE SET NULL`, since a deleted media file or disconnected social account should not destroy the posts or invoices that reference it.

## 4.4 Data Flow

For a state-changing request (e.g. creating a post): the browser's `fetch()` call posts JSON/form data to `?route=create_post` → the router matches the route, runs the `auth`, `non_admin`, and `csrf` middleware in order → `PostController::store()` reads and validates the request via `PostInputNormalizer` → `PostRepository::create()` inserts the row (and `attachMedia()`/`attachTags()` insert the join-table rows) inside a transaction → if `scheduled_at` is set, a `PublishPostJob` row is inserted into `job_queue` → the controller returns a JSON response, which the page's JavaScript module uses to update the DOM without a full page reload.

For a read-only request (e.g. loading the dashboard), the flow is the same up to the Repository layer, which issues `SELECT` queries (via `DashboardRepository`) and the Controller serialises the result as JSON.

## 4.5 Routing

Routing is **query-string based**, not path-based: every request carries `?route=<key>` (or the router falls back to parsing the request path when `route` is absent). Two route tables are registered on every request:

- `backend/routes/web.php` — GET-only page routes, e.g. `dashboard`, `planner`, `analytics`, `settings-profile`, `admin-users`.
- `backend/routes/api.php` — the JSON API, both GET (data) and POST (mutation) routes, e.g. `posts` / `create_post`, `deals_data` / `create_deal`.

Each route declares an ordered list of middleware tags — `auth` (must be logged in), `non_admin` (creator/brand content areas, blocks admin accounts), `role:admin` (admin-only), and `csrf` (required on every POST). The router (`backend/core/router.php`) resolves the handler through the DI container, so controllers receive their dependencies (Services, Repositories, the `ViewRenderer`/`JsonResponder`) automatically.

## 4.6 Authentication Flow

1. **Registration** (`POST register`) — `AuthController::register()` validates input, hashes the password with bcrypt (cost 12), creates the user row, and sends a verification email containing a single-use token.
2. **Email verification** (`GET verify?token=`) — validates and consumes the token, sets `email_verified = 1`.
3. **Login** (`POST login`) — checks the token-bucket rate limiter first (`rate_limits` table), then verifies the password hash; on success, regenerates the session ID and stores the user record (minus the password field) in the session.
4. **Forgot/Reset password** (`POST forgot-password` / `POST reset-password`) — issues and later validates a single-use reset token/OTP, then re-hashes and stores the new password.
5. **Logout** (`POST logout`) — destroys the session entirely (`session_destroy_all()`).

## 4.7 OAuth Flow

Four platform integrations exist, three of which follow one consistent, session-bound pattern, and one (Instagram) that deliberately does not — a distinction significant enough to warrant its own sub-sections.

### 4.7.1 Google Sign-In (application login, not a social "connect")

`GoogleAuthController::start()` generates a random `state`, stores it in `$_SESSION['google_auth_state']`, and redirects to Google's OAuth 2.0 consent screen. `callback()` compares the returned `state` against the session value using `hash_equals()` before exchanging the authorization code for an access token and fetching the user's profile (`openidconnect.googleapis.com/v1/userinfo`). A `google_id` match (or an email match, linking the account) resolves an existing user; otherwise a new account is registered.

### 4.7.2 YouTube and TikTok (platform "connect", not login)

Both follow the same session-bound pattern as Google: a random `state` (and, for TikTok, a PKCE `code_verifier`) is generated in `connectStart()` and stored in the session together with the connecting user's ID; the callback validates `state` via `hash_equals()` against the session value before exchanging the authorization code and storing the resulting access/refresh token (encrypted) against the user's `social_accounts` row.

### 4.7.3 Instagram Business Login (Meta Graph API v25) — a deliberately different design

Instagram's flow uses a **self-contained, HMAC-signed `state`** (`userId.nonce.signature`, signed with `APP_SECRET`) instead of a session-stored value. The callback derives the connecting user's identity directly from the signed `state` payload and re-establishes their application session from it, rather than relying on session continuity across the Instagram redirect.

This design exists because of a specific, previously encountered InfinityFree hosting behaviour: PHP workers on the shared host do not reliably retain the same session across the round trip to Instagram's authorization server and back, which silently broke a session-dependent flow. The stateless, signed alternative sidesteps that problem entirely. For the same reason, the live callback is configured (via the `INSTAGRAM_OAUTH_REDIRECT_URI` environment variable) to point at a standalone root-level script, `ig-cb.php`, rather than the routed `instagram-callback` handler — `ig-cb.php` re-implements the same signed-state verification independently so it can run before the full application bootstrap completes.

**Design trade-off, stated plainly:** because the signed state is not bound to the browser session that initiated it, anyone in possession of a given signed state value could, in principle, complete that specific connection on a different browser/session than the one that started it — a materially different security property from the session-bound Google/TikTok/YouTube flows. A time-limited validity window has been evaluated as a mitigation; fully session-binding the flow (as the other three integrations do) has **not** been applied, because doing so risks reintroducing the original session-loss failure the stateless design was built to solve, and any such change would require live testing against the actual InfinityFree environment before being trusted. This is recorded here, rather than silently, as an example of a real, considered security engineering trade-off made under a genuine hosting constraint.

### Table 4.6 — OAuth State Handling Comparison

| Integration | State storage | PKCE | Session-bound |
|---|---|:---:|:---:|
| Google Sign-In | `$_SESSION` | No | Yes |
| YouTube connect | `$_SESSION` | No | Yes |
| TikTok connect | `$_SESSION` | Yes | Yes |
| Instagram connect | Signed into the `state` value itself (HMAC-SHA256, `APP_SECRET`) | No | **No — deliberate design constraint, see above** |

## 4.8 Session Management

Sessions are stored in the `sessions` MySQL table via a custom handler (`backend/core/db-session-handler.php`), not the filesystem — avoiding the shared-hosting risk of session files being readable by other tenant accounts on the same server. Cookies are configured `HttpOnly`, `SameSite=Lax`, with `Secure` controlled by the `SESSION_SECURE` environment flag (enabled in production). A user-agent–derived fingerprint (`session_fingerprint_generate()`/`_is_valid()`) provides an additional, lightweight session-integrity check. Settings → Security lets a user list and revoke individual or all active sessions.

## 4.9 Security Architecture

- **SQL injection:** eliminated structurally — `CreatorzHive\Core\Database\Connection` wraps PDO with `ATTR_EMULATE_PREPARES` disabled and exposes only parameterised `query()`/`fetchOne()`/`fetchAll()`/`insert()`/`update()`/`delete()` methods; table/column identifiers are always backtick-quoted from fixed, developer-supplied strings, never user input.
- **CSRF:** every POST route in `backend/routes/api.php` carries a `csrf` middleware tag; `CsrfMiddleware::validatePost()` compares the submitted `_token` against the session-stored token with `hash_equals()`, and exempts only genuine bearer-token API clients (`Authorization: Bearer` header), never cookie-authenticated browser requests.
- **XSS:** server-rendered views escape output with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`; client-side JavaScript routes untrusted API data through a shared `Utils.escapeHtml()` helper before any `innerHTML` assignment — verified by direct code sampling across the planner, admin, deals, and settings modules.
- **Password storage:** bcrypt, cost factor 12.
- **Token storage:** OAuth access/refresh tokens are encrypted at rest (`CreatorzHive\Core\Security\TokenCrypto`) before being written to `social_accounts`.
- **Authorization:** every sensitive route carries an explicit `auth` and/or `role:admin`/`non_admin` middleware tag, enforced server-side by `RoleMiddleware`/`AuthMiddleware` — never left to client-side/UI-only enforcement.
- **Security headers:** `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, `Referrer-Policy: strict-origin-when-cross-origin` are set on every response (`backend/index.php`). A Content-Security-Policy header is intentionally not yet enabled, pending confirmation it will not interfere with the Google/Instagram/TikTok OAuth redirect flows.
- **Uploads:** server-side MIME sniffing via `finfo` against a fixed extension whitelist (JPEG/PNG/GIF/WebP/MP4/WebM), fully random destination filenames, a 10MB cap, and an uploads-directory `.htaccess` that disables PHP execution and blocks dotfile access.

This architecture was independently verified through a dedicated security audit covering SQL injection, XSS, CSRF, session handling, all four OAuth flows, file uploads, path traversal, dangerous PHP functions, and a malware/backdoor sweep — no SQL injection, no malware, and consistent escaping/CSRF coverage were found; the findings that were identified (an unauthenticated diagnostic script, and the Instagram OAuth state design discussed above) are detailed in Chapter 5, §5.15.

## 4.10 Dashboard and Analytics Architecture

`DashboardController`/`DashboardService`/`DashboardRepository` aggregate: total followers across connected platforms, post counts by status, upcoming scheduled posts (next 14 days), recent notifications, revenue summary, and active-deal count, for a single-screen overview.

The Analytics module is built in three layers over the same `analytics_snapshots` history:

1. **`AnalyticsRepository`** — raw aggregation queries (sums, growth-over-range, top/worst-performing posts by a whitelisted metric).
2. **`AnalyticsIntelligenceService`** — computes growth deltas between snapshot dates, generates plain-language insights (e.g. detecting a declining engagement streak, a drop in posting frequency, or higher weekend engagement) and short-horizon predictions using either a moving average or a simple linear regression over the recent daily series, caching both in `insights_cache`/`prediction_cache`.
3. **`CreatorScoreService`** — derives a composite growth/engagement/consistency/creator score per user (`creator_scores`) and a per-platform health classification, from the same underlying snapshot history.

Analytics data is refreshed by the asynchronous `FetchAnalyticsJob` and `FetchPostPerformanceJob` (queued and triggered via the webhook mechanism described in §4.13, and additionally triggered when a user opens the Dashboard or Analytics page if the cached data is older than a configured threshold), rather than a database view or trigger — the review conducted for this report found no `CREATE VIEW` or `CREATE TRIGGER` statement anywhere in `database/schema.sql` or the migration files, which corrects an earlier internal note that had assumed such a trigger existed; the `analytics`/`user_preferences`/`notification_preferences` rows are instead created lazily on first access via `INSERT` statements in the relevant Repository classes.

## 4.11 File Upload Flow

`MediaController::upload()` receives a multipart file, rejects it if it exceeds 10MB or fails PHP's own upload-error check, sniffs its true MIME type with `finfo` (never trusting the client-supplied `type` field) against a fixed whitelist, generates a random 32-character hex filename with the correct extension, stores it under `public/uploads/<year>/<month>/`, and — for images — generates a resized thumbnail via GD. The physical `public/uploads/.htaccess` file separately blocks `.php` execution and dotfile access inside the uploads directory as a second layer of defence.

## 4.12 Configuration Flow

`AppConfig` (`src/Config/AppConfig.php`) self-loads `.env` on first access via a `load_env()` helper that populates `$_ENV`, `getenv()`, and `$_SERVER` in parallel (a three-layer fallback used because some shared-hosting configurations disable one or two of these mechanisms). In production, `backend/index.php` fatally refuses to boot if `APP_SECRET` is empty or shorter than 16 characters, preventing an insecure deployment from running silently.

## 4.13 Composer Dependencies

CreatorzHive intentionally keeps its Composer footprint minimal, per its InfinityFree-compatibility requirement (no dependency may require a PHP extension or system tool unavailable on free shared hosting):

| Package | Version | Purpose |
|---|---|---|
| `phpmailer/phpmailer` | ^6.8 (installed: 6.12.0) | SMTP email delivery (verification, password reset, notifications) |
| `phpunit/phpunit` (dev only) | ^9.6 | Automated test suite |

Autoloading is PSR-4 (`CreatorzHive\` → `src/`, plus the legacy `App\` → `app/` mapping retained only because the disused prototype directory is still present), with `backend/helpers/functions.php` loaded unconditionally as a Composer "files" autoload entry so its ~90 global helper functions are always available.

## 4.14 Background Job (Cron) Design

InfinityFree's free tier provides no dependable cron daemon, so CreatorzHive implements its own minimal queue:

- `job_queue` holds pending/running/completed/failed jobs with a `job_class`, JSON `payload`, attempt count, and `available_at` timestamp (supporting delayed retry).
- Job classes (`src/Jobs/`) — `PublishPostJob`, `FetchAnalyticsJob`, `FetchPostPerformanceJob`, `SendNotificationJob`, `CleanupMediaJob` — each implement `JobHandlerInterface` and are resolved through the DI container.
- `public/webhook/process-jobs.php` is the sole external entry point: it requires a shared `WEBHOOK_SECRET` (as a `?secret=` query parameter or an `X-Webhook-Secret` header), then dequeues and runs a small, configurable batch of pending jobs (default 10, capped at 50) before returning a JSON status summary — kept deliberately small to stay within shared-hosting execution-time limits.
- An external free uptime-monitoring service (UptimeRobot) is configured to call this webhook on an interval (documented as 5 minutes, the free-tier minimum), functioning as the de facto cron trigger.
- The same script exposes several operator diagnostic query parameters (`?details=1`, `?reset_stuck=1`, `?diagnose=1`, `?refresh_analytics=1`, etc.), gated by the same shared secret, to inspect and unstick the queue without SSH access.
# CHAPTER 5. IMPLEMENTATION

This chapter documents how each major module is actually implemented, referencing the real controller, service, repository, and frontend files that make up each feature. Every claim below was verified directly against the source code as it exists in the repository at the time of writing, not against the original project plan.

## 5.1 Authentication and Registration

**Files:** `src/Controllers/AuthController.php`, `src/Services/AuthService.php`, `src/Services/AuthRateLimitService.php`, `src/Repositories/UserRepository.php`, `frontend/pages/auth/*.php`, `frontend/js/auth.js`.

Registration (`POST register`) validates the submitted fields, hashes the password with `password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])`, inserts the user, and sends a verification email built from `backend/storage/email-templates/verify-email.html`. Login (`POST login`) first consults `AuthRateLimitService` — a token-bucket limiter backed by the `rate_limits` table (no Redis required) — before checking the password hash; on success it calls `session_regenerate_safe()` and stores the user (with the password field stripped) in the session. Google Sign-In is implemented separately in `GoogleAuthController`/`GoogleAuthService` as described in §4.7.1.

## 5.2 Dashboard

**Files:** `src/Controllers/DashboardController.php`, `src/Services/DashboardService.php`, `src/Repositories/DashboardRepository.php`, `frontend/pages/dashboard/index.php`, `frontend/js/dashboard.js`.

`GET dashboard` renders the page shell; `GET dashboard_data` returns the aggregated JSON payload (follower totals, post counts by status, next 14 days of scheduled posts, recent notifications, revenue summary, active deal count) that `dashboard.js` uses to populate the cards and charts client-side.

## 5.3 Content Planner (Posts)

**Files:** `src/Controllers/PostController.php`, `src/Repositories/PostRepository.php`, `src/Support/PostInputNormalizer.php`, `src/Jobs/PublishPostJob.php`, `frontend/pages/planner/*`, `frontend/js/planner.js`.

The planner exposes both a calendar view (`GET posts_calendar`) and a filterable/sortable list view (`GET posts`, supporting `status`, `platform`, `date_from`/`date_to`, full-text `search`, and a whitelisted `sort`/`dir` pair — never raw column names from the client). `POST create_post` runs input through `PostInputNormalizer` before `PostRepository::create()` inserts the post plus its `post_media`/`post_tags` join rows inside a transaction; if a `scheduled_at` value is present, a `PublishPostJob` is queued. `update_post`, `delete_post` (soft delete via `is_deleted`), `duplicate_post`, and `bulk_posts` round out the CRUD surface.

**Background publishing:** `PublishPostJob` (dequeued by the webhook mechanism in §4.14) calls `SocialApiService::publish()` once per selected platform on the post, using the correct platform-specific flow — e.g. Instagram's two-step container-then-publish process against `graph.instagram.com/v25.0`, or YouTube's resumable upload endpoint — and records a `platform_post_results` row per attempt, updating the post's overall `status` to `published` or `failed` accordingly.

## 5.4 Instagram Business Login Integration

**Files:** `src/Controllers/InstagramOAuthController.php`, `src/Services/InstagramOAuthService.php`, `ig-cb.php` (root-level standalone callback).

Implements Meta's Instagram Business Login (Graph API v25): the authorize URL requests the `instagram_business_basic`, `instagram_business_manage_messages`, `instagram_business_manage_comments`, `instagram_business_content_publish`, and `instagram_business_manage_insights` scopes. On callback, the short-lived token returned by Instagram is exchanged for a long-lived token (`ig_exchange_token`), the connected account's profile (`id`, `username`, `followers_count`) is fetched and stored (encrypted) in `social_accounts`, and a `fetch_analytics` job is immediately queued so the dashboard reflects the new connection without waiting for the next scheduled sync. The OAuth `state` design for this integration specifically is documented in §4.7.3.

## 5.5 YouTube and TikTok Integrations

**Files:** `src/Controllers/YoutubeOAuthController.php`/`YoutubeOAuthService.php`, `src/Controllers/TiktokOAuthController.php`/`TiktokOAuthService.php`, `src/Services/SocialApiService.php`.

YouTube uses Google's standard OAuth 2.0 Web flow and exposes token refresh (needed because YouTube access tokens are short-lived). TikTok's connect flow adds a PKCE `code_verifier`/`code_challenge` pair on top of the state check, per TikTok's OAuth 2.0 requirements. Publishing and analytics retrieval for both platforms, and for X/Twitter (which currently uses a manually entered bearer token rather than an interactive OAuth flow — see Chapter 3, FR8), are centralised in `SocialApiService`, which each platform-specific OAuth service's `publish()`/`getAnalytics()` methods delegate to.

## 5.6 Analytics

**Files:** `src/Controllers/AnalyticsController.php`, `src/Repositories/AnalyticsRepository.php`, `src/Services/AnalyticsIntelligenceService.php`, `src/Services/CreatorScoreService.php`, `src/Support/AnalyticsReportHelper.php`, `src/Jobs/FetchAnalyticsJob.php`, `src/Jobs/FetchPostPerformanceJob.php`, `frontend/pages/analytics/*`, `frontend/js/analytics.js`.

`AnalyticsIntelligenceService::ensureFresh()` recomputes and caches insights/predictions when stale; `getInsights()` and `getPredictions()` read from the cache tables. Insight generation (`generateInsights()`) covers, among others, a declining-engagement-streak detector, a posting-frequency-drop detector, and a weekend-vs-weekday engagement comparison — each implemented as its own private method operating over `analytics_snapshots` rows. Predictions (`predict()`) use either a simple moving average (`averageDailyDelta()`) or an ordinary least-squares linear regression (`linearRegression()`) over the recent daily series (`recentDailySeries()`), storing the result with the number of snapshots it was based on for transparency. `CreatorScoreService::getScores()` derives a 0–100 growth/engagement/consistency/creator composite score and a per-platform health classification (`computePlatformHealth()`) from the same history. All of this runs as plain PHP arithmetic over MySQL query results — no external AI/ML service is used, consistent with the project's InfinityFree-compatibility constraint.

## 5.7 Deal Management (Creator CRM)

**Files:** `src/Controllers/DealController.php`, `src/Repositories/DealRepository.php`, `src/Support/DealWorkflowHelper.php`, `frontend/pages/monetization/deals.php`, `frontend/js/deals.js`.

Deals move through a six-stage pipeline (`lead` → `negotiation` → `contract` → `active` → `completed`/`cancelled`) rendered as a Kanban board. `update_deal_status` (`DealController::updateStatus()`) is handled by `DealWorkflowHelper`, which — beyond the plain status update — writes an `audit_logs` row and creates a "Deal moved to …" notification, giving the pipeline a lightweight audit trail without a dedicated history table. Deals link to the posts that fulfil them via the `deal_posts` join table, and deletion is a soft delete (`is_deleted`).

## 5.8 Invoice Generation

**Files:** `src/Controllers/InvoiceController.php`, `src/Repositories/InvoiceRepository.php`.

Invoices carry a JSON `line_items` array, a `tax_rate`/`tax_amount`, and a `status` workflow (`draft`→`sent`→`paid`/`overdue`/`cancelled`), optionally linked to a `deal_id`. `markPaid()` additionally creates an "Invoice paid" notification and feeds into the `analytics.total_revenue` rolling figure. Currency defaults to TZS at the database column level, matching the project's East African target market. As noted in Chapter 3 (FR22), the `pdf_url` column exists but no PDF-generation code path populates it in the current version — invoices are currently viewed and managed in-app only.

## 5.9 Media Library

**Files:** `src/Controllers/MediaController.php`, `src/Repositories/MediaFileRepository.php`, `src/Support/MediaUploadHelper.php`, `frontend/pages/media/*`, `frontend/js/media.js`, `frontend/js/media-library.js`.

Covered in detail in §4.11. Deletion (`delete_media`) removes both the physical file and the database row.

## 5.10 Notifications

**Files:** `src/Controllers/NotificationController.php`, `src/Repositories/NotificationRepository.php`, `src/Repositories/NotificationPreferenceRepository.php`, `src/Services/NotificationService.php`, `frontend/js/notifications.js`.

An in-app feed (`notifications_data`) with an unread badge count (`notifications_count`, polled to update the bell icon), mark-read/mark-all-read/delete/delete-read-only actions, and a per-event-type email preference toggle (`notification_prefs`/`update_notification_prefs`) stored in `notification_preferences`. Email templates are shared HTML files under `backend/storage/email-templates/`.

## 5.11 Settings

**Files:** `src/Controllers/SettingsController.php`, `src/Support/SettingsPageHelper.php`, `src/Repositories/UserPreferencesRepository.php`, `src/Repositories/UserSessionRepository.php`, `frontend/pages/settings/*`, `frontend/js/settings.js`.

Four sub-areas share one controller: **Profile** (name/username/bio/website/avatar), **Security** (password change, list/revoke active sessions via `UserSessionRepository`), **Integrations** (connect/disconnect platform, surfaced via `integrations_data`), and **Preferences** (theme, language, currency, date format — persisted per-user in `user_preferences`).

## 5.12 Administration

**Files:** `src/Controllers/AdminUserController.php`, `src/Services/AdminService.php`, `src/Services/PlatformApiSecretsService.php`, `src/Repositories/AuditLogRepository.php`, `frontend/pages/settings/admin-users.php`, `frontend/js/admin-users.js`.

Admin has its own four pages — Dashboard, Users, Settings, Security — each gated by the `role:admin` middleware and structurally barred from every creator-facing content route by `non_admin`. User management (`usersIndex`/`usersStore`/`usersUpdate`/`usersDestroy`/`usersVerify`) covers create/edit/delete/verify with password-reset support. **Platform API credentials** (Meta, TikTok, YouTube app IDs/secrets) are configured through the same Admin Settings page (`settingsPage()`/`settingsUpdate()`, route `admin_update_settings`) and stored, encrypted, via `PlatformApiSecretsService` — the project's internal feature map document describes this as a separate "platform credentials" page/route, but the current codebase folds it into Admin Settings rather than a dedicated screen, which this report records accurately rather than repeating the stale description. `auditLogsIndex()` and `securityActivity()` expose the `audit_logs` table and repeated-failed-login activity respectively, and `integrationTest()` lets an administrator verify a configured platform credential actually authenticates against the live API.

## 5.13 Tags

**Files:** `src/Controllers/TagController.php`, `src/Repositories/TagRepository.php`.

A small, user-scoped, colour-coded label system (`tags`, unique per user by name) attached to posts via `post_tags`; used for filtering in the planner list view.

## 5.14 Frontend: CSS, JavaScript, and Templates

The frontend is deliberately framework-free: 14 per-feature stylesheets (`frontend/css/*.css` — e.g. `dashboard.css`, `planner.css`, `analytics.css`, `monetization.css`, plus shared `main.css`, `layout.css`, `components.css`, `animations.css`, `dark-mode.css`) and 14 page-specific vanilla JavaScript modules (`frontend/js/*.js`, one per feature area, e.g. `dashboard.js`, `planner.js`, `analytics.js`, `deals.js`, `invoices.js`, `admin-users.js`, plus a shared `utils.js` providing `escapeHtml()`, date formatting, and a query-parameter helper) communicate with the JSON API purely via `fetch()`. Fonts (Inter, Playfair Display, JetBrains Mono) and Chart.js are self-hosted rather than loaded from a CDN, a deliberate choice for reliability on the lower-bandwidth connections common among the platform's target East African users (see Chapter 2's discussion of existing tools' USD/CDN-dependent design). Pages under `frontend/pages/` are thin PHP wrappers that `require` a shared layout and `file_get_contents()` a sibling `.html` shell fragment, which the page's JavaScript module then populates.

## 5.15 Security Measures (Implementation-Level Summary)

The security properties described architecturally in §4.9 were independently verified for this report through direct source inspection, corresponding to two concrete pieces of evidence: (1) a project-wide grep for raw SQL string concatenation found none — every data-carrying query in every Repository class uses named PDO parameters; (2) a project-wide sample of `innerHTML` assignments across the JavaScript modules found every one routed through `Utils.escapeHtml()` or `esc()`. Two genuine issues were identified and addressed during this review: an unauthenticated deployment-diagnostics script (`public/verify-deployment.php`) that disclosed database host/user and per-table row counts to any visitor — since removed — and the Instagram OAuth state design discussed in §4.7.3, which remains a documented, deliberate trade-off rather than an oversight.

## 5.16 Error Handling and Logging

`backend/core/error_handler.php` registers a global error/exception handler; unhandled errors are logged (`@file_put_contents`, best-effort — shared hosting does not guarantee arbitrary write locations) rather than displayed to the browser, except when `APP_DEBUG=true` is explicitly set for local troubleshooting, matching the project's documented "Set APP_DEBUG=true to diagnose 500 errors" operational procedure. `backend/core/mailer.php` similarly logs send failures without interrupting the request. Job failures are recorded per-row in `job_queue.error_message` with an attempt counter, rather than only in a log file, so failed background work remains visible and retryable from the database itself.
# CHAPTER 6. TESTING

## 6.1 Testing Strategy

CreatorzHive combines **automated testing** (PHPUnit 9.6, `tests/unit/` and `tests/integration/`) with **manual functional and deployment testing**. The automated suite totals **70 test methods across 18 test files** (7 unit test files, 11 integration test files — one per major controller), executed via `./vendor/bin/phpunit` against a dedicated test database (`DB_DATABASE_TEST`, falling back to the main development database if unset) and a shared `Tests\Support\IntegrationTestCase` base class that boots the application container once per test run.

### Table 6.1 — Automated Test Coverage by File

| File | Test count | Focus |
|---|---:|---|
| `tests/integration/AuthControllerTest.php` | 16 | Registration validation (weak password, duplicate email, admin-role rejection, invalid username format), login (valid/invalid credentials, username-as-identifier, inactive account, unverified email), logout, session-fingerprint mismatch, forgot/reset-password OTP flow including cooldown and wrong-OTP feedback |
| `tests/unit/ValidatorTest.php` | 6 | `required`, `email`, `min`, `max` validation rules |
| `tests/integration/ApiMetaControllerTest.php` | 5 | API metadata/introspection endpoints |
| `tests/integration/PostControllerTest.php` | 5 | Post CRUD and validation |
| `tests/unit/AuthServiceTest.php` | 5 | Password hashing/verification, token generation |
| `tests/unit/GoogleAuthServiceTest.php` | 4 | Google OAuth URL construction and profile handling |
| `tests/integration/NotificationControllerTest.php` | 4 | Notification feed, mark-read behaviour |
| `tests/integration/AdminUserControllerTest.php` | 3 | Admin user management and role enforcement |
| `tests/unit/SchedulerServiceTest.php` | 3 | Scheduling/queueing logic |
| `tests/unit/SocialAccountTokenTest.php` | 3 | Encrypted token storage/retrieval round-trip |
| `tests/unit/PlatformApiSecretsTest.php` | 3 | Encrypted platform credential storage |
| `tests/integration/SettingsControllerTest.php` | 3 | Profile/settings update behaviour |
| `tests/integration/DealControllerTest.php` | 2 | Deal creation and status transitions |
| `tests/integration/InvoiceControllerTest.php` | 2 | Invoice creation and status workflow |
| `tests/integration/MediaControllerTest.php` | 2 | Media upload validation |
| `tests/unit/InstagramOAuthTest.php` | 5 | Instagram `authorizeUrl()` construction (scopes, redirect URI, opaque state pass-through) |
| `tests/integration/AnalyticsControllerTest.php` | 1 | Analytics data endpoint |
| `tests/integration/TagControllerTest.php` | 1 | Tag creation |

## 6.2 Manual Functional Testing

Beyond the automated suite, each feature area was manually exercised end-to-end in a browser against a local development environment: registration and email verification, login (including Google Sign-In), post creation with media attachment and scheduling, the calendar and list planner views, deal pipeline drag-through, invoice creation and payment marking, media upload/delete, notification read/unread behaviour, and the full settings surface (profile, security/session revocation, integrations, preferences).

## 6.3 Authentication and Session Testing

Covered both automatically (see Table 6.1, `AuthControllerTest`) and manually: verified that a session fingerprint mismatch (different user agent mid-session) requires re-authentication, that revoking a session via Settings → Security actually invalidates it, and that the rate limiter blocks further login attempts after repeated failures within its window.

## 6.4 Instagram / OAuth Login Testing

Each of the four OAuth-style integrations (Google, Instagram, TikTok, YouTube) was manually tested against the corresponding platform's real sandbox/developer credentials, confirming: correct redirect to the platform's consent screen, correct handling of a denied/cancelled consent, correct `state` validation rejecting a forged or expired value, and correct account linkage on a successful callback. The Instagram flow specifically required additional, repeated live testing against the actual InfinityFree deployment during development, because its session-independent design (§4.7.3) could not be fully validated in a local environment that does not reproduce InfinityFree's specific shared-hosting session behaviour — this is recorded as a real testing limitation rather than treated as fully resolved by unit tests alone.

## 6.5 Security Testing

A dedicated, focused security review was carried out covering: SQL injection (grep-based audit of every Repository query plus manual review of the generic `insert()`/`update()`/`delete()` query builder), stored/reflected XSS (server-side escaping and a full sample of client-side `innerHTML` call sites), CSRF (confirming every POST route carries the `csrf` middleware tag), session fixation/hijacking, authentication/authorization bypass (confirming role checks are server-side, not client-only), path traversal and local/remote file inclusion, dangerous PHP function usage (`eval`, `exec`, `shell_exec`, etc. — none found outside legitimate `PDO::exec()` calls), a malware/backdoor sweep (obfuscated code, unexpected external scripts, hidden files — none found), security headers, and a Composer dependency vulnerability check (`composer audit` — no advisories). Two issues were found and are documented in §5.15 and Chapter 4, §4.7.3/§4.9.

## 6.6 Error and Edge-Case Testing

Automated tests specifically exercise negative paths (duplicate email, weak password, wrong password, wrong OTP, inactive/unverified account) rather than only the success path, and the error handler's production-mode behaviour (suppressing detailed error output unless `APP_DEBUG=true`) was manually confirmed by triggering a deliberate database-connection failure.

## 6.7 Performance Testing

Performance was assessed informally against the realistic shared-hosting targets documented for the InfinityFree deployment (dashboard load 2–3 seconds, API responses 1–2 seconds, background jobs completing within the ~60-second webhook execution window) rather than formal load testing, which was outside the scope and budget of this project. Query-level performance was addressed at the design stage through indexing (every foreign key and frequently filtered column — `user_id`, `status`, `platform`, date fields — is indexed; `posts` and `deals` additionally carry full-text indexes for search).

## 6.8 Browser Testing

Manually verified in current versions of Google Chrome, Mozilla Firefox, and Microsoft Edge on desktop, and Chrome on Android, covering both the light and dark theme variants of the interface.

## 6.9 InfinityFree Deployment Testing

The application was deployed to a live InfinityFree account (`creatorzhive.infinityfree.io`) and tested against the checklist in `docs/reference/infinityfree-compatibility-report.md`: page loads without errors, email/password and Google login, dashboard data population, media upload, social account connection (all four platforms), webhook-triggered background job processing, transactional email delivery, and admin panel access. This live-environment testing is what surfaced the Instagram-specific session-continuity issue described in §4.7.3 and Chapter 3's risk analysis — a concrete example of an issue that only manifested in the target hosting environment and could not have been caught by local testing or the automated suite alone.

## 6.10 Testing Results Summary

| Area | Result |
|---|---|
| Automated PHPUnit suite | 70 tests across 18 files exercising authentication, validation, admin, posts, notifications, deals, invoices, media, tags, analytics, OAuth URL construction, encrypted token round-trips, and scheduling logic |
| Manual functional walkthrough | All documented features in Chapter 3, §3.2.1 (excluding the three explicitly unimplemented items) confirmed working end-to-end |
| Security review | No SQL injection, no malware/backdoors, consistent CSRF/XSS coverage; one information-disclosure issue found and fixed, one documented OAuth design trade-off |
| Live InfinityFree deployment | Confirmed functional per the compatibility checklist, with the Instagram OAuth session-continuity constraint identified and worked around during this phase |
# CHAPTER 7. DEPLOYMENT

## 7.1 Local Installation

1. `composer install` — installs `phpmailer/phpmailer` and (in development) `phpunit/phpunit`.
2. `cp .env.example .env` and configure database, mail, and OAuth credentials.
3. `php scripts/migrate.php` — applies `database/schema.sql` to a local MySQL database.
4. Point a web server's document root at the project root (Apache with `mod_rewrite`), or run `composer serve` (`php -S 127.0.0.1:8080 -t . dev-server-router.php`) for a quick local server.
5. `./vendor/bin/phpunit` — runs the automated test suite.

## 7.2 Server Requirements

| Requirement | Version / Notes |
|---|---|
| PHP | 7.4 or later |
| MySQL | 8.0 (developed and tested against) |
| Web server | Apache with `mod_rewrite` enabled |
| Required PHP extensions | PDO, pdo_mysql, cURL, JSON, OpenSSL, mbstring, filter, GD |
| Folder permissions | `public/uploads/` must be writable by the web server process |
| Composer | Used locally to build `vendor/`; not required on the server itself |

## 7.3 Database Import

Import `database/schema.sql` via phpMyAdmin (or `mysql < database/schema.sql` where CLI access exists) — this creates all 22 base tables in one step, with no separate seed file required. The 3 tables added by later migrations (`insights_cache`, `prediction_cache`, `creator_scores`, `post_performance` — across `database/migrations/2026_07_08*.sql`) must be imported as a second, separate step, since they were added after the primary schema export and are documented as "run manually via phpMyAdmin" in their own file headers, reflecting the same no-SSH/no-migration-runner constraint described below.

## 7.4 Configuration

Copy `.env.example` to `.env` and set, at minimum: `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, the four `DB_*` values, `APP_SECRET` (a random 32+ byte value — the application refuses to start in production without one), `SESSION_SECURE=true`, and `WEBHOOK_SECRET`. Platform integration credentials (`INSTAGRAM_APP_ID`/`_SECRET`, `GOOGLE_CLIENT_ID`/`_SECRET`, `TIKTOK_CLIENT_KEY`/`_SECRET`) may be set either in `.env` or through the Admin → Settings UI, which stores them encrypted via `PlatformApiSecretsService` — the two mechanisms are interchangeable, with `.env` taking precedence when both are set.

## 7.5 Composer / Dependency Installation

Because the target hosting environment (InfinityFree) provides no SSH access, `composer install --no-dev --optimize-autoloader` must be run **locally**, and the resulting `vendor/` directory (a few megabytes, given the project's minimal dependency footprint — just PHPMailer at runtime) is uploaded alongside the application code via FTP, rather than run on the server itself.

## 7.6 InfinityFree Deployment — Step by Step

This procedure reflects `docs/guides/INFINITYFREE_SETUP.md` and the actual `.env`/`.htaccess` configuration observed in the deployed codebase:

1. **Create the database** via the InfinityFree control panel's MySQL Manager; note the generated host (e.g. `sql105.infinityfree.com`), database name, username, and password.
2. **Upload files via FTP** directly into `/htdocs/` — not into a subfolder — including the pre-built `vendor/` directory, which may take 10–30 minutes given its size.
3. **Create `.env`** in `/htdocs/` with the InfinityFree database credentials and a production-appropriate configuration (see §7.4).
4. **Import `database/schema.sql`**, then the migration files, via phpMyAdmin.
5. **Run `public/setup.php`** (a one-time, web-based setup wizard, restricted to `SETUP_ALLOWED_IPS`/localhost by default) to run migrations and create the first administrator account — then **delete `public/setup.php` from the server** once setup is confirmed, per its own on-screen instruction.
6. **Configure the background-job trigger**: register the deployed `webhook/process-jobs.php` URL (with its shared secret) as an HTTP(s) monitor in a free external uptime service such as UptimeRobot, at the shortest interval the free tier allows (typically 5 minutes).
7. **Verify the installation**: homepage loads, login works, dashboard renders, and Settings → Integrations shows the expected connection status for each configured platform.
8. **Optionally configure social integrations** (Instagram Business Login, YouTube/Google OAuth, TikTok) by registering a developer app on each platform with the correct callback URL and entering the resulting credentials via Admin → Settings.

## 7.7 Troubleshooting

| Symptom | Cause / Fix |
|---|---|
| `FATAL: APP_SECRET is not configured in production` | Add a strong `APP_SECRET` value to `.env` (the application deliberately refuses to boot without one in production) |
| Database connection error | Confirm `DB_HOST` matches the InfinityFree-assigned host (not `localhost`/`127.0.0.1`) and that credentials match the control panel exactly |
| All pages return 404 | `.htaccess` was not uploaded, or `mod_rewrite` is disabled — re-upload/verify |
| Internal Server Error with no message | Temporarily set `APP_DEBUG=true` to surface the underlying error, then revert it once diagnosed |
| Background jobs never run | Confirm the external uptime monitor is actually calling the webhook URL with the correct `WEBHOOK_SECRET`; use the webhook script's own `?details=1`/`?diagnose=1` diagnostic query parameters to inspect the queue directly |
| File uploads fail | Confirm `public/uploads/.htaccess` is present (it both enables uploads and blocks PHP execution within that directory) |
| Instagram connection intermittently fails or drops the session | A known, documented shared-hosting session-continuity constraint — see Chapter 4, §4.7.3; confirm `ig-cb.php` is uploaded and matches the configured `INSTAGRAM_OAUTH_REDIRECT_URI` |
# CHAPTER 8. MAINTENANCE

## 8.1 Future Improvements

The project's own roadmap document (`docs/roadmap/project-roadmap.md`) records a prioritised backlog. Reviewing it against the current codebase for this report found that several items originally listed as pending have, in fact, already been completed during subsequent development — corrected below rather than repeated uncritically:

| Roadmap item | Status found in current codebase |
|---|---|
| Validate OAuth `state` against session | Done for Google, TikTok, and YouTube; deliberately not applied to Instagram — see Chapter 4, §4.7.3 |
| Use `finfo_file()` for upload MIME validation instead of trusting `$_FILES['type']` | Done — confirmed in `MediaController::upload()` |
| Database session handler | Done — `backend/core/db-session-handler.php`, storing sessions in the `sessions` table |
| `APP_SECRET` enforcement in production | Done — `backend/index.php` fatally refuses to boot without one |
| PDF invoice generation | **Still pending** — no code path populates `invoices.pdf_url` |
| Twitter/X full OAuth flow | **Still pending** — token-based connection only |
| Full TikTok video upload (beyond inbox init) | Partially addressed — TikTok OAuth connect flow (with PKCE) is implemented; the roadmap's broader "video upload support" scope was not independently verified for this report |
| Team/agency multi-user accounts | **Still pending** |
| AI content/caption suggestions | **Still pending** — no AI/ML service integration exists in the codebase |
| Content-Security-Policy header | **Still pending** — deliberately deferred pending confirmation it will not break the four OAuth redirect flows |

Items genuinely still open and recommended as next priorities, in order of value relative to effort: (1) PDF invoice generation, since the schema already reserves a column for it; (2) a period-over-period analytics comparison view, building directly on the existing `analytics_snapshots` history; (3) a full Twitter/X OAuth flow, to remove the last manually-entered token requirement; (4) a report-only Content-Security-Policy header as a low-risk first step toward eventually enforcing one.

## 8.2 Possible New API Integrations

Beyond the roadmap's own long-term items (LinkedIn, Pinterest, a brand marketplace), the existing `SocialProviderInterface` contract means any new platform integration can be added by implementing that interface and registering it in `AppServiceProvider`, without changing the Controller, routing, or job-queue layers — the architecture was deliberately built to make this addition path narrow and self-contained.

## 8.3 Code Maintainability

The Controller/Service/Repository separation (Chapter 4, §4.1) means a bug fix or feature change is normally confined to one layer: a validation change touches a Controller or a `Support` normalizer class; a business-rule change touches a Service; a query change touches a Repository. The `backend/compat/*.php` global-function bridge layer is explicitly documented (in its own `README.md`) as legacy — new code should depend on the DI container directly rather than adding to it. The PHPUnit suite (Chapter 6) provides a regression safety net for the modules it covers, though its 70 tests do not yet cover every controller (e.g. `SettingsController`'s integrations sub-area and the OAuth callback controllers themselves have no automated integration test — OAuth flows are exercised manually against live platform sandboxes instead, as automated testing of a real third-party consent screen is impractical).

## 8.4 Backup Strategy

The project's InfinityFree deployment relies on the control panel's own database export (phpMyAdmin) for backups; no automated backup job exists in the codebase itself. Given the shared-hosting constraint of no cron/SSH access (Chapter 3, §3.5), a realistic near-term improvement would be a scheduled job (triggered via the same webhook mechanism used for post-publishing and analytics) that exports and emails or uploads a compressed schema+data dump on a regular interval — this is recommended in this report as a concrete next step rather than assumed to already exist.

## 8.5 Security Updates

Composer dependencies should be periodically re-checked with `composer audit` (used during this report's security review, which found no advisories against the current `phpmailer/phpmailer` 6.12.0). `APP_SECRET` rotation is documented as requiring re-encryption of stored OAuth tokens (`scripts/encrypt-social-tokens.php` exists specifically for this purpose) rather than a simple `.env` value swap, since the same secret is used both to sign CSRF/OAuth-state values and to encrypt tokens at rest.

## 8.6 Versioning

The project is tracked in Git, with descriptive, single-purpose commit messages per change (visible directly in the repository history) rather than a formal semantic-versioning release scheme — appropriate for a single-deployment student/early-stage product rather than a distributed package with external consumers.

---

# CONCLUSION

This project set out to design and develop CreatorzHive, a web-based content management platform addressing the fragmentation independent social media influencers face when managing multiple platforms, tracking performance, and running their creator business without enterprise-grade (and enterprise-priced) tooling. The literature review in Chapter 2 established a clear gap between existing tools — priced and designed for marketing agencies — and the actual needs of an individual creator; Chapters 3 through 7 documented how the resulting system closes that gap in practice: a working authentication system (including Google Sign-In), four social platform integrations of varying depth (full interactive OAuth for Google/YouTube/TikTok, a deliberately session-independent OAuth design for Instagram to accommodate a real shared-hosting constraint, and a token-based connection for X/Twitter), a content planner with calendar and list views and asynchronous multi-platform publishing, a historical analytics engine that generates plain-language insights and simple statistical predictions from recorded snapshots rather than only showing current totals, a brand-deal Kanban CRM, a TZS-denominated invoice generator, a media library, an in-app notification system, and an administrator panel — all running within the real technical constraints of free InfinityFree shared hosting (no SSH, no reliable cron, no Redis), and independently verified through both a 70-test automated PHPUnit suite and a dedicated security review.

The project also surfaced genuine engineering trade-offs rather than a uniformly "solved" system: three planned features (PDF invoice export, full Twitter/X OAuth, team/agency accounts) remain unimplemented and are reported as such rather than glossed over; the Instagram OAuth design represents a considered, documented compromise between security purity and a real hosting-platform limitation rather than an oversight; and the testing chapter records that certain issues (the Instagram session-continuity behaviour) were only discoverable through live deployment testing, not local development or unit tests alone. Reporting these limitations honestly is, in the authors' view, as important a deliverable of a systems-analysis-and-design capstone project as the working features themselves.

Overall, the findings support the original objectives set out in Chapter 1: CreatorzHive demonstrates that an affordable, creator-focused, single-dashboard alternative to enterprise social media management tools is technically achievable on free shared hosting, without sacrificing core security practices (parameterised queries throughout, consistent CSRF/XSS protection, encrypted token storage, bcrypt password hashing). For the project team, it has been a substantial applied exercise in systems analysis, secure web application design, and the practical realities of deploying and operating software under real infrastructure constraints — and a foundation the team believes is worth continuing to build on, guided by the concrete, code-verified priorities set out in this chapter.
# REFERENCES

Abidin, C. (2021). Influencer fatigue and burnout: Realities of the creator economy. *Journal of Digital Social Research*, 3(1), 41–67.

Bishop, S. (2021). Managing visibility on YouTube through algorithmic gossip. *New Media & Society*, 23(11), 3211–3227.

Buffer. (2023). *Buffer pricing*. Retrieved from https://buffer.com/pricing

Duffy, B. E. (2017). *(Not) getting paid to do what you love: Gender, social media, and aspirational work*. Yale University Press.

Hootsuite. (2023). *Hootsuite pricing and plans*. Retrieved from https://hootsuite.com/plans

Influencer Marketing Hub. (2023). *The state of influencer marketing 2023: Benchmark report*. Retrieved from https://influencermarketinghub.com

Norman, D. A. (2013). *The design of everyday things* (Revised ed.). Basic Books.

Quesenberry, K. A. (2020). *Social media strategy: Marketing, advertising, and public relations in the consumer revolution* (3rd ed.). Rowman & Littlefield.

**Technical and official platform references:**

The PHP Group. (n.d.). *PHP manual*. Retrieved from https://www.php.net/manual/en/

Oracle Corporation. (n.d.). *MySQL 8.0 reference manual*. Retrieved from https://dev.mysql.com/doc/refman/8.0/en/

The Apache Software Foundation. (n.d.). *Apache HTTP Server documentation — mod_rewrite*. Retrieved from https://httpd.apache.org/docs/current/mod/mod_rewrite.html

Meta Platforms, Inc. (n.d.). *Instagram platform — Instagram API with Instagram Login*. Meta for Developers. Retrieved from https://developers.facebook.com/docs/instagram-platform

Meta Platforms, Inc. (n.d.). *Meta Graph API documentation*. Meta for Developers. Retrieved from https://developers.facebook.com/docs/graph-api

Google LLC. (n.d.). *Using OAuth 2.0 to access Google APIs*. Google Identity. Retrieved from https://developers.google.com/identity/protocols/oauth2

Google LLC. (n.d.). *YouTube Data API (v3) reference*. Retrieved from https://developers.google.com/youtube/v3

TikTok Pte. Ltd. (n.d.). *TikTok for Developers — Login Kit and Content Posting API documentation*. Retrieved from https://developers.tiktok.com/doc/overview

Hardt, D. (Ed.). (2012). *The OAuth 2.0 authorization framework* (RFC 6749). Internet Engineering Task Force. Retrieved from https://datatracker.ietf.org/doc/html/rfc6749

Composer. (n.d.). *Composer documentation*. Retrieved from https://getcomposer.org/doc/

PHPMailer Project. (n.d.). *PHPMailer documentation*. Retrieved from https://github.com/PHPMailer/PHPMailer

Sebastian Bergmann and Contributors. (n.d.). *PHPUnit documentation*. Retrieved from https://docs.phpunit.de/

MDN Contributors. (n.d.). *HTML, CSS, and JavaScript reference*. MDN Web Docs, Mozilla. Retrieved from https://developer.mozilla.org/en-US/docs/Web

InfinityFree. (n.d.). *InfinityFree knowledge base*. Retrieved from https://forum.infinityfree.com/
# APPENDICES

## Appendix A — Folder Structure

```
creatorzhive/
├── src/                    OOP business logic (production code, CreatorzHive\ namespace)
│   ├── Config/             AppConfig — loads .env, exposes config values
│   ├── Contracts/          SocialProviderInterface
│   ├── Controllers/        HTTP request handlers
│   ├── Core/               Application, Container, Router, Database\Connection, Security\TokenCrypto
│   ├── Helpers/            PlatformHelper
│   ├── Jobs/               PublishPostJob, FetchAnalyticsJob, FetchPostPerformanceJob,
│   │                       SendNotificationJob, CleanupMediaJob
│   ├── Middleware/         AuthMiddleware, CsrfMiddleware, RoleMiddleware
│   ├── Providers/          AppServiceProvider (DI wiring)
│   ├── Repositories/       One class per table/domain (14 files)
│   ├── Services/           Business logic (16 files)
│   └── Support/            MediaUploadHelper, PostInputNormalizer, DealWorkflowHelper,
│                           SettingsPageHelper, UserPayloadFormatter, AnalyticsReportHelper
├── backend/                Procedural front controller + compatibility layer
│   ├── index.php           Entry point — APP_SECRET check, bootstrap, security headers
│   ├── routes/{web,api}.php
│   ├── compat/{models,services,auth}.php   ~90 global functions bridging to src/
│   ├── core/                session.php, db-session-handler.php, router.php, mailer.php, ...
│   └── storage/{email-templates,logs,uploads}/
├── frontend/
│   ├── js/                 14 page-specific modules + utils.js
│   ├── css/                14 stylesheets
│   ├── pages/              PHP + HTML templates (auth, dashboard, planner, analytics,
│   │                       monetization, media, settings, notifications, errors, partials)
│   ├── components/         Shared navbar/sidebar HTML fragments
│   ├── fonts/               Self-hosted Inter, JetBrains Mono, Playfair Display
│   └── assets/              icon.svg, self-hosted Chart.js
├── public/                 Web document root
│   ├── index.php           Front controller
│   ├── setup.php           One-time, IP-restricted setup wizard
│   ├── webhook/process-jobs.php   Background job trigger
│   └── uploads/             User-uploaded media (.htaccess disables PHP execution)
├── database/
│   ├── schema.sql           Full base schema (22 tables)
│   └── migrations/          3 incremental migration files (3 additional tables)
├── scripts/                 migrate.php, hash-password.php, encrypt-social-tokens.php,
│                             verify-server.php, download-frontend-vendor.sh
├── tests/                   unit/ (7 files) + integration/ (11 files) + Support/
├── docs/                    guides/, knowledge-base/, reference/, business/, roadmap/
├── vendor/                  Composer dependencies
├── .env / .env.example
├── composer.json
└── .htaccess
```

## Appendix B — Database Schema Summary

See Chapter 4, Tables 4.1–4.5 for the full column-level schema of all 25 tables. Summary by domain:

| Domain | Tables |
|---|---|
| Identity & sessions | `users`, `email_verifications`, `password_resets`, `sessions`, `rate_limits`, `user_preferences` |
| Content & publishing | `posts`, `media_files`, `post_media`, `tags`, `post_tags`, `social_accounts`, `platform_post_results`, `post_performance` |
| Analytics | `analytics`, `analytics_snapshots`, `insights_cache`, `prediction_cache`, `creator_scores` |
| Monetisation | `deals`, `deal_posts`, `invoices` |
| Notifications & operations | `notifications`, `notification_preferences`, `job_queue`, `audit_logs` |

## Appendix C — Sample Table Definitions (from `database/schema.sql`)

```sql
CREATE TABLE `posts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `cover_media_id` int unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `caption` text,
  `platforms` json DEFAULT NULL,
  `status` enum('draft','scheduled','published','failed') NOT NULL DEFAULT 'draft',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_posts_user_status` (`user_id`,`status`,`is_deleted`),
  FULLTEXT KEY `idx_posts_search` (`title`,`content`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`cover_media_id`) REFERENCES `media_files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE `deals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) NOT NULL DEFAULT 'TZS',
  `status` enum('lead','negotiation','contract','active','completed','cancelled') NOT NULL DEFAULT 'lead',
  `deal_type` enum('sponsored_post','affiliate','ambassador','gifted','other') NOT NULL DEFAULT 'sponsored_post',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `idx_deals_search` (`brand_name`,`title`),
  CONSTRAINT `deals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Appendix D — API Endpoint Reference

All endpoints are accessed as `?route=<name>` against the single front controller. Authentication ("Auth") column: **–** = public, **Auth** = requires login, **Admin** = requires the `admin` role; **CSRF** column marks routes additionally protected by the CSRF middleware.

### D.1 Page Routes (`backend/routes/web.php`, GET only)

| Route | Controller::Method | Auth |
|---|---|:---:|
| `home` | `SystemController::home` | – |
| `login` / `register` / `forgot-password` / `reset-password` / `verify-email` / `logout` | `AuthController::*` | – |
| `dashboard` | `DashboardController::index` | Auth |
| `planner` | `PostController::plannerPage` | Auth (non-admin) |
| `analytics` | `AnalyticsController::index` | Auth (non-admin) |
| `deals` | `DealController::index` | Auth (non-admin) |
| `invoices` | `InvoiceController::index` | Auth (non-admin) |
| `media` | `MediaController::index` | Auth (non-admin) |
| `notifications` | `NotificationController::index` | Auth |
| `settings`, `settings-profile`, `settings-security`, `settings-integrations`, `settings-notifications`, `settings-preferences` | `SettingsController::*` | Auth |
| `admin-users`, `admin-settings`, `admin-dashboard`, `admin-security` | `AdminUserController::*` | Admin |
| `google-auth` / `google-callback` | `GoogleAuthController::start` / `callback` | – |
| `instagram-connect` / `instagram-callback` | `InstagramOAuthController::connectStart` / `callbackHandler` | connectStart: Auth |
| `youtube-connect` / `youtube-callback` | `YoutubeOAuthController::connectStart` / `callbackHandler` | connectStart: Auth |
| `tiktok-connect` / `tiktok-callback` | `TiktokOAuthController::connectStart` / `callbackHandler` | connectStart: Auth |
| `privacy-policy` / `terms-of-service` | `SystemController::*` | – |

### D.2 JSON API Routes (`backend/routes/api.php`)

| Route | Method | Controller::Method | Auth | CSRF |
|---|:---:|---|:---:|:---:|
| `register` | POST | `AuthController::register` | – | ✔ |
| `login` | POST | `AuthController::login` | – | ✔ |
| `logout` | POST | `AuthController::logout` | Auth | ✔ |
| `forgot-password` / `reset-password` / `resend-verification` | POST | `AuthController::*` | – | ✔ |
| `check_username` / `verify` | GET | `AuthController::*` | – | – |
| `posts` / `posts_calendar` / `post` | GET | `PostController::index` / `calendar` / `show` | Auth | – |
| `create_post` / `update_post` / `delete_post` / `duplicate_post` / `bulk_posts` | POST | `PostController::*` | Auth | ✔ |
| `upload_media` / `delete_media` | POST | `MediaController::upload` / `delete` | Auth | ✔ |
| `media_list` | GET | `MediaController::list` | Auth | – |
| `tags` | GET | `TagController::index` | Auth | – |
| `create_tag` | POST | `TagController::store` | Auth | ✔ |
| `analytics_data` | GET | `AnalyticsController::data` | Auth | – |
| `seed_analytics` | POST | `AnalyticsController::seed` | Auth | ✔ |
| `deals_data` / `deal` | GET | `DealController::data` / `show` | Auth | – |
| `create_deal` / `update_deal` / `update_deal_status` / `delete_deal` | POST | `DealController::*` | Auth | ✔ |
| `invoices_data` / `invoice` | GET | `InvoiceController::list` / `show` | Auth | – |
| `create_invoice` / `update_invoice` / `mark_invoice_paid` | POST | `InvoiceController::*` | Auth | ✔ |
| `notifications_data` / `notifications_count` | GET | `NotificationController::data` / `unreadCount` | Auth | – |
| `mark_read` / `mark_all_read` / `delete_notification` / `delete_read_notifications` | POST | `NotificationController::*` | Auth | ✔ |
| `profile_data` / `user_sessions` / `integrations_data` / `notification_prefs` | GET | `SettingsController::*` | Auth | – |
| `update_profile` / `update_password` / `revoke_session` / `revoke_all_sessions` / `connect_platform` / `disconnect_platform` / `update_notification_prefs` / `update_preferences` | POST | `SettingsController::*` | Auth | ✔ |
| `dashboard_data` | GET | `DashboardController::data` | Auth | – |
| `admin_users` / `admin_overview` / `admin_audit_logs` / `admin_test_integration` / `admin_security_activity` | GET | `AdminUserController::*` | Admin | – |
| `admin_create_user` / `admin_update_user` / `admin_delete_user` / `admin_verify_user` / `admin_update_settings` | POST | `AdminUserController::*` | Admin | ✔ |
| `ping` | GET | `SystemController::ping` | – | – |
| `db-test` | GET | `SystemController::dbTest` | Admin | – |
| `api_me` / `api_catalog` | GET | `ApiMetaController::*` | Auth | – |

### D.3 JSON Response Envelope

All API responses follow one consistent envelope, produced by `CreatorzHive\Core\Http\JsonResponder`:

```json
// Success
{
  "success": true,
  "message": "Success",
  "data": { }
}

// Error
{
  "success": false,
  "message": "Human-readable error message",
  "errors": { }
}
```

## Appendix E — Environment Variables (`.env.example`, secret values excluded)

| Variable | Purpose |
|---|---|
| `APP_NAME`, `APP_URL`, `APP_BASE_PATH` | Application identity and base URL/path |
| `APP_ENV`, `APP_DEBUG` | Environment mode and error-display toggle |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection |
| `DB_DATABASE_TEST` | Optional separate database for PHPUnit/CI |
| `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | SMTP email delivery (PHPMailer) |
| `SESSION_LIFETIME`, `SESSION_SECURE` | Session cookie lifetime (minutes) and `Secure` flag |
| `WEBHOOK_SECRET` | Shared secret required to trigger background job processing |
| `SETUP_ALLOWED_IPS` | IP allowlist for the one-time `public/setup.php` wizard |
| `APP_SECRET` | Signs CSRF/OAuth-state values and encrypts stored OAuth tokens — required in production |
| `INSTAGRAM_APP_ID`, `INSTAGRAM_APP_SECRET`, `INSTAGRAM_OAUTH_REDIRECT_URI` | Instagram Business Login (Meta Graph API v25) |
| `API_CORS_ORIGINS` | Optional allowed browser Origins for JSON API CORS |
| `SOCIAL_API_MOCK_FALLBACK`, `SOCIAL_FALLBACK_IMAGE_URL` | Development-mode fallback behaviour when a social API token is absent |
| `TIKTOK_CLIENT_KEY`, `TIKTOK_CLIENT_SECRET`, `TIKTOK_OAUTH_REDIRECT_URI`, `TIKTOK_ACCESS_TOKEN`, `TIKTOK_PRIVACY_LEVEL` | TikTok Login Kit / Content Posting API |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_AUTH_REDIRECT_URI` | Google Sign-In and YouTube OAuth |
| `YOUTUBE_ACCESS_TOKEN`, `YOUTUBE_CHANNEL_ID`, `YOUTUBE_PRIVACY_STATUS` | YouTube-specific publishing defaults |
| `TWITTER_BEARER_TOKEN` | X/Twitter token-based connection |

## Appendix F — Important Classes

| Class | Responsibility |
|---|---|
| `CreatorzHive\Core\Application` | Boots the DI container and application lifecycle |
| `CreatorzHive\Core\Container` | Dependency-injection container |
| `CreatorzHive\Core\Database\Connection` | PDO wrapper; the only route to the database |
| `CreatorzHive\Core\Security\TokenCrypto` | Encrypts/decrypts OAuth tokens and platform secrets at rest |
| `CreatorzHive\Middleware\{Auth,Csrf,Role}Middleware` | Request-level access control |
| `CreatorzHive\Services\AnalyticsIntelligenceService` | Growth deltas, insights, and statistical predictions |
| `CreatorzHive\Services\CreatorScoreService` | Composite creator/platform health scoring |
| `CreatorzHive\Services\SocialApiService` | Cross-platform publish/analytics dispatch |
| `CreatorzHive\Support\MediaUploadHelper` | Server-side upload validation and thumbnailing |
| `CreatorzHive\Support\PostInputNormalizer` | Validates/normalises post-creation input |
| `CreatorzHive\Support\DealWorkflowHelper` | Deal status-change side effects (audit log + notification) |

## Appendix G — Key Helper Functions (`backend/helpers/functions.php` and `backend/compat/*`)

| Function | Purpose |
|---|---|
| `load_env()` / `env()` | Loads and reads `.env` values (three-layer `$_ENV`/`getenv()`/`$_SERVER` fallback) |
| `session_start_safe()` / `session_set_user()` / `session_get_user()` | Session lifecycle and current-user access |
| `csrf_generate_token()` / `csrf_validate_post()` | CSRF token issuance and validation |
| `router_get_action()` / `router_post_action()` / `router_dispatch()` | Route registration and dispatch |
| `response_json()` / `response_html()` / `response_redirect()` | HTTP response helpers |
| `job_runner_dispatch()` / `job_runner_run()` | Queue a job / process pending jobs from the queue |
| `platform_api_secrets_resolve()` | Reads a platform credential from `.env` or the encrypted admin-configured store |
