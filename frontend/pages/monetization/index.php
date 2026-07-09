<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monetization - CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dashboard.css')) ?>">
</head>
<body>
<div class="app-shell">
  <div id="sidebar-container"></div>
  <div class="main-content">
    <div id="header-container"></div>
    <main class="page-content">
      <header class="page-header page-header--primary">
        <div>
          <span class="eyebrow">Grow</span>
          <h1 class="page-heading">Monetization</h1>
          <p class="page-lead text-muted">Deals on a board, invoices in a table — same design language across the hub.</p>
        </div>
      </header>

      <section class="hero-strip hero-strip--quiet" aria-label="Monetization overview">
        <h2 class="hero-strip-title">From handshake to paid</h2>
        <p>Open <strong>Deals</strong> for the Kanban pipeline, then <strong>Invoices</strong> when it is time to bill. Warm accents, soft cards, minimal noise.</p>
      </section>

      <div class="d-grid gap-4 monetization-hub-grid">
        <div class="card card--gradient-accent slide-up">
          <div class="card-header">
            <h3 class="card-title">Brand deals</h3>
          </div>
          <div class="card-body">
            <p>Track stages from lead to completion. Drag cards, attach notes, and link to invoice drafts.</p>
            <a class="btn btn-secondary btn-on-gradient mt-4" href="<?= htmlspecialchars(route_url('deals')) ?>">Open deals</a>
          </div>
        </div>
        <div class="card card--feature slide-up">
          <div class="card-header">
            <h3 class="card-title">Invoices</h3>
          </div>
          <div class="card-body">
            <p class="text-secondary">Filter by status, see paid totals, and keep due dates visible next to your pipeline.</p>
            <a class="btn btn-secondary mt-4" href="<?= htmlspecialchars(route_url('invoices')) ?>">View invoices</a>
          </div>
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
</body>
</html>
