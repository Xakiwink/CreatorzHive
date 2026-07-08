-- Run manually via phpMyAdmin on InfinityFree (no SSH/migration runner available).
-- Adds a cache table for the creator/growth score engine (Phase 2).
-- Regenerated in place (UNIQUE key + upsert) -- this is a cache, not history.
-- analytics_snapshots remains the source of truth for history.

CREATE TABLE IF NOT EXISTS `creator_scores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `growth_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `engagement_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `consistency_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `creator_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `computed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_creator_score_user` (`user_id`),
  CONSTRAINT `creator_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
