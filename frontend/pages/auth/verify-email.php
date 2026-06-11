<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
$csrf = htmlspecialchars(session_get('_csrf_token', ''), ENT_QUOTES, 'UTF-8');
$tokenInUrl = isset($_GET['token']) ? trim((string) $_GET['token']) : '';
$hasVerifyToken = $tokenInUrl !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Verify email — CreatorzHive</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/auth.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <aside class="left">
    <div class="auth-brand-inner">
      <div class="auth-logo-row"><span class="auth-logo-mark" aria-hidden="true"></span></div>
      <h1>Verify your email</h1>
      <p class="auth-tagline">One quick step to unlock your dashboard and keep your account secure.</p>
    </div>
  </aside>
  <main class="right">
    <div class="card card--feature">
      <h2>Email verification</h2>
      <div id="verifyPending" class="verify-state<?= $hasVerifyToken ? '' : ' hidden' ?>">
        <div class="verify-icon-wrap verify-icon-wrap--pending" aria-hidden="true">
          <div class="auth-verify-spinner"></div>
        </div>
        <p class="auth-verify-title">Verifying…</p>
        <p class="auth-verify-text">Please wait while we confirm your link.</p>
      </div>
      <div id="verifyMissing" class="verify-state<?= $hasVerifyToken ? ' hidden' : '' ?>">
        <div class="verify-icon-wrap verify-icon-wrap--err" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
        </div>
        <p class="auth-verify-title">No verification token</p>
        <p class="auth-verify-text">Open the link from your email, or paste the URL including <code>?token=</code>.</p>
      </div>
      <div id="verifyOk" class="verify-state hidden">
        <div class="verify-icon-wrap verify-icon-wrap--ok" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <p class="auth-verify-title">Email verified</p>
        <p class="auth-verify-text">Redirecting you to sign in…</p>
      </div>
      <div id="verifyFail" class="verify-state hidden">
        <div class="verify-icon-wrap verify-icon-wrap--err" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        </div>
        <p class="auth-verify-title">Link invalid or expired</p>
        <p class="auth-verify-text">Request a new verification email below.</p>
      </div>
      <div class="resend-box" id="resendSection">
        <h4>Didn’t get an email?</h4>
        <form id="resendForm" novalidate>
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <div class="row">
            <label for="resendEmail">Email address</label>
            <input id="resendEmail" name="email" type="email" autocomplete="email" required placeholder="you@example.com">
          </div>
          <div class="row">
            <button class="btn" type="submit" id="resendBtn"><span class="btn-label">Resend verification email</span></button>
          </div>
          <p id="resendMsg" class="auth-msg" role="status" aria-live="polite"></p>
        </form>
      </div>
      <p class="small mt-4"><a href="<?= htmlspecialchars(route_url('login')) ?>">← Back to sign in</a></p>
    </div>
  </main>
</div>
<script>
window.__BASE_PATH__=<?= json_encode(base_url_path()) ?>;
window.__CSRF__=<?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__VERIFY_TOKEN__=<?= json_encode($tokenInUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/auth.js')) ?>"></script>
</body>
</html>
