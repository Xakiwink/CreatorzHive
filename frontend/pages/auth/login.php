<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
$csrf = htmlspecialchars(session_get('_csrf_token', ''), ENT_QUOTES, 'UTF-8');
$authError = session_get_flash('auth_error');
$authSuccess = session_get_flash('auth_success');
$googleAuthStartUrl = google_auth_start_url('creator');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Sign in — CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/auth.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <aside class="left" aria-hidden="false">
    <div class="auth-brand-inner">
      <div class="auth-logo-row">
        <img class="auth-logo-mark" src="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2" alt="">
      </div>
      <span class="eyebrow auth-brand-eyebrow">Account</span>
      <h1>Welcome back</h1>
      <p class="auth-tagline">Plan content, track analytics, and grow deals — all in one calm workspace.</p>
    </div>
  </aside>
  <main class="right">
    <div class="card card--feature">
      <span class="eyebrow auth-form-eyebrow">Secure access</span>
      <h2>Sign in</h2>
      <p class="auth-sub">Enter your credentials to continue.</p>
      <form id="loginForm" novalidate>
        <input type="hidden" name="_token" value="<?= $csrf ?>">
        <div class="row">
          <label for="email">Email or username</label>
          <input id="email" name="email" type="text" autocomplete="username" required placeholder="you@example.com or yourusername">
        </div>
        <div class="row">
          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
            <button type="button" class="password-toggle" id="toggleLoginPassword" aria-label="Show password" aria-pressed="false">Show</button>
          </div>
        </div>
        <div class="links">
          <label class="auth-checkbox-label"><input type="checkbox" name="remember" id="rememberMe" class="auth-checkbox" value="1"> Remember me</label>
          <a href="<?= htmlspecialchars(route_url('forgot-password')) ?>">Forgot password?</a>
        </div>
        <div class="row">
          <button class="btn" id="loginBtn" type="submit"><span class="btn-label">Sign In</span></button>
        </div>
        <p id="formMsg" class="auth-msg" role="status" aria-live="polite"><?php
          if (is_string($authError) && $authError !== '') {
              echo '<span class="auth-msg-error">' . htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') . '</span>';
          } elseif (is_string($authSuccess) && $authSuccess !== '') {
              echo '<span class="auth-msg-success">' . htmlspecialchars($authSuccess, ENT_QUOTES, 'UTF-8') . '</span>';
          }
        ?></p>
        <div class="social-row">
          <p class="social-row-label">Or continue with</p>
          <div class="social-buttons social-buttons--single">
            <a class="btn-google" href="<?= htmlspecialchars($googleAuthStartUrl) ?>">Google</a>
          </div>
        </div>
        <p class="small">No account? <a href="<?= htmlspecialchars(route_url('register')) ?>">Create one</a></p>
      </form>
    </div>
  </main>
</div>
<script>window.__BASE_PATH__=<?= json_encode(base_url_path()) ?>;window.__CSRF__=<?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/auth.js')) ?>"></script>
</body>
</html>
