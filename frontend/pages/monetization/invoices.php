<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoices - CreatorzHive</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dashboard.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/monetization.css')) ?>">
</head>
<body>
<div class="app-shell">
  <div id="sidebar-container"></div>
  <div class="main-content">
    <div id="header-container"></div>
    <main class="page-content">
<?php echo file_get_contents(__DIR__ . '/invoices-fragment.html'); ?>
    </main>
  </div>
</div>
<div id="modal-container"></div>
<div id="toast-container"></div>
<?php require __DIR__ . '/../partials/app_script_globals.php'; ?>
<script src="<?= htmlspecialchars(asset_url('frontend/js/utils.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/app.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/invoices.js')) ?>"></script>
</body>
</html>
