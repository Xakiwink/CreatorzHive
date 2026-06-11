SET @uid := (SELECT id FROM users WHERE email = 'david@creatorzhive.com' LIMIT 1);

-- Idempotent demo seed: replace prior demo deals for this user.
DELETE FROM deals WHERE user_id = @uid;

INSERT INTO deals (user_id, brand_name, brand_email, title, description, amount, currency, status, deal_type, deadline_at, contracted_at, completed_at, notes, is_deleted) VALUES
(@uid, 'Safaricom Tanzania', 'partners@safaricom.co.tz', 'Safaricom 5G Creator Sprint', 'Short-form reels highlighting 5G use cases in DSM.', 8500000.00, 'TZS', 'completed', 'sponsored_post', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), NULL, 0),
(@uid, 'Airtel Tanzania', 'creatorlab@airtel.com', 'Data bundle awareness push', 'Stories + TikTok mix.', 4200000.00, 'TZS', 'completed', 'sponsored_post', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 55 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), NULL, 0),
(@uid, 'YAZAKE Beverages', 'marketing@yazake.co.tz', 'Summer hydration challenge', 'UGC hashtag challenge.', 3100000.00, 'TZS', 'active', 'ambassador', DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL, NULL, 0),
(@uid, 'CBE Innovation Hub', 'hub@cbe.ac.tz', 'Campus tech talk mini-series', 'Panel + Q&A clips.', 1800000.00, 'TZS', 'active', 'other', DATE_ADD(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, NULL, 0),
(@uid, 'CRDB Bank', 'brandstudio@crdbbank.co.tz', 'Youth savings literacy clips', 'Carousel + reel.', 5200000.00, 'TZS', 'negotiation', 'sponsored_post', DATE_ADD(CURDATE(), INTERVAL 45 DAY), NULL, NULL, NULL, 0),
(@uid, 'NMB Bank', 'collabs@nmbbank.co.tz', 'MSME toolkit explainers', 'Long-form YouTube.', 6100000.00, 'TZS', 'negotiation', 'affiliate', DATE_ADD(CURDATE(), INTERVAL 50 DAY), NULL, NULL, NULL, 0),
(@uid, 'Azam TV', 'creators@azam.tv', 'Sports highlights commentary', 'Pilot single episode.', 950000.00, 'TZS', 'lead', 'gifted', DATE_ADD(CURDATE(), INTERVAL 90 DAY), NULL, NULL, NULL, 0),
(@uid, 'Twiga Cement', 'media@twigacement.co.tz', 'Infrastructure documentary shorts', 'Deferred timeline.', 2750000.00, 'TZS', 'cancelled', 'other', NULL, NULL, NULL, 'Paused by brand.', 0);
