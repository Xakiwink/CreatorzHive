-- Run manually via phpMyAdmin on InfinityFree (no SSH/migration runner available).
-- Adds cache tables for the analytics insights/predictions engine.
-- Both tables are regenerated in place (UNIQUE key + upsert) -- they are caches,
-- not history. analytics_snapshots remains the source of truth for history.

CREATE TABLE IF NOT EXISTS `insights_cache` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `insight_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('positive','negative','neutral') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'neutral',
  `metric` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_insight` (`user_id`,`insight_key`),
  KEY `idx_insights_user` (`user_id`),
  CONSTRAINT `insights_cache_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prediction_cache` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `platform` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL = all platforms combined',
  `metric` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `horizon` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'next_week | next_month',
  `method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'linear_regression | moving_average',
  `predicted_value` decimal(14,2) NOT NULL,
  `based_on_snapshots` int unsigned NOT NULL DEFAULT '0',
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prediction` (`user_id`,`platform`,`metric`,`horizon`),
  KEY `idx_predictions_user` (`user_id`),
  CONSTRAINT `prediction_cache_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
