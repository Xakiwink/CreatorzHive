<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<?php $settingsIsAdmin = session_user_is_admin(); ?>
<div class="app-shell">
  <div id="sidebar-container"></div>
  <div class="main-content">
    <div id="header-container"></div>
    <main class="page-content">
      <div class="settings-shell slide-up">
        <header class="page-header--primary">
          <span class="eyebrow">Account</span>
          <h1 class="page-heading">Settings</h1>
          <p class="page-lead text-muted">Manage your profile, security, and preferences.</p>
        </header>

        <section class="hero-strip hero-strip--quiet" aria-label="Settings overview">
          <h2 class="hero-strip-title">Profile, security, and preferences in one place</h2>
          <p>Open each section from the sidebar — the same navigation works on every screen size.</p>
        </section>

        <div class="settings-layout">
          <div class="settings-panels">
            <!-- Profile -->
            <section id="panel-profile" class="settings-panel card card--feature">
              <div class="card-header"><h3 class="card-title">Profile</h3></div>
              <div class="card-body">
                <div class="profile-avatar-row">
                  <div class="profile-avatar-preview" id="avatarPreview" aria-hidden="true"></div>
                  <div>
                    <label class="btn btn-secondary btn-sm">
                      Change photo
                      <input type="file" id="avatarInput" accept="image/*" class="visually-hidden">
                    </label>
                    <p class="text-muted text-sm mt-1">JPG, PNG, GIF or WebP · max 2MB · resized to 200×200</p>
                  </div>
                </div>
                <form id="profileForm" class="form-stack">
                  <div class="form-row">
                    <label class="form-label" for="pfName">Full name</label>
                    <input class="form-input" type="text" id="pfName" name="name" autocomplete="name" required>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pfUsername">Username</label>
                    <input class="form-input" type="text" id="pfUsername" name="username" autocomplete="username" required>
                    <p class="form-hint" id="usernameHint"></p>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pfEmail">Email</label>
                    <input class="form-input" type="email" id="pfEmail" readonly tabindex="-1">
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pfBio">Bio</label>
                    <textarea class="form-input" id="pfBio" name="bio" rows="3"></textarea>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pfWebsite">Website URL</label>
                    <input class="form-input" type="url" id="pfWebsite" name="website_url" placeholder="https://">
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pfTz">Timezone</label>
                    <input class="form-input" type="text" id="pfTzSearch" placeholder="Search timezones…" autocomplete="off">
                    <select class="form-input mt-2" id="pfTimezone" name="timezone" required></select>
                  </div>
                  <button type="submit" class="btn btn-primary" id="profileSaveBtn">Save changes</button>
                </form>
              </div>
            </section>

            <!-- Security -->
            <section id="panel-security" class="settings-panel card card--feature d-none">
              <div class="card-header"><h3 class="card-title">Security</h3></div>
              <div class="card-body">
                <h4 class="settings-subheading">Change password</h4>
                <form id="passwordForm" class="form-stack">
                  <div class="form-row">
                    <label class="form-label" for="pwCurrent">Current password</label>
                    <input class="form-input" type="password" id="pwCurrent" name="current_password" autocomplete="current-password" required>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pwNew">New password</label>
                    <input class="form-input" type="password" id="pwNew" name="new_password" autocomplete="new-password" required minlength="8">
                    <div class="password-meter" aria-hidden="true"><span id="pwMeterBar"></span></div>
                    <p class="form-hint" id="pwMeterLabel"></p>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="pwConfirm">Confirm new password</label>
                    <input class="form-input" type="password" id="pwConfirm" name="new_password_confirmation" autocomplete="new-password" required>
                  </div>
                  <button type="submit" class="btn btn-primary">Update password</button>
                </form>

                <hr class="settings-divider">

                <h4 class="settings-subheading">Active sessions</h4>
                <p class="text-muted text-sm">Devices where your account is logged in. Revoke access you don’t recognize.</p>
                <div class="table-wrapper">
                  <table class="table" id="sessionsTable">
                    <thead>
                      <tr>
                        <th>Device / browser</th>
                        <th>IP</th>
                        <th>Last active</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id="sessionsBody"></tbody>
                  </table>
                </div>
                <button type="button" class="btn btn-danger btn-sm" id="revokeAllSessionsBtn">Revoke all other sessions</button>
              </div>
            </section>

            <!-- Integrations (creators) / API & platform (admins) -->
            <section id="panel-integrations" class="settings-panel card card--feature d-none">
              <div class="card-header">
                <h3 class="card-title"><?= $settingsIsAdmin ? 'API &amp; platform configuration' : 'Integrations' ?></h3>
              </div>
              <div class="card-body">
                <p class="text-muted text-sm mb-3">
                  <?= $settingsIsAdmin
                    ? 'Configure platform API tokens (Meta, TikTok, YouTube, X), provider toggles, and JSON API details. Full user and audit tools stay under User management.'
                    : 'Connect social accounts to enable publishing and analytics sync.' ?>
                </p>
                <div id="integrationsMount" class="<?= $settingsIsAdmin ? 'admin-api-config-mount' : 'integration-grid' ?>"></div>
              </div>
            </section>

            <!-- Notification prefs -->
            <section id="panel-notifications" class="settings-panel card card--feature d-none">
              <div class="card-header"><h3 class="card-title">Notification preferences</h3></div>
              <div class="card-body">
                <h4 class="settings-subheading">Email notifications</h4>
                <div class="pref-list" id="emailPrefs"></div>
                <h4 class="settings-subheading mt-4">Push notifications</h4>
                <p class="text-muted text-sm">In-app alerts on this device. Browser push may arrive in a later release.</p>
                <div class="pref-list" id="pushPrefs"></div>
              </div>
            </section>

            <!-- App preferences -->
            <section id="panel-preferences" class="settings-panel card card--feature d-none">
              <div class="card-header"><h3 class="card-title">Preferences</h3></div>
              <div class="card-body">
                <form id="prefsForm" class="form-stack">
                  <div class="form-row">
                    <label class="form-label" for="prefTheme">Theme</label>
                    <select class="form-input" id="prefTheme" name="theme">
                      <option value="light">Light</option>
                      <option value="dark">Dark</option>
                      <option value="system">System</option>
                    </select>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="prefLang">Language</label>
                    <input class="form-input" type="text" id="prefLang" name="language" maxlength="10" placeholder="en">
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="prefCurrency">Default currency</label>
                    <input class="form-input" type="text" id="prefCurrency" name="default_currency" maxlength="3" placeholder="TZS">
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="prefDateFmt">Date format</label>
                    <select class="form-input" id="prefDateFmt" name="date_format">
                      <option value="Y-m-d">YYYY-MM-DD</option>
                      <option value="d/m/Y">DD/MM/YYYY</option>
                      <option value="m/d/Y">MM/DD/YYYY</option>
                    </select>
                  </div>
                  <div class="form-row">
                    <label class="form-label" for="prefTimeFmt">Time format</label>
                    <select class="form-input" id="prefTimeFmt" name="time_format">
                      <option value="24h">24-hour</option>
                      <option value="12h">12-hour</option>
                    </select>
                  </div>
                  <div class="form-row form-inline">
                    <label class="form-switch">
                      <input type="checkbox" id="prefWeekStart" name="week_starts_on" value="1">
                      <span>Week starts on Monday</span>
                    </label>
                  </div>
                  <button type="submit" class="btn btn-primary">Save preferences</button>
                </form>
              </div>
            </section>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<div id="modal-container"></div>
<div id="toast-container"></div>
<?php require __DIR__ . '/../partials/app_script_globals.php'; ?>
<script>
  window.__SETTINGS_PANEL__ = <?= json_encode((string) ($settings_panel ?? 'profile')) ?>;
  window.__OAUTH_FLASH__ = <?= json_encode([
      'success' => session_get_flash('oauth_success'),
      'error' => session_get_flash('oauth_error'),
  ]) ?>;
</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/utils.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/app.js')) ?>"></script>
<?php if ($settingsIsAdmin): ?>
<script src="<?= htmlspecialchars(asset_url('frontend/js/admin-platform-credentials.js')) ?>"></script>
<?php endif; ?>
<script src="<?= htmlspecialchars(asset_url('frontend/js/settings.js')) ?>"></script>
</body>
</html>
