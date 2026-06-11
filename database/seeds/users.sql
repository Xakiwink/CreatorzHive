-- Demo users (bcrypt cost 12). Triggers insert analytics / notification_preferences / user_preferences.
INSERT INTO users (name, username, email, password, role, email_verified, is_active, timezone) VALUES
(
    'System Admin',
    'admin',
    'admin@creatorzhive.com',
    '$2y$12$piyPLnzsG8eYoCEJndkkEOLFpMSYPMYmROTH4MmqhIsa6Ua0fLgg2',
    'admin',
    1,
    1,
    'Africa/Dar_es_Salaam'
),
(
    'David Mposo',
    'davidmposo',
    'david@creatorzhive.com',
    '$2y$12$orzNzowzs.bO4u4SoVZlSeAdDCjG6.JSzC4yMUqpfVvZnKzteOjeG',
    'creator',
    1,
    1,
    'Africa/Dar_es_Salaam'
),
(
    'Brand Partner',
    'brandpartner',
    'brand@creatorzhive.com',
    '$2y$12$UnDzAvYKdS0/R.yNBEia/ObK23VkdCPbE6K7nyjIZaJ2agKcAfs6a',
    'brand',
    1,
    1,
    'Africa/Dar_es_Salaam'
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    username = VALUES(username),
    password = VALUES(password),
    role = VALUES(role),
    email_verified = VALUES(email_verified),
    is_active = VALUES(is_active),
    timezone = VALUES(timezone);
