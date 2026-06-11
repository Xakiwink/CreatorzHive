<?php

/** php scripts/build-posts-seed.php > database/seeds/posts.sql */

$uid = "SET @uid := (SELECT id FROM users WHERE email = 'david@creatorzhive.com' LIMIT 1);";
echo "-- 25 posts for David (creator)\n$uid\n\n";

$platformSets = [
    '["instagram","tiktok"]',
    '["youtube","facebook"]',
    '["instagram"]',
    '["tiktok","youtube"]',
    '["facebook","instagram","tiktok"]',
];

$ins = function ($title, $content, $caption, $platforms, $status, $sched, $pub) {
    $cap = $caption === null ? 'NULL' : "'" . addslashes($caption) . "'";
    $s = $sched === null ? 'NULL' : "'$sched'";
    $p = $pub === null ? 'NULL' : "'$pub'";

    return "INSERT INTO posts (user_id, cover_media_id, title, content, caption, platforms, status, scheduled_at, published_at, is_deleted) VALUES (@uid, NULL, '" . addslashes($title) . "', '" . addslashes($content) . "', $cap, CAST('$platforms' AS JSON), '$status', $s, $p, 0);\n";
};

$n = 1;
// 10 published (last 30 days)
for ($i = 0; $i < 10; $i++) {
    $days = rand(1, 29);
    $pub = date('Y-m-d H:i:s', strtotime("-$days days"));
    $pl = $platformSets[$i % count($platformSets)];
    echo $ins(
        "Summer vibes #$n — Dar trip recap",
        'Quick recap from last weekend. Traffic was wild but the sunset delivered.',
        '#travel #tanzania #creator #' . $n,
        $pl,
        'published',
        null,
        $pub
    );
    $n++;
}
// 5 scheduled next 14 days
for ($i = 0; $i < 5; $i++) {
    $days = rand(1, 14);
    $sched = date('Y-m-d H:i:s', strtotime("+$days days"));
    $pl = $platformSets[$i % count($platformSets)];
    echo $ins(
        "Scheduled collab teaser #$n",
        'Drafting hooks for the Safaricom integration reel.',
        '#collab #safaricom #' . $n,
        $pl,
        'scheduled',
        $sched,
        null
    );
    $n++;
}
// 7 drafts
for ($i = 0; $i < 7; $i++) {
    $pl = $platformSets[$i % count($platformSets)];
    echo $ins(
        "Draft idea #$n — skincare routine",
        'Outline only — need b-roll from Wednesday.',
        '#skincare #routine #' . $n,
        $pl,
        'draft',
        null,
        null
    );
    $n++;
}
// 3 failed
for ($i = 0; $i < 3; $i++) {
    $pl = $platformSets[$i % count($platformSets)];
    echo $ins(
        "Failed publish attempt #$n",
        'API quota exceeded during upload.',
        '#oops #' . $n,
        $pl,
        'failed',
        null,
        null
    );
    $n++;
}
