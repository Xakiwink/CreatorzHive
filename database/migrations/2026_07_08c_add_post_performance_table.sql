-- Run manually via phpMyAdmin on InfinityFree (no SSH/migration runner available).
-- Stores the latest known real per-post performance (Instagram/YouTube only --
-- TikTok/Twitter have no read-back OAuth scope granted, so their rows are
-- written with available=0 rather than fabricated). One row per
-- platform_post_results entry, refreshed in place by FetchPostPerformanceJob.

CREATE TABLE IF NOT EXISTS `post_performance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `platform_post_result_id` int unsigned NOT NULL,
  `post_id` int unsigned NOT NULL,
  `platform` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT '0',
  `likes` int unsigned NOT NULL DEFAULT '0',
  `comments` int unsigned NOT NULL DEFAULT '0',
  `shares` int unsigned NOT NULL DEFAULT '0',
  `saves` int unsigned NOT NULL DEFAULT '0',
  `reach` int unsigned NOT NULL DEFAULT '0',
  `engagement_rate` decimal(6,2) NOT NULL DEFAULT '0.00',
  `fetched_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_performance_result` (`platform_post_result_id`),
  KEY `idx_post_performance_post` (`post_id`),
  CONSTRAINT `post_performance_ibfk_1` FOREIGN KEY (`platform_post_result_id`) REFERENCES `platform_post_results` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_performance_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
