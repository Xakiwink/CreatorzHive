<?php

/** One-off generator: php scripts/build-analytics-seed.php > database/seeds/analytics.sql */

$platforms = ['instagram' => [7000, 7500], 'tiktok' => [3200, 3800], 'youtube' => [1800, 2200]];
echo "-- Seed analytics_snapshots (90 days) + analytics totals\n";
echo "SET @uid := (SELECT id FROM users WHERE email = 'david@creatorzhive.com' LIMIT 1);\n\n";

for ($d = 89; $d >= 0; $d--) {
    $date = date('Y-m-d', strtotime("-{$d} days"));
    $w = (int) date('w', strtotime($date));
    $weekend = ($w === 0 || $w === 6);
    foreach ($platforms as $plat => $range) {
        [$start, $end] = $range;
        $t = 1.0 - ($d / 89.0);
        $baseF = (int) round($start + ($end - $start) * $t);
        $noise = crc32($plat . $date) % 37;
        $followers = max($start, min($end, $baseF + $noise - 18));
        $imp = (int) ($followers * 12 + ($weekend ? 800 : 200));
        $reach = (int) ($imp * 0.85);
        $likes = (int) ($followers * 0.02 * ($weekend ? 1.15 : 1));
        $comments = (int) ($likes * 0.12);
        $shares = (int) ($likes * 0.08);
        $saves = (int) ($likes * 0.1);
        $er = round(2 + ($weekend ? 2 : 0) + (crc32($date . $plat) % 50) / 10, 2);
        $lc = (int) ($likes * 0.03);
        $pv = (int) ($reach * 0.01);

        printf(
            "INSERT INTO analytics_snapshots (user_id, social_account_id, platform, snapshot_date, period, followers, impressions, reach, likes, comments, shares, saves, link_clicks, profile_visits, engagement_rate) VALUES (@uid, NULL, '%s', '%s', 'daily', %d, %d, %d, %d, %d, %d, %d, %d, %d, %.2f) ON DUPLICATE KEY UPDATE followers=VALUES(followers), impressions=VALUES(impressions), reach=VALUES(reach), likes=VALUES(likes), comments=VALUES(comments), shares=VALUES(shares), saves=VALUES(saves), engagement_rate=VALUES(engagement_rate);\n",
            $plat,
            $date,
            $followers,
            $imp,
            $reach,
            $likes,
            $comments,
            $shares,
            $saves,
            $lc,
            $pv,
            $er
        );
    }
}

echo "\n";
echo "INSERT INTO analytics (user_id, total_posts, published_posts, draft_posts, scheduled_posts, failed_posts, total_followers, total_impressions, total_engagements, total_reach, avg_engagement_rate, total_revenue)\n";
echo "VALUES (@uid, 25, 10, 7, 5, 3, 13500, 2500000, 120000, 2100000, 4.25, 0.00)\n";
echo "ON DUPLICATE KEY UPDATE total_posts=VALUES(total_posts), published_posts=VALUES(published_posts), draft_posts=VALUES(draft_posts), scheduled_posts=VALUES(scheduled_posts), failed_posts=VALUES(failed_posts), total_followers=VALUES(total_followers), total_impressions=VALUES(total_impressions), total_engagements=VALUES(total_engagements), total_reach=VALUES(total_reach), avg_engagement_rate=VALUES(avg_engagement_rate);\n";
