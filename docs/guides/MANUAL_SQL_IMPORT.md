# Manual SQL Import Guide — InfinityFree phpMyAdmin

**When to use this**: If the automated setup.php doesn't work

**What this does**: Manually creates database schema and populates demo data

**Time required**: 10-15 minutes

---

## Overview

Instead of using the setup.php web form, you'll manually import SQL files using InfinityFree's phpMyAdmin tool. This gives you more control and better error visibility.

### Files to Import (In This Order)

1. ✅ **database/schema.sql** (31KB) — Creates all tables, indexes, and views
2. ✅ **database/seeds/users.sql** (1.1KB) — Creates demo users
3. ✅ **database/seeds/posts.sql** (8.7KB) — Creates demo posts
4. ✅ **database/seeds/deals.sql** (2.2KB) — Creates demo deals
5. ✅ **database/seeds/notifications.sql** (1.9KB) — Creates demo notifications
6. ✅ **database/seeds/analytics.sql** (145KB) — Creates demo analytics data

---

## Step 1: Log In to InfinityFree Control Panel

1. Go to: **https://infinityfree.net/cpanel**
2. **Enter your credentials** (username and password)
3. Click **"Login"**

---

## Step 2: Open phpMyAdmin

1. In the control panel, find **"MySQL Manager"** or **"Databases"** section
2. Click **"phpMyAdmin"** or **"Manage"** next to your database
3. You should see the phpMyAdmin interface

**Your database name**: `if0_42095116_creatorz_hive` (from .env)

---

## Step 3: Select Your Database

1. On the left sidebar, click on **`if0_42095116_creatorz_hive`** (your database name)
2. The database should now be selected (highlighted)
3. You should see an empty "Tables" section (or existing tables if partially set up)

---

## Step 4: Import Schema (Main Tables)

### 4.1 Click the "Import" Tab

1. Click the **"Import"** tab at the top of phpMyAdmin
2. You should see an upload form

### 4.2 Select the Schema File

1. Click **"Choose File"** (or **"Browse"**)
2. Navigate to: `database/schema.sql`
3. **Select the file** and click **"Open"**

### 4.3 Run the Import

1. Click **"Go"** or **"Import"** button
2. **Wait** for the import to complete (may take 30-60 seconds)
3. **Expected result**: Green success message saying "✅ Query successful" or similar

### 4.4 Verify Tables Were Created

1. Click on your database name in the left sidebar
2. You should see a list of tables:
   - `users`
   - `posts`
   - `social_accounts`
   - `analytics`
   - `deals`
   - `invoices`
   - `notifications`
   - `job_queue`
   - `settings`
   - And others...

If you don't see these tables, **STOP** and screenshot the error message.

---

## Step 5: Import Demo Data (Users)

### 5.1 Click "Import" Tab Again

1. Click **"Import"** tab
2. Click **"Choose File"**

### 5.2 Select Users Seed File

1. Navigate to: `database/seeds/users.sql`
2. **Select and open** the file

### 5.3 Run the Import

1. Click **"Go"**
2. **Wait** for success message
3. **Verify**: In left sidebar, click on `users` table → you should see at least 1-2 users created

---

## Step 6: Import Demo Posts

Repeat the same process:

1. **Import** tab → **Choose File**
2. Select: `database/seeds/posts.sql`
3. Click **"Go"**
4. **Wait** for success message
5. **Verify**: Click `posts` table → should see 5-10 demo posts

---

## Step 7: Import Demo Deals

1. **Import** tab → **Choose File**
2. Select: `database/seeds/deals.sql`
3. Click **"Go"**
4. **Wait** for success message

---

## Step 8: Import Demo Notifications

1. **Import** tab → **Choose File**
2. Select: `database/seeds/notifications.sql`
3. Click **"Go"**
4. **Wait** for success message

---

## Step 9: Import Demo Analytics

1. **Import** tab → **Choose File**
2. Select: `database/seeds/analytics.sql`
3. Click **"Go"**
4. **Wait** for success message (this file is large, may take 1-2 minutes)

---

## Step 10: Verify All Tables Have Data

1. In phpMyAdmin left sidebar, click on each table:
   - `users` — Should have 1-2 rows
   - `posts` — Should have 5-10 rows
   - `deals` — Should have 1-2 rows
   - `social_accounts` — Should have data (if seeded)

2. If all tables have data, you're **done with database setup!** ✅

---

## Step 11: Create Admin User (Manual)

Since we didn't run setup.php, we need to create an admin user manually.

### Option A: Use phpMyAdmin (Recommended for testing)

1. In phpMyAdmin, click on `users` table
2. Click **"Insert"** tab
3. Fill in the form:
   - **id**: Leave empty (auto-increment)
   - **name**: `Administrator`
   - **username**: `admin_` + random string (e.g., `admin_a1b2c3d4`)
   - **email**: `admin@creatorzhive.local` (or your email)
   - **password**: **IMPORTANT** — Must be hashed with bcrypt
   - **role**: `admin`
   - **email_verified_at**: Leave empty
   - **created_at**: Current timestamp
   - **updated_at**: Current timestamp

**Problem**: You need to hash the password with bcrypt, which phpMyAdmin can't do directly.

### Option B: Use a PHP Script (Better)

Instead, use this helper script to generate the hash:

1. Create a file called `hash-password.php` in your project root:

```php
<?php
// Quick password hasher
if (php_sapi_name() !== 'cli') {
    echo "Usage: php hash-password.php YourPassword123\n";
    exit;
}

$password = $argv[1] ?? 'Admin@1234';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
?>
```

2. Run locally:
```bash
php hash-password.php Admin@1234
```

3. Copy the hash output
4. In phpMyAdmin, insert user with the hash as password field

### Option C: Use MySQL Function (If Available)

Some InfinityFree instances support the `MD5` function in phpMyAdmin. Try this SQL:

```sql
INSERT INTO users (name, username, email, password, role, created_at, updated_at)
VALUES (
    'Administrator',
    'admin_' || substr(md5(rand()), 1, 8),
    'admin@creatorzhive.local',
    '$2y$12$' || substr(md5('Admin@1234'), 1, 53),  -- Note: This won't work, just example
    'admin',
    NOW(),
    NOW()
);
```

**Recommendation**: Use Option B (hash locally, paste hash into phpMyAdmin)

---

## Troubleshooting

### "Syntax Error" During Import

**Cause**: Schema might have MySQL version compatibility issues

**Solution**:
1. Check if all tables were created (some may have succeeded before error)
2. Try deleting all tables and re-importing schema only
3. Email support with the error message

### "Access Denied" or "Permission Error"

**Cause**: User doesn't have permission to create tables

**Solution**:
1. Check .env file — verify `DB_USERNAME` and `DB_PASSWORD` are correct
2. Try re-creating the database user in InfinityFree control panel
3. Delete and re-create the database

### "Table Already Exists" Error

**Cause**: Schema was partially imported before

**Solution**: This is okay! Continue with the seed files.

### Import Hangs or Times Out

**Cause**: File too large or network issue

**Solution**:
1. Try again — network might be temporarily slow
2. If `analytics.sql` hangs, you can skip it (demo data only)
3. Break up the file into smaller chunks

### Still Getting Database Errors in Application

**Cause**: Possible issues:
1. Tables created but no admin user
2. Tables created but no demo data
3. Database connection credentials wrong in .env

**Solution**:
1. Verify tables exist: Click database → should see 10+ tables
2. Verify data exists: Click `users` table → should have ≥ 1 row
3. Double-check .env credentials match InfinityFree

---

## Verify It Worked

After all imports complete:

1. **Go to**: https://creatorzhive.infinityfree.io/verify-deployment.php
2. **Check all boxes are green** ✅
3. **Verify**:
   - ✅ Database connection works
   - ✅ All tables exist
   - ✅ Row counts are > 0 for users, posts, etc.
4. **Test login**:
   - Go to: https://creatorzhive.infinityfree.io/
   - Login with admin account (email/password you created)
   - Dashboard should load with stats and posts

---

## What If Something Goes Wrong?

### Option 1: Start Over (Clean Slate)

1. Delete all tables in phpMyAdmin (right-click each table → Drop)
2. Re-import schema.sql
3. Re-import all seed files

### Option 2: Manual Debugging

1. Take screenshots of:
   - phpMyAdmin showing table list
   - Any error messages
   - verify-deployment.php output

2. Share with technical support

---

## Summary

**If imports succeed**:
- ✅ All tables created
- ✅ Demo data seeded
- ✅ Ready to login
- ✅ Application should work normally

**If any import fails**:
- ❌ Note which file failed
- ❌ Screenshot the error
- ❌ Try to continue with next file (some errors are recoverable)
- ❌ Share error with support

---

## Next Steps

After manual SQL import completes:

1. **Verify**: Visit verify-deployment.php
2. **Create admin user**: Use Option B (hash password locally)
3. **Test login**: Try logging in at https://creatorzhive.infinityfree.io/
4. **Tell me**: "Database initialized successfully" with screenshot

Then I'll execute Phases 2-8 (feature testing, security hardening, documentation, etc.)

---

**Questions?** Refer to QUICK_START.txt or NEXT_STEPS.md for overall context.
