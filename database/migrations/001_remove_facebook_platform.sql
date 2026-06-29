-- Migration 001: Remove Facebook platform
-- Run this on your live database to complete the Instagram Business Login migration.
-- Safe to run: deactivates existing Facebook accounts before altering the ENUM.

-- Deactivate any existing Facebook social accounts (preserves data, prevents ENUM violation)
UPDATE social_accounts SET is_active = 0 WHERE platform = 'facebook';

-- Remove 'facebook' from the platform ENUM
ALTER TABLE social_accounts
    MODIFY COLUMN platform ENUM('instagram','tiktok','youtube','twitter') NOT NULL;

-- Remove any Facebook analytics snapshots
UPDATE analytics_snapshots SET platform = NULL WHERE platform = 'facebook';
