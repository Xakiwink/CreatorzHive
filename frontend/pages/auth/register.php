<?php
require_once dirname(__DIR__, 3) . '/backend/bootstrap-web-view.php';
$csrf = htmlspecialchars(session_get('_csrf_token', ''), ENT_QUOTES, 'UTF-8');
$authError = session_get_flash('auth_error');
$googleAuthStartUrl = google_auth_start_url('creator');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Create account — CreatorzHive</title>
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
      <span class="eyebrow auth-brand-eyebrow">Join</span>
      <h1>Join CreatorzHive</h1>
      <p class="auth-tagline">Built for creators and brands who treat content like a business.</p>
    </div>
  </aside>
  <main class="right">
    <div class="card card--feature">
      <div id="registerFormPanel">
        <span class="eyebrow auth-form-eyebrow">Registration</span>
        <h2>Create account</h2>
        <p class="auth-sub">Fill in your details — we’ll send a verification email.</p>
        <form id="registerForm" novalidate>
          <input type="hidden" name="_token" value="<?= $csrf ?>">
          <input type="hidden" id="role" name="role" value="creator">
          <div class="row">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" required maxlength="255" placeholder="Jane Creator">
          </div>
          <div class="row">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" autocomplete="username" required minlength="3" maxlength="100" placeholder="janecreates" pattern="[a-zA-Z0-9_\-\.]+" title="Letters, numbers, dots, dashes, underscores">
            <span class="small">Only letters, numbers, _, - and .</span>
          </div>
          <div class="row">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com">
          </div>
          <div class="row">
            <label for="password">Password</label>
            <div class="password-field">
              <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Min. 8 characters">
              <button type="button" class="password-toggle" id="toggleRegPassword" aria-label="Show password">Show</button>
            </div>
            <div class="strength-meter" id="strengthMeter" data-level="0">
              <div class="strength" aria-hidden="true"><span id="strengthBar"></span></div>
              <span class="strength-label" id="strengthLabel"></span>
            </div>
          </div>
          <div class="row">
            <label for="password_confirmation">Confirm password</label>
            <div class="password-field">
              <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Repeat password">
              <button type="button" class="password-toggle" id="toggleRegPassword2" aria-label="Show confirm password">Show</button>
            </div>
          </div>
          <div class="row">
            <span class="form-label-like">I am a</span>
            <div class="role-toggle" role="group" aria-label="Account type">
              <button type="button" class="role-btn active" data-role="creator">Creator</button>
              <button type="button" class="role-btn" data-role="brand">Brand</button>
            </div>
          </div>
          <div class="row">
            <label class="auth-checkbox-label auth-terms-label"><input type="checkbox" name="terms" id="terms" class="auth-checkbox" value="1" required> I agree to the <a href="#" onclick="return false;">Terms</a> and <a href="#" onclick="return false;">Privacy Policy</a></label>
          </div>
          <div class="row">
            <button class="btn" id="registerBtn" type="submit"><span class="btn-label">Create account</span></button>
          </div>
          <p id="formMsg" class="auth-msg" role="status" aria-live="polite"><?php
            if (is_string($authError) && $authError !== '') {
                echo '<span class="auth-msg-error">' . htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') . '</span>';
            }
          ?></p>
          <div class="social-row">
            <p class="social-row-label">Or continue with</p>
            <div class="social-buttons social-buttons--single">
              <a class="btn-google" id="googleRegisterBtn" href="<?= htmlspecialchars($googleAuthStartUrl) ?>">Continue with Google</a>
            </div>
          </div>
          <p class="small">Already have an account? <a href="<?= htmlspecialchars(route_url('login')) ?>">Sign in</a></p>
        </form>
      </div>
      <div id="registerSuccessPanel" class="hidden">
        <div class="auth-success-banner">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
          <div>
            <h3>Check your email</h3>
            <p>We sent a verification link. Open it on this device to activate your account. The link expires in 24 hours.</p>
          </div>
        </div>
        <p class="small"><a href="<?= htmlspecialchars(route_url('login')) ?>">Back to sign in</a></p>
      </div>
    </div>
  </main>
</div>
<script>window.__BASE_PATH__=<?= json_encode(base_url_path()) ?>;window.__CSRF__=<?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/auth.js')) ?>"></script>
</body>
</html>
