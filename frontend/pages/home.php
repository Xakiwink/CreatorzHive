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
  <meta name="description" content="Know which content makes money. Then make more of it. Plan posts, track real growth, and get statistics-based insights across Instagram, TikTok, and YouTube — all from one calm workspace.">
  <title>CreatorzHive — Know which content makes money</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/landing.css')) ?>">
</head>
<body class="landing-body">

<header class="landing-nav">
  <div class="landing-nav-inner">
    <a href="<?= htmlspecialchars(route_url('home')) ?>" class="landing-logo">
      <img class="landing-logo-mark" src="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2" alt="">
      <span class="landing-logo-text">CreatorzHive</span>
    </a>
    <nav class="landing-nav-links" aria-label="Section links">
      <a href="#features">Features</a>
      <a href="#platforms">Platforms</a>
      <a href="#insights">Insights</a>
      <a href="#faq">FAQ</a>
    </nav>
    <div class="landing-nav-actions">
      <a href="<?= htmlspecialchars(route_url('login')) ?>" class="btn btn-ghost btn-sm">Sign In</a>
      <a href="<?= htmlspecialchars(route_url('register')) ?>" class="btn btn-primary btn-sm">Get Started</a>
    </div>
  </div>
</header>

<main>

  <!-- ── Hero ─────────────────────────────────────────── -->
  <section class="landing-hero">
    <div class="landing-hero-inner">
      <div class="landing-hero-copy reveal">
        <span class="eyebrow">Creator workspace</span>
        <h1>Know Which Content Makes Money.<br>Then Make More Of It.</h1>
        <p class="landing-hero-sub">Track creator growth across Instagram, TikTok, and YouTube using real historical analytics, statistics-based insights, and growth predictions — all from one dashboard.</p>
        <div class="landing-hero-actions">
          <a href="<?= htmlspecialchars(route_url('register')) ?>" class="btn btn-primary btn-lg">Get started free</a>
          <a href="#features" class="btn btn-secondary btn-lg">See how it works</a>
        </div>
        <p class="landing-hero-trust">No credit card required · Set up in minutes</p>
      </div>
      <div class="landing-hero-visual reveal" aria-hidden="true">
        <div class="landing-hero-blob landing-hero-blob--1"></div>
        <div class="landing-hero-blob landing-hero-blob--2"></div>
        <div class="landing-mock-dashboard">
          <div class="landing-mock-header">
            <span class="landing-mock-dot"></span><span class="landing-mock-dot"></span><span class="landing-mock-dot"></span>
          </div>
          <div class="landing-mock-stats">
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Followers</span>
              <span class="landing-mock-stat-value" data-count-to="12400">0</span>
            </div>
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Engagement</span>
              <span class="landing-mock-stat-value" data-count-to="6.2" data-suffix="%">0</span>
            </div>
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Growth score</span>
              <span class="landing-mock-stat-value" data-count-to="82">0</span>
            </div>
          </div>
          <div class="landing-mock-chart">
            <span style="--h:38%"></span><span style="--h:52%"></span><span style="--h:44%"></span>
            <span style="--h:68%"></span><span style="--h:58%"></span><span style="--h:80%"></span>
            <span style="--h:74%"></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Features ─────────────────────────────────────── -->
  <section class="landing-section" id="features">
    <div class="landing-section-inner">
      <div class="landing-section-head reveal">
        <span class="eyebrow">Everything in one place</span>
        <h2>Built for the whole creator workflow</h2>
        <p>Content-first, calm, and focused — no tab-juggling between five different tools.</p>
      </div>
      <div class="landing-feature-grid">
        <div class="landing-feature-card reveal">
          <span class="landing-feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>
          </span>
          <h3>Planner</h3>
          <p>Schedule content across every platform from one calendar, with a live per-platform preview before you publish.</p>
        </div>
        <div class="landing-feature-card reveal">
          <span class="landing-feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm9.75 1.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v5.625c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-5.625zm0-9.75C13.5 5.504 14.004 5 14.625 5h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V6.125z" /></svg>
          </span>
          <h3>Analytics</h3>
          <p>Real per-day growth, engagement, and reach across platforms — plus statistics-based insights and predictions. No fabricated numbers.</p>
        </div>
        <div class="landing-feature-card reveal">
          <span class="landing-feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </span>
          <h3>Deals</h3>
          <p>Track brand collaborations from first contact to signed contract, without losing the thread in your inbox.</p>
        </div>
        <div class="landing-feature-card reveal">
          <span class="landing-feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3A1.5 1.5 0 001.5 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
          </span>
          <h3>Media</h3>
          <p>One library for every image and video you post, ready to drop straight into the composer.</p>
        </div>
        <div class="landing-feature-card reveal">
          <span class="landing-feature-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
          </span>
          <h3>Notifications</h3>
          <p>Know the moment a post publishes, a publish fails, or a deal moves forward.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Platform integrations ────────────────────────── -->
  <section class="landing-section landing-section--muted" id="platforms">
    <div class="landing-section-inner">
      <div class="landing-section-head reveal">
        <span class="eyebrow">Connect once</span>
        <h2>Publish and track across every platform that matters</h2>
        <p>One composer, one analytics view, no switching tabs to see how a post actually did.</p>
      </div>
      <div class="landing-platform-row reveal">
        <div class="landing-platform-badge">
          <span class="landing-platform-icon landing-platform-icon--instagram">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
          </span>
          <span>Instagram</span>
        </div>
        <div class="landing-platform-badge">
          <span class="landing-platform-icon landing-platform-icon--tiktok">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.81a8.18 8.18 0 004.78 1.52V6.89a4.85 4.85 0 01-1.01-.2z"/></svg>
          </span>
          <span>TikTok</span>
        </div>
        <div class="landing-platform-badge">
          <span class="landing-platform-icon landing-platform-icon--youtube">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 001.46 6.42 29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.4a2.78 2.78 0 001.95-1.97A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
          </span>
          <span>YouTube</span>
        </div>
        <div class="landing-platform-badge landing-platform-badge--soon">
          <span class="landing-platform-icon landing-platform-icon--twitter">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </span>
          <span>X / Twitter</span>
          <span class="landing-platform-soon-tag">Coming soon</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Analytics preview ────────────────────────────── -->
  <section class="landing-section" id="insights">
    <div class="landing-section-inner landing-split">
      <div class="landing-split-copy reveal">
        <span class="eyebrow">Analytics</span>
        <h2>Growth deltas, not just a snapshot</h2>
        <p>Every metric shows today, this week, and this month — not just a single number frozen in time. Nearest-snapshot logic keeps the numbers honest even when a platform's data refreshes on its own schedule.</p>
        <ul class="landing-check-list">
          <li>Real per-platform follower, reach, and engagement history</li>
          <li>Platform comparison — see which platform is actually pulling its weight</li>
          <li>Content ranked by real performance where the platform allows it — never estimated</li>
        </ul>
      </div>
      <div class="landing-split-visual reveal" aria-hidden="true">
        <div class="landing-mock-dashboard landing-mock-dashboard--wide">
          <div class="landing-mock-stats landing-mock-stats--4">
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Reach</span>
              <span class="landing-mock-stat-value" data-count-to="48200">0</span>
            </div>
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Impressions</span>
              <span class="landing-mock-stat-value" data-count-to="91300">0</span>
            </div>
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Avg. engagement</span>
              <span class="landing-mock-stat-value" data-count-to="5.4" data-suffix="%">0</span>
            </div>
            <div class="landing-mock-stat">
              <span class="landing-mock-stat-label">Posts</span>
              <span class="landing-mock-stat-value" data-count-to="24">0</span>
            </div>
          </div>
          <div class="landing-mock-chart landing-mock-chart--wide">
            <span style="--h:30%"></span><span style="--h:42%"></span><span style="--h:38%"></span>
            <span style="--h:55%"></span><span style="--h:48%"></span><span style="--h:62%"></span>
            <span style="--h:58%"></span><span style="--h:70%"></span><span style="--h:65%"></span>
            <span style="--h:80%"></span><span style="--h:76%"></span><span style="--h:90%"></span>
            <span style="--h:84%"></span><span style="--h:95%"></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Growth tracking / insights engine ────────────── -->
  <section class="landing-section landing-section--muted">
    <div class="landing-section-inner landing-split landing-split--reverse">
      <div class="landing-split-copy reveal">
        <span class="eyebrow">Growth &amp; insights</span>
        <h2>A growth score, and insights you can actually trust</h2>
        <p>A single 0–100 score blends your growth, engagement, and posting consistency, so you can see at a glance whether things are trending up before you dig into the details.</p>
        <p>Insights like "follower growth increased 12% this week" or "posting frequency dropped" are generated from statistical analysis of your own historical data — moving averages and trend detection, not a third-party AI API. If there isn't enough history yet to say something meaningful, nothing is shown, rather than a guess dressed up as a fact.</p>
      </div>
      <div class="landing-split-visual reveal">
        <div class="landing-insight-card">
          <span class="landing-insight-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" /></svg>
          </span>
          <p class="landing-insight-text">"Follower growth increased 8.4% compared to last week."</p>
        </div>
        <div class="landing-insight-card">
          <span class="landing-insight-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </span>
          <p class="landing-insight-text">"Engagement is growing faster than your follower count this week."</p>
        </div>
        <div class="landing-insight-card">
          <span class="landing-insight-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          </span>
          <p class="landing-insight-text">"Weekend posts receive 18% more engagement than weekdays."</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ── FAQ ───────────────────────────────────────────── -->
  <section class="landing-section" id="faq">
    <div class="landing-section-inner landing-section-inner--narrow">
      <div class="landing-section-head reveal">
        <span class="eyebrow">Questions</span>
        <h2>Frequently asked questions</h2>
      </div>
      <div class="landing-faq reveal">
        <details class="landing-faq-item">
          <summary>Which platforms does CreatorzHive support?</summary>
          <p>Instagram, TikTok, and YouTube today, with X/Twitter on the way. Connect one or all of them from Settings.</p>
        </details>
        <details class="landing-faq-item">
          <summary>Do I need to install anything?</summary>
          <p>No — CreatorzHive runs entirely in your browser. Connect your accounts and you're set up in minutes.</p>
        </details>
        <details class="landing-faq-item">
          <summary>Does it use AI to generate insights?</summary>
          <p>No. Insights and predictions come from statistical analysis of your own historical data — growth trends and moving averages — not a third-party AI API. If there isn't enough data yet, we simply don't show an insight rather than guessing.</p>
        </details>
        <details class="landing-faq-item">
          <summary>Is my data secure?</summary>
          <p>Platform access tokens are encrypted at rest, and we only request the permissions each integration actually needs to publish and read back your own analytics.</p>
        </details>
        <details class="landing-faq-item">
          <summary>Can I manage brand deals and invoices too?</summary>
          <p>Yes — Deals and Invoices live alongside your content calendar, so collaborations don't get lost in a separate spreadsheet.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- ── Final CTA ─────────────────────────────────────── -->
  <section class="landing-cta">
    <div class="landing-cta-inner reveal">
      <h2>Ready to know what's actually working?</h2>
      <p>Create your free workspace and connect your first platform in a few minutes.</p>
      <a href="<?= htmlspecialchars(route_url('register')) ?>" class="btn btn-primary btn-lg">Get started free</a>
    </div>
  </section>

</main>

<footer class="landing-footer">
  <div class="landing-footer-inner">
    <div class="landing-footer-brand">
      <img class="landing-logo-mark" src="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2" alt="">
      <span class="landing-logo-text">CreatorzHive</span>
    </div>
    <nav class="landing-footer-links" aria-label="Legal">
      <a href="<?= htmlspecialchars(route_url('privacy-policy')) ?>">Privacy Policy</a>
      <a href="<?= htmlspecialchars(route_url('terms-of-service')) ?>">Terms of Service</a>
    </nav>
    <p class="landing-footer-copy">&copy; <?= htmlspecialchars($year) ?> CreatorzHive. All rights reserved.</p>
  </div>
</footer>

<script src="<?= htmlspecialchars(asset_url('frontend/js/landing.js')) ?>"></script>
</body>
</html>
