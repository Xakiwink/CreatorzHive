<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap-web-view.php';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Privacy Policy — CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/legal.css')) ?>">
</head>
<body class="legal-body">

<header class="legal-nav">
  <div class="legal-nav-inner">
    <a href="<?= htmlspecialchars(route_url('home')) ?>" class="legal-logo">
      <img class="legal-logo-mark" src="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2" alt="">
      <span class="legal-logo-text">CreatorzHive</span>
    </a>
    <a href="<?= htmlspecialchars(route_url('home')) ?>" class="legal-nav-back">&larr; Back to home</a>
  </div>
</header>

<main class="legal-page">
  <div class="legal-card">
    <span class="eyebrow">Legal</span>
    <h1>Privacy Policy</h1>
    <p class="legal-updated">Last updated: July 9, 2026</p>

    <div class="legal-body">
      <h2>1. Introduction</h2>
      <p>This Privacy Policy explains what information CreatorzHive ("we," "us," "our," or "the service") collects, how we use it, and the choices you have. By using CreatorzHive, you agree to the collection and use of information as described here.</p>

      <h2>2. Information We Collect</h2>
      <p><strong>Account information</strong> — name, username, email address, and a securely hashed password (or your Google account identifier if you sign in with Google), plus any profile details you add such as an avatar or bio.</p>
      <p><strong>Connected platform data</strong> — when you connect Instagram, TikTok, or YouTube, we store an encrypted access token and the analytics data those platforms make available through their official APIs for your own account: follower counts, engagement, reach, and post performance. We only request the specific permissions needed to display that data back to you.</p>
      <p><strong>Content you create</strong> — posts, captions, and media you draft or schedule in the planner.</p>
      <p><strong>Usage data</strong> — basic technical data such as IP address, browser type, pages visited, and timestamps, used for security and troubleshooting.</p>

      <h2>3. How We Use Your Information</h2>
      <ul>
        <li>To provide the planner, analytics dashboard, and deals/invoices features</li>
        <li>To calculate growth deltas, statistics-based insights, and predictions from your own historical data — this is rule-based analysis of your data, not AI or machine learning, and not shared or trained on across other users</li>
        <li>To send account-related emails, such as verification and password reset</li>
        <li>To keep the service secure and detect abuse</li>
      </ul>

      <h2>4. How We Access Platform Data</h2>
      <p>We only ever connect to Instagram, TikTok, and YouTube through each platform's official OAuth authorization flow — you approve the connection on the platform's own sign-in screen, and we never see or store your platform password. We don't scrape data or access anything beyond what the granted permissions allow.</p>

      <h2>5. Data Sharing</h2>
      <p>We do not sell your data, and we do not use advertising or third-party analytics/tracking scripts on CreatorzHive. We share data only with:</p>
      <ul>
        <li>Instagram/Meta, TikTok, and YouTube/Google — to fetch the analytics data for the accounts you've connected</li>
        <li>Google — if you choose to sign in with Google</li>
        <li>Our email delivery provider — to send verification and account emails</li>
      </ul>

      <h2>6. Data Security</h2>
      <p>Passwords are stored hashed, never in plain text. Connected-platform access tokens are encrypted at rest. Traffic to the service is served over HTTPS. That said, no method of transmission or storage is 100% secure, and we can't guarantee absolute security.</p>

      <h2>7. Data Retention and Your Choices</h2>
      <p>You can disconnect any platform at any time from Settings, which immediately stops any further data collection from it. Historical analytics snapshots are kept so your growth charts and trends stay accurate over time. To request deletion of your account and associated data, contact us at <a href="mailto:privacy@creatorzhive.com">privacy@creatorzhive.com</a>.</p>

      <h2>8. Cookies and Sessions</h2>
      <p>CreatorzHive uses a single secure session cookie to keep you signed in. We don't use third-party advertising or tracking cookies.</p>

      <h2>9. Children's Privacy</h2>
      <p>CreatorzHive is not directed at anyone under 18. We do not knowingly collect personal information from children under 18. If we become aware that we have, we'll delete it and terminate the associated account.</p>

      <h2>10. Changes to This Policy</h2>
      <p>We may update this Privacy Policy as the service evolves. We'll update the "last updated" date above whenever a change is made.</p>

      <h2>11. Contact Us</h2>
      <p>Questions about this Privacy Policy? Reach us at <a href="mailto:privacy@creatorzhive.com">privacy@creatorzhive.com</a>.</p>
    </div>
  </div>
</main>

<footer class="legal-footer">
  <div class="legal-footer-inner">
    <span>&copy; <?= htmlspecialchars($year) ?> CreatorzHive. All rights reserved.</span>
    <nav aria-label="Legal">
      <a href="<?= htmlspecialchars(route_url('privacy-policy')) ?>">Privacy Policy</a>
      <a href="<?= htmlspecialchars(route_url('terms-of-service')) ?>">Terms of Service</a>
    </nav>
  </div>
</footer>

</body>
</html>
