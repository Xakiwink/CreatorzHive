<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>?v=2">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/main.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/layout.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/animations.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
</head>
<body>
<div class="app-shell">
  <div id="sidebar-container"></div>
  <div class="main-content">
    <div id="header-container"></div>
    <main class="page-content">
      <div class="settings-shell slide-up">
        <header class="page-header--primary">
          <span class="eyebrow">Admin</span>
          <h1 class="page-heading">Admin</h1>
          <p class="page-lead text-muted">Manage users, platform settings, and system health.</p>
        </header>

        <div class="settings-panels">

          <section id="panel-dashboard" class="settings-panel card card--feature">
            <div class="card-header"><h3 class="card-title">Dashboard</h3></div>
            <div class="card-body">
              <h4 class="settings-subheading">System snapshot</h4>
              <div class="admin-summary-grid" id="adminSummaryCards"></div>

              <hr class="settings-divider">

              <h4 class="settings-subheading">API integrations control</h4>
              <p class="text-muted text-sm mb-3">Enable/disable providers, test credentials, and monitor token expiry risks. Tokens are read from <code>.env</code>.</p>
              <div class="table-wrapper">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Provider</th>
                      <th>Enabled</th>
                      <th>Token</th>
                      <th>Connected</th>
                      <th>Expiring &lt;7d</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="adminIntegrationsBody"></tbody>
                </table>
              </div>
            </div>
          </section>

          <section id="panel-users" class="settings-panel card card--feature d-none">
            <div class="card-header"><h3 class="card-title">User management</h3></div>
            <div class="card-body">
              <h4 class="settings-subheading">Create user</h4>
              <form id="adminCreateUserForm" class="form-stack">
                <div class="form-row"><label class="form-label" for="auName">Full name</label><input class="form-input" id="auName" name="name" required></div>
                <div class="form-row"><label class="form-label" for="auUsername">Username</label><input class="form-input" id="auUsername" name="username" required></div>
                <div class="form-row"><label class="form-label" for="auEmail">Email</label><input class="form-input" id="auEmail" name="email" type="email" required></div>
                <div class="form-row"><label class="form-label" for="auPassword">Password</label><input class="form-input" id="auPassword" name="password" type="password" minlength="8" required></div>
                <div class="form-row">
                  <label class="form-label" for="auRole">Role</label>
                  <select class="form-input" id="auRole" name="role">
                    <option value="creator">Creator</option>
                    <option value="brand">Brand</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
                <div class="form-row form-inline">
                  <label class="form-switch"><input type="checkbox" id="auVerified" name="email_verified" value="1"><span>Email verified</span></label>
                </div>
                <div class="form-row form-inline">
                  <label class="form-switch"><input type="checkbox" id="auActive" name="is_active" value="1" checked><span>Active account</span></label>
                </div>
                <button type="submit" class="btn btn-primary">Create user</button>
              </form>

              <hr class="settings-divider">

              <h4 class="settings-subheading">All users</h4>
              <div class="tab-pills mb-3" id="adminUserFilters" role="group" aria-label="Filter users by status">
                <button type="button" class="tab-pill active" data-filter="all">All</button>
                <button type="button" class="tab-pill" data-filter="active">Active</button>
                <button type="button" class="tab-pill" data-filter="inactive">Inactive</button>
                <button type="button" class="tab-pill" data-filter="unverified">Unverified</button>
                <button type="button" class="tab-pill" data-filter="admin">Admins</button>
              </div>
              <div class="table-wrapper">
                <table class="table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Username</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Verified</th>
                      <th>Active</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="adminUsersBody"></tbody>
                </table>
              </div>
            </div>
          </section>

          <section id="panel-settings" class="settings-panel card card--feature d-none">
            <div class="card-header"><h3 class="card-title">Platform settings</h3></div>
            <div class="card-body">
              <form id="adminSettingsForm" class="form-stack">
                <div class="form-row form-inline">
                  <label class="form-switch"><input type="checkbox" id="asRegistration" name="registration_enabled" value="1"><span>Allow new registrations</span></label>
                </div>
                <div class="form-row form-inline">
                  <label class="form-switch"><input type="checkbox" id="asRequireVerify" name="require_email_verification" value="1"><span>Require email verification before login</span></label>
                </div>
                <div class="form-row form-inline">
                  <label class="form-switch"><input type="checkbox" id="asForgotEnabled" name="forgot_password_enabled" value="1"><span>Enable forgot password / OTP recovery</span></label>
                </div>
                <div class="form-row">
                  <label class="form-label" for="asSiteName">Site display name</label>
                  <input class="form-input" type="text" id="asSiteName" name="site_display_name" maxlength="120" placeholder="CreatorzHive">
                  <p class="form-hint">Shown in emails, footers, and maintenance pages.</p>
                </div>
                <div class="form-row">
                  <label class="form-label" for="asSupportEmail">Support contact email</label>
                  <input class="form-input" type="email" id="asSupportEmail" name="support_email" maxlength="255" placeholder="support@example.com">
                  <p class="form-hint">Optional. Leave blank if you do not publish a support address.</p>
                </div>
                <div class="form-row form-inline">
                  <label class="form-switch"><input type="checkbox" id="asMaintenance" name="maintenance_mode" value="1"><span>Maintenance mode (creators see a downtime message; admins can still sign in)</span></label>
                </div>
                <div class="form-row">
                  <label class="form-label" for="asMaintenanceMsg">Maintenance message</label>
                  <textarea class="form-input" id="asMaintenanceMsg" name="maintenance_message" rows="3" maxlength="2000" placeholder="We are upgrading…"></textarea>
                </div>
                <div class="form-row">
                  <label class="form-label" for="asMaxUpload">Max media upload (MB)</label>
                  <input class="form-input" type="number" id="asMaxUpload" name="max_upload_mb" min="1" max="128" step="1">
                  <p class="form-hint">Policy hint for creators; enforce in reverse proxy or PHP limits as needed.</p>
                </div>
                <div class="form-row">
                  <label class="form-label" for="asAdminNote">Admin operations note</label>
                  <textarea class="form-input" id="asAdminNote" name="admin_note" rows="2" maxlength="500" placeholder="Optional internal note for admins"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save platform settings</button>
              </form>
            </div>
          </section>

          <section id="panel-security" class="settings-panel card card--feature d-none">
            <div class="card-header"><h3 class="card-title">Security &amp; activity</h3></div>
            <div class="card-body">
              <h4 class="settings-subheading">Suspicious login activity</h4>
              <p class="text-muted text-sm mb-3">IP addresses with repeated failed login attempts, per the Terms of Service's ban on unauthorized access attempts.</p>
              <div class="table-wrapper">
                <table class="table">
                  <thead>
                    <tr>
                      <th>IP address</th>
                      <th>Failed attempts</th>
                      <th>Last attempt</th>
                    </tr>
                  </thead>
                  <tbody id="adminSecurityBody"></tbody>
                </table>
              </div>

              <hr class="settings-divider">

              <h4 class="settings-subheading">Recent change log</h4>
              <div class="table-wrapper">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Time</th>
                      <th>Action</th>
                      <th>Actor</th>
                      <th>Target</th>
                      <th>IP</th>
                    </tr>
                  </thead>
                  <tbody id="adminAuditBody"></tbody>
                </table>
              </div>
            </div>
          </section>

        </div>
      </div>
    </main>
  </div>
</div>
<div id="modal-container"></div>
<div id="toast-container"></div>
<?php require __DIR__ . '/../partials/app_script_globals.php'; ?>
<script>window.__ADMIN_PANEL__ = <?= json_encode((string) ($admin_panel ?? 'dashboard')) ?>;</script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/utils.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/app.js')) ?>"></script>
<script src="<?= htmlspecialchars(asset_url('frontend/js/admin-users.js')) ?>"></script>
</body>
</html>
