-- Link accounts to Google Sign-In (OpenID sub)
ALTER TABLE users
    ADD COLUMN google_id VARCHAR(64) NULL DEFAULT NULL AFTER password,
    ADD UNIQUE INDEX idx_users_google_id (google_id);
