<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics - CreatorzHive</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dashboard.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/analytics.css')) ?>">
</head>
<body>
<div class="app-shell">
  <div id="sidebar-container"></div>
  <div class="main-content">
    <div id="header-container"></div>
    <main class="page-content">
<?php
$fragment = file_get_contents(__DIR__ . '/analytics.html');
echo str_replace(
    '__CHIVE_ROUTE_SETTINGS__',
    htmlspecialchars(route_url('settings'), ENT_QUOTES, 'UTF-8'),
    $fragment
);
?>
    </main>
  </div>
</div>
<div id="modal-container"></div>
<div id="toast-container"></div>
<?php require __DIR__ . '/../partials/app_script_globals.php'; ?>
<script>
  window.__APP_ENV__ = <?= json_encode(strtolower((string) env('APP_ENV', 'development'))) ?>;
</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/utils.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/app.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/assets/chart.js/chart.umd.min.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/analytics.js')) ?>"></script>
</body>
</html>
