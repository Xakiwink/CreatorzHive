<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
$errorMessage = isset($message) ? (string) $message : 'You are not allowed to access this page.';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Access Restricted — CreatorzHive</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/auth.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <aside class="left">
    <div class="auth-brand-inner">
      <div class="auth-logo-row"><span class="auth-logo-mark" aria-hidden="true"></span></div>
      <span class="eyebrow auth-brand-eyebrow">Admin access</span>
      <h1>This area is for creator workflows</h1>
      <p class="auth-tagline">As an admin, you can manage users, integrations, and site settings from your operations panels.</p>
    </div>
  </aside>
  <main class="right">
    <div class="card card--feature">
      <span class="eyebrow auth-form-eyebrow">403</span>
      <h2>Access restricted</h2>
      <p class="auth-sub"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="row">
        <a class="btn" href="<?= htmlspecialchars(route_url('settings')) ?>">Go to Settings</a>
      </div>
      <p class="small"><a href="<?= htmlspecialchars(route_url('admin-users')) ?>">Open User Management</a></p>
    </div>
  </main>
</div>
</body>
</html>
