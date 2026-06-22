# Claude Code Guidelines — CreatorzHive

This document contains project-specific guidelines for Claude Code when working on CreatorzHive.

---

## 🎯 Core Principles

1. **Always commit after every change** - No exceptions. Use meaningful commit messages with co-author line
2. **Provide InfinityFree deployment instructions** - Every fix must include exact upload paths and testing steps
3. **Preserve architecture** - Don't refactor or redesign; only fix what's broken
4. **Clean code, no comments** - Only comment WHY, not WHAT; code should be self-documenting
5. **Test before considering complete** - Verify the fix works in the deployed environment

---

## 📋 Deployment Context

### Live Environment
- **Platform**: InfinityFree Free Hosting
- **Domain**: https://creatorz.freedev.app
- **Document Root**: `/htdocs/` (entire project at root, NOT in subdirectory)
- **Database Host**: `sql211.infinityfree.com` (remote server)
- **Database Name**: `if0_42095116_creatorz_hive`
- **Database User**: `if0_42095116`
- **FTP Access**: Available, SSH NOT available

### Key Constraints
- ✅ No SSH access (use web endpoints for setup/debugging)
- ✅ No reliable cron (use webhook triggers with UptimeRobot)
- ✅ Open_basedir restriction (all files must be in `/htdocs/`)
- ✅ File-based sessions (Phase 2: implement DB session handler)
- ✅ ~2-3 second page load acceptable for shared hosting

---

## 📤 Deployment Workflow

**After EVERY code change:**

1. **Commit locally**
   ```bash
   git add -A
   git commit -m "Fix: Description of what was fixed
   
   Why this matters and what it changes.
   
   Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>"
   ```

2. **Provide upload instructions** (MUST include in response to user)
   ```
   📤 Upload to InfinityFree:
   - Local: /var/www/html/creatorzhive/[EXACT_PATH]
   - Remote: /htdocs/[EXACT_PATH]
   - Replace: Yes
   ```

3. **Explain the change**
   - What was broken
   - Why it was broken
   - How the fix works
   - How to verify it works

4. **Provide testing instructions**
   ```
   🧪 Test:
   1. Visit: https://creatorz.freedev.app/[PAGE]
   2. Action: [USER_ACTION]
   3. Expected: [EXPECTED_RESULT]
   ```

---

## 🔧 Critical Files (Track These)

| File | Purpose | Notes |
|------|---------|-------|
| `.env` | Database credentials & secrets | Update when deploying database config |
| `backend/index.php` | Entry point bootstrap | APP_SECRET enforcement |
| `backend/bootstrap-oop.php` | OOP layer bootstrap | Calls load_env() |
| `src/Config/AppConfig.php` | Config loader | Self-loads .env if needed |
| `public/setup.php` | One-time deployment wizard | Delete after first use |
| `webhook/process-jobs.php` | Background job trigger | Called by UptimeRobot |
| `setup.php` (root) | Root-level setup proxy | Includes public/setup.php |
| `webhook/` (root) | Root-level webhook proxy | Includes public/webhook/ |
| `.htaccess` (root & public) | URL routing & security | Critical for request handling |

---

## 🐛 Debugging Common Issues

### "Internal Server Error" with no message
1. Set `APP_DEBUG=true` in `.env`
2. Reload browser
3. Error message should now display
4. Clear browser cache (Ctrl+Shift+Delete) if still not showing

### "Connection refused" or DB errors
1. Check `.env` has correct DB credentials
2. Verify `AppConfig::ensureEnvLoaded()` is being called
3. Run `/public/bootstrap-check.php` to test each step
4. Confirm DB_HOST points to `sql211.infinityfree.com` (not localhost)

### Files return 404 or "Not Found"
1. Verify file is in `/htdocs/` (not `/htdocs/creatorzhive/`)
2. Check `.htaccess` isn't blocking the route
3. For setup.php or webhook, use root-level proxy files
4. Verify FTP upload completed successfully

### Environment variables not loading
1. `.env` file must be in `/htdocs/` (project root)
2. AppConfig now auto-loads `.env` - no manual load needed
3. Check both `$_ENV['KEY']` and `getenv('KEY')` work
4. If stale, clear PHP opcode cache (restart service)

---

## 📝 Commit Message Format

**Required for ALL commits:**

```
Fix: Brief description (under 50 chars)

Longer explanation of what was broken, why, and how it's fixed.
Include technical details and rationale.

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>
```

**Commit prefixes:**
- `Fix:` - Bug fixes
- `Docs:` - Documentation updates
- `Chore:` - Configuration, tooling
- `Refactor:` - Code cleanup (rare - preserve architecture)
- `Feat:` - New features (rare - MVP focused)

---

## 🚀 Before Considering Work Complete

- [ ] Code change made locally
- [ ] Git commit created with meaningful message
- [ ] Upload instructions provided (exact paths)
- [ ] Explanation of change included
- [ ] Testing steps documented
- [ ] User instructed to verify it works

**Do NOT skip any of these steps.**

---

## 📚 Documentation

### Read First
- [INFINITYFREE_SETUP.md](docs/guides/INFINITYFREE_SETUP.md) — Deployment guide
- [CODEBASE_ORGANIZATION.md](docs/guides/CODEBASE_ORGANIZATION.md) — File structure

### Key References
- [docs/MASTER_PROJECT_GUIDE.md](docs/MASTER_PROJECT_GUIDE.md) — Complete reference
- [docs/system-analysis.md](docs/system-analysis.md) — Architecture & design
- [docs/security/security-audit.md](docs/security/security-audit.md) — Security notes

---

## 🎯 Phase Timeline

### ✅ Phase 1 Complete
- Web-based setup endpoint (`setup.php`)
- Background job webhook (`process-jobs.php`)
- Security hardening (APP_SECRET enforcement, upload protection)
- Comprehensive documentation
- InfinityFree deployment readiness

### ⏳ Phase 2 (Post-Launch)
- Database session handler (security)
- CSP headers (security)
- Email integration verification

### 📅 Phase 3 (Feature Completeness)
- Invoice PDF generation
- OAuth implementations (Twitter, TikTok, YouTube)
- Media library enhancements

### 🚀 Phase 4 (Scaling)
- Redis caching
- Database optimization
- CDN integration

---

## ⚠️ Do NOT

- ❌ Redesign architecture without explicit request
- ❌ Add features beyond what's asked
- ❌ Skip git commits
- ❌ Forget to provide upload instructions
- ❌ Mix multiple changes in one commit
- ❌ Commit without testing the fix locally first
- ❌ Add extensive comments or docstrings
- ❌ Remove or rename files without confirmation

---

## ✅ Do

- ✅ Commit after every change
- ✅ Provide exact InfinityFree paths
- ✅ Explain what changed and why
- ✅ Include testing instructions
- ✅ Reference related issues/PRs
- ✅ Keep commits focused and atomic
- ✅ Test fixes locally before deployment
- ✅ Use self-documenting code

---

## 📞 Quick Reference

| Situation | Action |
|-----------|--------|
| Fix applied | Commit + Upload instructions + Test steps |
| New feature | Confirm scope + Plan + Implement + Test + Commit |
| Refactoring | Preserve architecture + Commit after |
| Documentation | Update + Commit |
| Code review | Check architecture + Security + Performance |

---

**Last Updated**: 2026-06-12  
**Version**: 1.0  
**Status**: Active — Follow these guidelines in every session
