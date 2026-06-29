# Manual SQL Import — InfinityFree phpMyAdmin

Use this when `setup.php` doesn't complete the database step automatically.

---

## What to Import

One file only: **`database/schema.sql`**

This is a full database dump — it creates all tables, indexes, triggers, and includes the initial data. There are no separate seed files.

---

## Steps

### 1. Open phpMyAdmin

InfinityFree control panel → **MySQL Manager** → click **phpMyAdmin** next to your database.

### 2. Select Your Database

Click your database name in the left sidebar (e.g. `if0_42295215_creatorz_hive`).

### 3. Import

1. Click the **Import** tab at the top
2. Click **Choose File** → select `database/schema.sql`
3. Leave all other settings at defaults
4. Click **Go**

Wait for the success message. Import typically takes 15–30 seconds.

### 4. Verify Tables

Click your database name in the left sidebar. You should see:

`analytics`, `audit_logs`, `deals`, `email_verifications`, `invoices`, `job_queue`, `media_files`, `notification_preferences`, `notifications`, `platform_post_results`, `posts`, `post_media`, `post_tags`, `rate_limits`, `sessions`, `settings`, `social_accounts`, `tags`, `user_preferences`, `users`

---

## Create the Admin User

The schema import does not create an admin user. Use `setup.php` for this:

1. Visit `https://creatorzhive.infinityfree.io/setup.php`
2. Fill in admin email, name, password
3. Click **Complete Setup**

If `setup.php` is already deleted, insert a user manually:

1. Run this locally to get a bcrypt hash:
   ```bash
   php scripts/hash-password.php YourPassword123
   ```
2. In phpMyAdmin → `users` table → **Insert**:
   - `name`: Administrator
   - `username`: admin
   - `email`: your email
   - `password`: the hash from step 1
   - `role`: admin
   - `is_active`: 1
   - `email_verified`: 1

---

## Troubleshooting

| Error | Fix |
|-------|-----|
| `#1142 CREATE VIEW command denied` | You have an old schema.sql with views. Use the current file from the repo — it has no views. |
| `Table already exists` | Safe to ignore if tables look correct. Use `DROP TABLE IF EXISTS` is already in the file. |
| Import times out | Split the file using a text editor and import in two parts. |
| `Access denied` | Verify `DB_USERNAME` and `DB_PASSWORD` in `.env` match InfinityFree credentials. |
