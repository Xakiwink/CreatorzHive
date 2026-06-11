SET @uid := (SELECT id FROM users WHERE email = 'david@creatorzhive.com' LIMIT 1);

DELETE FROM notifications WHERE user_id = @uid;

INSERT INTO notifications (user_id, type, title, body, action_url, icon, is_read, read_at, created_at) VALUES
(@uid, 'post_published', 'Post published', '“Summer edit” is now live.', '/?route=planner', '✅', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@uid, 'post_published', 'Post published', '“Collab teaser” went out to followers.', '/?route=planner', '✅', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(@uid, 'post_published', 'Post published', 'Scheduled reel posted successfully.', '/?route=planner', '✅', 0, NULL, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(@uid, 'deal_updated', 'Deal updated', '“Safaricom sprint” moved to completed.', '/?route=deals', '🤝', 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(@uid, 'deal_updated', 'Deal updated', '“YAZAKE challenge” is now active.', '/?route=deals', '🤝', 0, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(@uid, 'deal_updated', 'Deal updated', '“CRDB clips” entered negotiation.', '/?route=deals', '🤝', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@uid, 'invoice_paid', 'Invoice paid', 'INV-2026-014 · 4,200,000 TZS', '/?route=invoices', '💰', 1, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY)),
(@uid, 'invoice_paid', 'Invoice paid', 'INV-2026-009 marked paid.', '/?route=invoices', '💰', 0, NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(@uid, 'welcome', 'Welcome to CreatorzHive', 'Hi David — your workspace is ready.', '/?route=dashboard', '👋', 1, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 90 DAY)),
(@uid, 'post_failed', 'Post failed', '“Failed publish attempt” — API quota exceeded.', '/?route=planner', '❌', 0, NULL, DATE_SUB(NOW(), INTERVAL 6 HOUR));
