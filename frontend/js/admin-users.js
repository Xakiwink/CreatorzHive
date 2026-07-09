(function () {
  'use strict';

  var bodyEl = document.getElementById('adminUsersBody');
  var createForm = document.getElementById('adminCreateUserForm');
  var settingsForm = document.getElementById('adminSettingsForm');
  var auditBody = document.getElementById('adminAuditBody');
  var securityBody = document.getElementById('adminSecurityBody');
  var summaryCards = document.getElementById('adminSummaryCards');
  var integrationsBody = document.getElementById('adminIntegrationsBody');

  function esc(v) {
    return Utils.escapeHtml(String(v == null ? '' : v));
  }

  // Line icons matching the sidebar's icon language (frontend/components/sidebar.html) --
  // reused verbatim (users/security/notifications nav icons, dashboard's check/clock) for
  // visual consistency instead of plain unillustrated summary tiles.
  var SUMMARY_ICONS = {
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.742-.477 3 3 0 00-4.682-2.72m.94 3.198v.75c0 .414-.336.75-.75.75H5.25a.75.75 0 01-.75-.75v-.75m13.5 0a9.09 9.09 0 01-13.5 0m13.5 0a9.09 9.09 0 00-13.5 0m13.5 0v-1.35a3 3 0 00-3-3h-1.5m-7.5 4.35v-1.35a3 3 0 013-3H7.5m0 0A3 3 0 104.5 9.75 3 3 0 007.5 13.5zm9 0a3 3 0 100-6 3 3 0 000 6z" /></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    bell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>',
    security: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
  };

  function boolToText(v) {
    return parseInt(v, 10) === 1 ? 'Yes' : 'No';
  }

  function buildRow(user) {
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td>' + esc(user.id) + '</td>' +
      '<td><input class="form-input" data-field="name" value="' + esc(user.name) + '"></td>' +
      '<td><input class="form-input" data-field="username" value="' + esc(user.username) + '"></td>' +
      '<td><input class="form-input" data-field="email" value="' + esc(user.email) + '"></td>' +
      '<td><select class="form-input" data-field="role">' +
      '<option value="creator">Creator</option>' +
      '<option value="brand">Brand</option>' +
      '<option value="admin">Admin</option>' +
      '</select></td>' +
      '<td>' + esc(boolToText(user.email_verified)) + '</td>' +
      '<td>' + esc(boolToText(user.is_active)) + '</td>' +
      '<td>' +
      '<button class="btn btn-sm btn-secondary" data-action="save">Save</button> ' +
      '<button class="btn btn-sm btn-secondary" data-action="verify">Verify</button> ' +
      '<button class="btn btn-sm btn-danger" data-action="delete">Delete</button>' +
      '</td>';

    var roleSelect = tr.querySelector('select[data-field="role"]');
    if (roleSelect) roleSelect.value = String(user.role || 'creator');

    tr.querySelector('[data-action="save"]').addEventListener('click', function () {
      saveUser(user.id, tr);
    });
    tr.querySelector('[data-action="verify"]').addEventListener('click', function () {
      verifyUser(user.id);
    });
    tr.querySelector('[data-action="delete"]').addEventListener('click', function () {
      deleteUser(user.id);
    });

    return tr;
  }

  function collectRowPayload(tr) {
    return {
      name: tr.querySelector('[data-field="name"]').value.trim(),
      username: tr.querySelector('[data-field="username"]').value.trim(),
      email: tr.querySelector('[data-field="email"]').value.trim(),
      role: tr.querySelector('[data-field="role"]').value,
    };
  }

  function loadUsers() {
    return window.api('admin_users', 'GET').then(function (res) {
      bodyEl.innerHTML = '';
      var users = (res.data && res.data.users) || [];
      users.forEach(function (user) {
        bodyEl.appendChild(buildRow(user));
      });
    });
  }

  function summaryCard(title, value, icon) {
    return (
      '<div class="integration-card">' +
      '<div class="integration-card-head">' +
      '<span class="integration-icon integration-icon--admin">' + (SUMMARY_ICONS[icon] || '') + '</span>' +
      '<div><strong>' + esc(title) + '</strong><br><span class="text-muted text-sm">' + esc(value) + '</span></div>' +
      '</div></div>'
    );
  }

  function loadOverview() {
    return window.api('admin_overview', 'GET').then(function (res) {
      var data = res.data || {};
      var settings = data.settings || {};
      var summary = data.summary || {};
      var integrations = data.integrations || [];

      if (document.getElementById('asRegistration')) document.getElementById('asRegistration').checked = !!settings.registration_enabled;
      if (document.getElementById('asRequireVerify')) document.getElementById('asRequireVerify').checked = !!settings.require_email_verification;
      if (document.getElementById('asForgotEnabled')) document.getElementById('asForgotEnabled').checked = !!settings.forgot_password_enabled;
      if (document.getElementById('asSiteName')) document.getElementById('asSiteName').value = String(settings.site_display_name || '');
      if (document.getElementById('asSupportEmail')) document.getElementById('asSupportEmail').value = String(settings.support_email || '');
      if (document.getElementById('asMaintenance')) document.getElementById('asMaintenance').checked = !!settings.maintenance_mode;
      if (document.getElementById('asMaintenanceMsg')) document.getElementById('asMaintenanceMsg').value = String(settings.maintenance_message || '');
      if (document.getElementById('asMaxUpload')) {
        document.getElementById('asMaxUpload').value = String(settings.max_upload_mb != null ? settings.max_upload_mb : 2);
      }
      if (document.getElementById('asAdminNote')) document.getElementById('asAdminNote').value = String(settings.admin_note || '');

      if (summaryCards) {
        summaryCards.innerHTML =
          summaryCard('Total users', summary.users_total || 0, 'users') +
          summaryCard('Active users', summary.users_active || 0, 'check') +
          summaryCard('Unverified users', summary.users_unverified || 0, 'bell') +
          summaryCard('Active sessions', summary.sessions_active || 0, 'security') +
          summaryCard('Pending jobs', summary.jobs_pending || 0, 'clock');
      }

      if (integrationsBody) {
        integrationsBody.innerHTML = '';
        integrations.forEach(function (row) {
          var tr = document.createElement('tr');
          var toggleKey = 'integration_enabled_' + row.platform;
          tr.innerHTML =
            '<td>' + esc(row.label || row.platform) + '</td>' +
            '<td><label class="form-switch"><input type="checkbox" data-int-toggle="' + esc(toggleKey) + '"' + (row.enabled ? ' checked' : '') + '><span>' + (row.enabled ? 'On' : 'Off') + '</span></label></td>' +
            '<td>' + (row.token_configured
              ? '<span class="text-success">' + esc(row.token_source === 'ui' ? 'UI' : row.token_source === 'env' ? '.env' : 'OK') + '</span>'
              : '<span class="text-danger">Missing</span>') + '</td>' +
            '<td>' + esc(row.connected_accounts || 0) + '</td>' +
            '<td>' + (parseInt(row.expiring_soon, 10) > 0 ? '<span class="text-warning">' + esc(row.expiring_soon) + ' warning</span>' : '<span class="text-muted">0</span>') + '</td>' +
            '<td><button class="btn btn-sm btn-secondary" data-int-test="' + esc(row.platform) + '">Test connection</button></td>';
          integrationsBody.appendChild(tr);
        });

        integrationsBody.querySelectorAll('[data-int-test]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var platform = btn.getAttribute('data-int-test');
            window.api('admin_test_integration', 'GET', { platform: platform }).then(function (r) {
              window.Toast.success((r && r.message) || 'Connection test passed');
            }).catch(function (err) {
              window.Toast.error(err.message || 'Connection test failed');
            });
          });
        });
      }
    });
  }

  function loadSecurityActivity() {
    if (!securityBody) return Promise.resolve();
    return window.api('admin_security_activity', 'GET').then(function (res) {
      securityBody.innerHTML = '';
      var logins = (res.data && res.data.logins) || [];
      if (logins.length === 0) {
        securityBody.innerHTML = '<tr><td colspan="3" class="text-muted text-sm">No repeated failed login attempts recorded.</td></tr>';
        return;
      }
      logins.forEach(function (row) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + esc(row.ip) + '</td>' +
          '<td>' + esc(row.attempts) + '</td>' +
          '<td>' + esc(Utils.formatDate(row.last_attempt, 'medium')) + '</td>';
        securityBody.appendChild(tr);
      });
    });
  }

  function loadAuditLogs() {
    if (!auditBody) return Promise.resolve();
    return window.api('admin_audit_logs', 'GET', { limit: '100' }).then(function (res) {
      auditBody.innerHTML = '';
      var logs = (res.data && res.data.logs) || [];
      logs.forEach(function (row) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + esc(Utils.formatDate(row.created_at, 'medium')) + '</td>' +
          '<td>' + esc(row.action || '') + '</td>' +
          '<td>' + esc(row.actor_email || ('#' + (row.user_id || 'system'))) + '</td>' +
          '<td>' + esc((row.entity_type || '-') + (row.entity_id ? ':' + row.entity_id : '')) + '</td>' +
          '<td>' + esc(row.ip_address || '-') + '</td>';
        auditBody.appendChild(tr);
      });
    });
  }

  function saveUser(id, tr) {
    var payload = collectRowPayload(tr);
    payload.id = String(id);
    window.api('admin_update_user', 'POST', payload).then(function () {
      window.Toast.success('User updated');
      Promise.all([loadUsers(), loadOverview(), loadAuditLogs()]);
    }).catch(function (err) {
      window.Toast.error(err.message || 'Update failed');
    });
  }

  function verifyUser(id) {
    window.api('admin_verify_user', 'POST', { id: String(id) }).then(function () {
      window.Toast.success('User verified');
      Promise.all([loadUsers(), loadOverview(), loadAuditLogs()]);
    }).catch(function (err) {
      window.Toast.error(err.message || 'Verify failed');
    });
  }

  function deleteUser(id) {
    if (!window.confirm('Delete this user account?')) return;
    window.api('admin_delete_user', 'POST', { id: String(id) }).then(function () {
      window.Toast.success('User deleted');
      Promise.all([loadUsers(), loadOverview(), loadAuditLogs()]);
    }).catch(function (err) {
      window.Toast.error(err.message || 'Delete failed');
    });
  }

  function saveAdminSettings(e) {
    e.preventDefault();
    var payload = {
      registration_enabled: document.getElementById('asRegistration').checked ? '1' : '0',
      require_email_verification: document.getElementById('asRequireVerify').checked ? '1' : '0',
      forgot_password_enabled: document.getElementById('asForgotEnabled').checked ? '1' : '0',
      site_display_name: document.getElementById('asSiteName') ? document.getElementById('asSiteName').value.trim() : '',
      support_email: document.getElementById('asSupportEmail') ? document.getElementById('asSupportEmail').value.trim() : '',
      maintenance_mode: document.getElementById('asMaintenance') && document.getElementById('asMaintenance').checked ? '1' : '0',
      maintenance_message: document.getElementById('asMaintenanceMsg') ? document.getElementById('asMaintenanceMsg').value.trim() : '',
      max_upload_mb: document.getElementById('asMaxUpload') ? String(document.getElementById('asMaxUpload').value || '2') : '2',
      admin_note: document.getElementById('asAdminNote').value.trim(),
    };
    if (integrationsBody) {
      integrationsBody.querySelectorAll('[data-int-toggle]').forEach(function (input) {
        var key = input.getAttribute('data-int-toggle');
        if (key) payload[key] = input.checked ? '1' : '0';
      });
    }
    window.api('admin_update_settings', 'POST', payload).then(function () {
      window.Toast.success('Platform settings updated');
      Promise.all([loadOverview(), loadAuditLogs()]);
    }).catch(function (err) {
      window.Toast.error(err.message || 'Settings update failed');
    });
  }

  function createUser(e) {
    e.preventDefault();
    var fd = new FormData(createForm);
    var payload = {
      name: String(fd.get('name') || '').trim(),
      username: String(fd.get('username') || '').trim(),
      email: String(fd.get('email') || '').trim(),
      password: String(fd.get('password') || ''),
      role: String(fd.get('role') || 'creator'),
      email_verified: fd.get('email_verified') ? '1' : '0',
      is_active: fd.get('is_active') ? '1' : '0',
    };
    window.api('admin_create_user', 'POST', payload).then(function () {
      window.Toast.success('User created');
      createForm.reset();
      document.getElementById('auActive').checked = true;
      Promise.all([loadUsers(), loadOverview(), loadAuditLogs()]);
    }).catch(function (err) {
      window.Toast.error(err.message || 'Create failed');
    });
  }

  function initAdminTabs() {
    var panels = document.querySelectorAll('.settings-panel');
    var activePanel = String(window.__ADMIN_PANEL__ || 'users');
    panels.forEach(function (p) {
      p.classList.toggle('d-none', p.id !== 'panel-' + activePanel);
    });
  }

  function boot() {
    if (!bodyEl || !createForm) return;
    initAdminTabs();
    createForm.addEventListener('submit', createUser);
    if (settingsForm) settingsForm.addEventListener('submit', saveAdminSettings);
    Promise.all([loadUsers(), loadOverview(), loadAuditLogs(), loadSecurityActivity()]).catch(function (err) {
      window.Toast.error(err.message || 'Could not load users');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
