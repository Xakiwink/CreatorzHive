<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
$csrf = htmlspecialchars(session_get('_csrf_token', ''), ENT_QUOTES, 'UTF-8');
$token = isset($_GET['token']) ? htmlspecialchars((string) $_GET['token'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>New password — CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/auth.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <aside class="left">
    <div class="auth-brand-inner">
      <div class="auth-logo-row"><img class="auth-logo-mark" src="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2" alt=""></div>
      <h1>Choose a new password</h1>
      <p class="auth-tagline">Use at least 8 characters and mix letters with numbers or symbols.</p>
    </div>
  </aside>
  <main class="right">
    <div class="card card--feature">
      <h2>Reset password</h2>
      <p class="auth-sub">Enter your email, 6-digit OTP code, and your new password.</p>
      <form id="resetForm" novalidate>
        <input type="hidden" name="_token" value="<?= $csrf ?>">
        <?php if ($token !== ''): ?>
          <input type="hidden" name="token" value="<?= $token ?>">
        <?php endif; ?>
        <div class="row">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com">
        </div>
        <div class="row">
          <label for="otp">OTP code</label>
          <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="123456">
          <span class="small">Check your email for the 6-digit code. OTP expires in 10 minutes.</span>
        </div>
          <div class="row">
            <label for="password">New password</label>
            <div class="password-field">
              <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Min. 8 characters">
              <button type="button" class="password-toggle" id="toggleResetPassword" aria-label="Show password">Show</button>
            </div>
            <div class="strength-meter" id="resetStrengthMeter" data-level="0">
              <div class="strength" aria-hidden="true"><span id="resetStrengthBar"></span></div>
              <span class="strength-label" id="resetStrengthLabel"></span>
            </div>
          </div>
          <div class="row">
            <label for="password_confirmation">Confirm password</label>
            <div class="password-field">
              <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Repeat password">
              <button type="button" class="password-toggle" id="toggleResetPassword2" aria-label="Show confirm password">Show</button>
            </div>
          </div>
          <div class="row">
            <button class="btn" id="resetBtn" type="submit"><span class="btn-label">Update password</span></button>
          </div>
          <p id="formMsg" class="auth-msg" role="status" aria-live="polite"></p>
      </form>
      <p class="small"><a href="<?= htmlspecialchars(route_url('login')) ?>">← Back to sign in</a></p>
    </div>
  </main>
</div>
<script>window.__BASE_PATH__=<?= json_encode(base_url_path()) ?>;window.__CSRF__=<?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/auth.js')) ?>"></script>
</body>
</html>
