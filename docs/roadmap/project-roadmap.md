# CreatorzHive — Project Roadmap

> **Version:** 1.0 | **Date:** 2026-06-10

---

## Vision Statement

Become the **all-in-one operating system for content creators in East Africa**, expanding to pan-African and global emerging markets. Every tool a creator needs — scheduling, analytics, deals, invoicing, and AI guidance — in one platform designed for their context.

---

## Immediate Improvements (Now — 2 Weeks)

These are quick wins that fix critical issues or unblock other work.

### 1. Security Hardening
- [ ] Set `APP_SECRET` enforcement: fatal error if `production` env and secret is empty
- [ ] Validate Meta OAuth state parameter against `$_SESSION['oauth_state']`
- [ ] Add `php_flag engine off` `.htaccess` to `public/uploads/`
- [ ] Add `Content-Security-Policy` header in `backend/index.php`
- [ ] Use `finfo_file()` instead of `$_FILES['type']` for upload MIME validation

### 2. PHP Version Upgrade
- [ ] Audit and fix PHP 8.x deprecations
- [ ] Update `composer.json` minimum PHP to `^8.1`
- [ ] Update CI to test against PHP 8.1 and 8.2

### 3. Critical Bug Fixes
- [ ] `TokenCrypto::pack()` should throw `RuntimeException` on OpenSSL failure (not silently return `''`)
- [ ] Add `findByIdAndUser(int $id, int $userId)` to all repositories (IDOR protection)

### 4. Developer Experience
- [ ] Add PHPStan level 5 to CI pipeline
- [ ] Add PHP-CS-Fixer with PSR-12 configuration
- [ ] Add `pre-commit` hook example to documentation
- [ ] Document all `.env` variables with descriptions in `.env.example`

### 5. Codebase Cleanup
- [ ] Remove or archive `scripts/oop-*.php` migration scripts
- [ ] Remove empty `backend/controllers/` and `backend/jobs/` placeholder directories
- [ ] Rename `READMimi.md` to a proper README or delete
- [ ] Mark `backend/compat/` files with `@deprecated` PHPDoc

---

## Short-Term Goals (1–3 Months)

These add significant user value and close important feature gaps.

### 1. PDF Invoice Generation
**Priority:** HIGH — Currently PDF URL is stored but not generated.

- Integrate `dompdf/dompdf` or `mpdf/mpdf`
- Create invoice HTML template
- Generate PDF on `POST ?route=create_invoice` or on-demand button
- Store PDF in `public/uploads/invoices/`
- Update `invoices.pdf_url`

**Impact:** Professional invoicing for brand deals. Currently missing feature.

### 2. Twitter/X OAuth Flow
**Priority:** MEDIUM

- Implement Twitter OAuth 2.0 PKCE flow
- Store tokens in `social_accounts` (platform: 'twitter')
- Replace manual token entry requirement

### 3. Platform Analytics Depth
**Priority:** HIGH

- Add period-over-period comparison (this week vs last week)
- Add platform-specific metrics breakdown charts
- Export analytics as CSV
- Top performing posts table

### 4. Notification Email Sending
**Priority:** HIGH — Notification preferences exist but email sending isn't fully wired.

- Wire `email_post_published`, `email_deal_updated`, etc. to actual email sends
- Use `notification-generic.html` template
- Implement weekly summary email (scheduled via cron)

### 5. Media Library Enhancement
**Priority:** MEDIUM

- Image resizing/cropping before upload
- Bulk delete media
- Media search by filename/type
- Storage size indicator

### 6. Post Bulk Operations
**Priority:** MEDIUM

- Bulk delete posts
- Bulk schedule (set scheduled_at for multiple)
- Bulk tag assignment

### 7. Deal Workflow Enhancement
**Priority:** MEDIUM

- Deal contract file attachment (upload PDF)
- Deal history timeline (using audit_logs)
- Deliverables checklist (checkbox items)
- Email brand contact from within deal

### 8. Session Security (DB Handler)
**Priority:** MEDIUM

- Implement custom PHP session handler using the existing `sessions` table
- Enables horizontal scaling and session visibility

---

## Medium-Term Goals (3–6 Months)

These are larger features that expand the platform significantly.

### 1. AI Content Assistant
**Priority:** HIGH — Differentiating feature

- Caption generation from post content
- Hashtag suggestions based on content and platform
- Post performance predictions
- Best posting time recommendations (based on analytics_snapshots data)
- Implementation: Claude API or OpenAI API integration
- New DB tables: `ai_suggestions`, `content_templates`

### 2. Team / Agency Accounts
**Priority:** HIGH — Unlocks agency market

- Team invitations system
- Multi-user per account (owner + team members)
- Role-based team permissions (owner, editor, viewer)
- Client accounts management (agency manages multiple creator accounts)
- New DB tables: `teams`, `team_members`, `team_invitations`

### 3. TikTok OAuth Integration
**Priority:** MEDIUM

- Full TikTok OAuth 2.0 flow
- Video upload support (not just inbox init)
- TikTok analytics (views, likes, shares) via API

### 4. Advanced Deal Pipeline
**Priority:** HIGH

- Email templates for brand outreach
- Deal templates (pre-filled forms for common deal types)
- Revenue forecasting based on pipeline value
- Bulk deal export to CSV
- Integration with Google Drive / Dropbox for contract files

### 5. Enhanced Monetization Tracking
**Priority:** HIGH

- Monthly/yearly revenue charts
- Revenue by platform breakdown
- Revenue by deal type breakdown
- Pipeline value vs actual revenue comparison
- Tax reporting summary

### 6. Mobile-Responsive PWA
**Priority:** HIGH — East Africa is mobile-first

- Full mobile responsive design (current CSS may need overhaul)
- Progressive Web App manifest + service worker
- Offline support for viewing posts/deals
- Mobile-optimized media upload

### 7. Platform-Specific Publishing Settings
**Priority:** MEDIUM

- Per-platform caption (different for IG vs Twitter)
- Per-platform media (different crops for each platform)
- Optimal image dimensions enforcement per platform
- Platform-specific scheduling (Instagram optimal times differ from TikTok)

---

## Long-Term Vision (6–24 Months)

These are strategic features that define CreatorzHive as a platform business.

### 1. Brand Marketplace
**Priority:** HIGH — New revenue stream

A two-sided marketplace connecting brands with creators.

Features:
- Brand campaign brief creation
- Creator discovery (search by niche, location, follower count)
- Creator bid/apply to campaigns
- Platform escrow for deal payments
- Automated deliverable tracking
- Platform fee: 5–10% of deal value

New infrastructure:
- Marketplace listings table
- Creator profile public pages
- Creator portfolio showcase
- Payment processing (M-Pesa, Airtel Money, Stripe)

### 2. Advanced AI Features
**Priority:** HIGH

- Trend detection (analyze what's performing well in creator's niche)
- Content calendar AI generation (suggest posting schedule for the month)
- Competitive analysis (compare performance to industry benchmarks)
- Content repurposing suggestions (turn YouTube to Twitter threads)
- AI-generated invoice drafts from deal terms

### 3. Audience Insights
**Priority:** HIGH

- Audience demographics (age, gender, location) per platform
- Audience growth rate tracking
- Audience overlap analysis across platforms
- Best content types for your audience

### 4. Creator Analytics API
**Priority:** MEDIUM

Public API allowing:
- Brands to pull verified creator analytics
- Third-party tools to integrate CreatorzHive data
- Webhook notifications for post publish events

### 5. LinkedIn Integration
**Priority:** MEDIUM

- LinkedIn OAuth
- Article and post publishing
- Company page management
- LinkedIn analytics

### 6. Content Templates & Brand Kits
**Priority:** MEDIUM

- Branded content templates (colors, fonts, watermarks)
- Template marketplace (share/sell templates)
- Brand kit per creator (logo, colors, fonts)
- Auto-apply brand kit to posts

### 7. Creator Courses & Monetization
**Priority:** LOW (Future)

- Creator learning hub (monetization tips, platform algorithms)
- Affiliate program for CreatorzHive
- Creator certification badges
- NFT/digital product integration

### 8. White-Label for Agencies
**Priority:** MEDIUM

- Custom domain support
- Agency branding (logo, colors)
- Client-facing reports with agency branding
- Reseller program

---

## Technical Infrastructure Roadmap

### Phase 1 (Immediate)
- PHP 8.1 upgrade
- PHPStan + PHP-CS-Fixer in CI
- DB session handler

### Phase 2 (3 months)
- Queue worker daemon (replace cron every minute with persistent worker)
- Redis for caching (session, rate limits, analytics)
- Object storage (S3/MinIO) for media files

### Phase 3 (6 months)
- Horizontal scaling support (stateless app, shared DB + Redis)
- Read replica for analytics queries
- CDN for media delivery
- Application Performance Monitoring (APM)

### Phase 4 (12 months)
- Microservices extraction (publishing engine, analytics engine)
- Separate creator and admin apps
- Real-time notifications (WebSockets)
- Mobile app (React Native)

---

## Success Metrics by Phase

| Phase | MAU | Monthly Revenue | Posts Scheduled | Deals Tracked |
|-------|-----|----------------|----------------|---------------|
| Now | 50 | 0 | 200 | 30 |
| 3 months | 200 | 500K TZS | 2,000 | 150 |
| 6 months | 1,000 | 3M TZS | 15,000 | 800 |
| 12 months | 5,000 | 20M TZS | 100,000 | 5,000 |
| 24 months | 25,000 | 100M TZS | 500,000 | 30,000 |
