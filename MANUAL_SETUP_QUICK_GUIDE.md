# Manual SQL Import — Quick Reference

**Use this when**: setup.php doesn't work  
**Time required**: 10-15 minutes  
**No technical skills needed**: Just copying and pasting in InfinityFree

---

## 📋 The 6-Step Process

### Step 1: Open phpMyAdmin (2 minutes)

1. Go to: **https://infinityfree.net/cpanel**
2. Login with your InfinityFree account
3. Find: **MySQL Manager** or **phpMyAdmin**
4. Click: **phpMyAdmin** button
5. Select database: `if0_42095116_creatorz_hive` (from your .env)

### Step 2: Import Schema (2 minutes)

1. Click: **Import** tab
2. Click: **Choose File** button
3. Navigate to: `database/schema.sql` (in your project folder on your computer)
4. Click: **Go** button
5. **Wait** for green success message ✅

**Verify**: On left sidebar, you should now see tables like:
- `users`, `posts`, `analytics`, `deals`, `invoices`, `notifications`, etc.

### Step 3-7: Import Demo Data (5 × 1 minute each)

Repeat the same process for each file:

| File | Location |
|------|----------|
| **users** | `database/seeds/users.sql` |
| **posts** | `database/seeds/posts.sql` |
| **deals** | `database/seeds/deals.sql` |
| **notifications** | `database/seeds/notifications.sql` |
| **analytics** | `database/seeds/analytics.sql` |

For each:
1. Click **Import** → **Choose File**
2. Select the file
3. Click **Go**
4. Wait for success message

### Step 8: Create Admin User (3 minutes)

Now you need to manually create an admin account.

#### Option A: Use Web Password Hasher (Easiest)

1. Go to: **https://creatorz.freedev.app/hash-password.php**
2. Enter password: `Admin@1234` (or your password ≥ 8 chars)
3. Click: **Generate Hash**
4. Click: **Copy to Clipboard**

#### Option B: Use Local PHP Script

If you have PHP installed locally:

```bash
php scripts/hash-password.php Admin@1234
```

Copy the output hash.

---

## Insert Admin User into Database

1. In phpMyAdmin, click on `users` table
2. Click **Insert** tab
3. Fill in the form:
   - **id**: Leave empty (auto-generates)
   - **name**: `Administrator`
   - **username**: `admin_12345678` (or any name)
   - **email**: `admin@creatorzhive.local` (or your email)
   - **password**: **Paste the hash from Step 8** ⬅️ IMPORTANT
   - **password_confirmed_at**: Leave empty
   - **role**: `admin`
   - **email_verified_at**: Leave empty
   - **created_at**: Leave empty (auto-generates)
   - **updated_at**: Leave empty (auto-generates)

4. Click **Go** or **Insert**
5. Success message should appear ✅

---

## Verify Everything Works

1. Go to: **https://creatorz.freedev.app/verify-deployment.php**
2. Check all items are green ✅
3. Go to: **https://creatorz.freedev.app/**
4. Login with:
   - Email: `admin@creatorzhive.local` (whatever you entered)
   - Password: `Admin@1234` (whatever you entered)
5. Dashboard should load with stats and posts 🎉

---

## Troubleshooting

### Import Gives "Syntax Error"

Some imports may fail due to MySQL version issues. This is okay:
- Continue with the next file
- Most tables should have been created
- You can skip `analytics.sql` if it fails (demo data only)

### "Table Already Exists" Error

This is fine! It means tables were already partially created. Continue.

### Can't Find phpMyAdmin

Look for:
- "MySQL Manager"
- "Databases"
- "cPanel" > "MySQL Databases"
- Then click the manage/phpMyAdmin link

### Password Hash Not Working

Make sure you:
1. ✅ Copied the ENTIRE hash (starts with `$2y$12$`)
2. ✅ Pasted it into the password field (not username)
3. ✅ Left other hash-related fields empty
4. ✅ Used Admin tab to see raw hash (not "View" tab)

---

## Files Reference

For detailed instructions, see:
- **docs/guides/MANUAL_SQL_IMPORT.md** — Full step-by-step guide
- **QUICK_START.txt** — Overall context
- **NEXT_STEPS.md** — What comes after

---

## Done? Tell Me!

After you complete these steps:

1. Screenshot verify-deployment.php showing all green ✅
2. Message: "Manual SQL import complete"
3. I'll verify and execute Phases 2-8

Then you'll have a fully tested, production-ready application! 🚀

---

## Quick Links

- **Database schema**: `database/schema.sql`
- **Demo data**: `database/seeds/*.sql`
- **Password hasher (web)**: https://creatorz.freedev.app/hash-password.php
- **Verification**: https://creatorz.freedev.app/verify-deployment.php
- **Your application**: https://creatorz.freedev.app/

---

**Questions?** Read MANUAL_SQL_IMPORT.md for the detailed version.
