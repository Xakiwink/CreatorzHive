<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
$csrf = htmlspecialchars(session_get('_csrf_token', ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Reset password — CreatorzHive</title>
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
      <h1>Reset your password</h1>
      <p class="auth-tagline">We’ll email a 6-digit OTP code to your inbox.</p>
    </div>
  </aside>
  <main class="right">
    <div class="card card--feature">
      <div id="forgotFormWrap">
        <h2>Forgot password</h2>
        <p class="auth-sub">Enter the email you used to register. We will send a one-time code.</p>
        <form id="forgotForm" novalidate>
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <div class="row">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com">
            <span class="small">The OTP expires in 10 minutes. You can request a new code after 60 seconds.</span>
          </div>
          <div class="row">
            <button class="btn" id="forgotBtn" type="submit"><span class="btn-label">Send OTP code</span></button>
          </div>
          <p id="formMsg" class="auth-msg" role="status" aria-live="polite"></p>
        </form>
        <p class="small"><a href="<?= htmlspecialchars(route_url('login')) ?>">← Back to sign in</a></p>
      </div>
      <div id="forgotSuccessPanel" class="hidden">
        <div class="auth-success-banner">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
          <div>
            <h3>Check your inbox</h3>
            <p>If an account exists for that address, we sent a reset OTP code. Didn’t see it? Look in spam or try again in a few minutes.</p>
          </div>
        </div>
        <p class="small"><a href="<?= htmlspecialchars(route_url('login')) ?>">Return to sign in</a></p>
      </div>
    </div>
  </main>
</div>
<script>window.__BASE_PATH__=<?= json_encode(base_url_path()) ?>;window.__CSRF__=<?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/auth.js')) ?>"></script>
</body>
</html>
