<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - CreatorzHive</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<div class="app-shell">
  <div id="sidebar-container"></div>
  <div class="main-content">
    <div id="header-container"></div>
    <main class="page-content">
      <div class="page-stack slide-up">
        <header class="page-toolbar page-header--primary">
          <div>
            <span class="eyebrow">Inbox</span>
            <h1 class="page-heading">Notifications</h1>
            <p class="page-lead text-muted">Stay on top of posts, deals, and payouts.</p>
          </div>
          <div class="page-toolbar-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="notifMarkAllRead">Mark all as read</button>
            <button type="button" class="btn btn-ghost btn-sm" id="notifDeleteRead">Delete read</button>
          </div>
        </header>

        <section class="hero-strip hero-strip--quiet" aria-label="Notifications overview">
          <h2 class="hero-strip-title">Quiet, scannable alerts</h2>
          <p>Filter by type, mark all read, or clear history — tuned for quick triage between sessions.</p>
        </section>

        <div class="tab-pills notif-filter-tabs" role="tablist">
          <button type="button" class="tab-pill active" data-tab="all" role="tab">All</button>
          <button type="button" class="tab-pill" data-tab="unread" role="tab">Unread</button>
          <button type="button" class="tab-pill" data-tab="posts" role="tab">Posts</button>
          <button type="button" class="tab-pill" data-tab="deals" role="tab">Deals</button>
          <button type="button" class="tab-pill" data-tab="invoices" role="tab">Invoices</button>
        </div>

        <div id="notifListMount" class="notif-list" aria-live="polite"></div>
        <div id="notifEmpty" class="notif-empty d-none">
          <p class="notif-empty-title">You're all caught up 🎉</p>
          <p class="text-muted">New alerts will show up here.</p>
        </div>
        <div class="notif-load-more-wrap">
          <button type="button" class="btn btn-secondary d-none" id="notifLoadMore">Load more</button>
        </div>
      </div>
    </main>
  </div>
</div>
<div id="modal-container"></div>
<div id="toast-container"></div>
<?php require __DIR__ . '/../partials/app_script_globals.php'; ?>
<script src="<?= htmlspecialchars(asset_url('frontend/js/utils.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/app.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/notifications.js')) ?>"></script>
</body>
</html>
