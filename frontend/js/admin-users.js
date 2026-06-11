(function () {
  'use strict';

  var bodyEl = document.getElementById('adminUsersBody');
  var createForm = document.getElementById('adminCreateUserForm');
  var settingsForm = document.getElementById('adminSettingsForm');
  var auditBody = document.getElementById('adminAuditBody');
  var summaryCards = document.getElementById('adminSummaryCards');
  var integrationsBody = document.getElementById('adminIntegrationsBody');

  function esc(v) {
    return Utils.escapeHtml(String(v == null ? '' : v));
  }

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

  function summaryCard(title, value) {
    return (
      '<div class="integration-card">' +
      '<div class="integration-card-head"><div><strong>' + esc(title) + '</strong><br><span class="text-muted text-sm">' + esc(value) + '</span></div></div>' +
      '</div>'
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
          summaryCard('Total users', summary.users_total || 0) +
          summaryCard('Active users', summary.users_active || 0) +
          summaryCard('Unverified users', summary.users_unverified || 0) +
          summaryCard('Active sessions', summary.sessions_active || 0) +
          summaryCard('Pending jobs', summary.jobs_pending || 0);
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

      var credMount = document.getElementById('adminPlatformCredentialsMount');
      var credGroups = data.platform_credentials || [];
      if (credMount && window.AdminPlatformCredentials) {
        if (window.AdminPlatformCredentials.renderAll) {
          window.AdminPlatformCredentials.renderAll(credMount, credGroups);
        } else if (credGroups[0]) {
          window.AdminPlatformCredentials.render(credMount, credGroups[0]);
        }
      }
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

  function boot() {
    if (!bodyEl || !createForm) return;
    createForm.addEventListener('submit', createUser);
    if (settingsForm) settingsForm.addEventListener('submit', saveAdminSettings);
    Promise.all([loadUsers(), loadOverview(), loadAuditLogs()]).catch(function (err) {
      window.Toast.error(err.message || 'Could not load users');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
