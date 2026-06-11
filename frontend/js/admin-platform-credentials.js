(function () {
  'use strict';

  var GROUP_TESTS = {
    meta: [
      { platform: 'instagram', label: 'Test Instagram' },
      { platform: 'facebook', label: 'Test Facebook' },
    ],
    tiktok: [{ platform: 'tiktok', label: 'Test TikTok' }],
    youtube: [{ platform: 'youtube', label: 'Test YouTube' }],
    twitter: [{ platform: 'twitter', label: 'Test X' }],
  };

  function esc(v) {
    return Utils.escapeHtml(String(v == null ? '' : v));
  }

  function sourceLabel(source) {
    if (source === 'ui') return 'Saved in admin UI';
    if (source === 'env') return 'From server .env';
    return 'Not set';
  }

  function renderField(field) {
    const key = field.key || '';
    const isSecret = !!field.is_secret;
    const inputType = isSecret ? 'password' : 'text';
    const autocomplete = isSecret ? 'new-password' : 'off';
    const preview = field.preview ? ' <span class="text-muted text-xs">(' + esc(field.preview) + ')</span>' : '';
    const statusClass =
      field.configured && field.source === 'ui'
        ? 'text-success'
        : field.configured
          ? 'text-warning'
          : 'text-danger';

    return (
      '<div class="form-row admin-cred-field" data-cred-field="' +
      esc(key) +
      '">' +
      '<label class="form-label" for="cred_' +
      esc(key) +
      '">' +
      esc(field.label || key) +
      preview +
      '</label>' +
      '<input class="form-input" id="cred_' +
      esc(key) +
      '" name="credential_' +
      esc(key) +
      '" type="' +
      inputType +
      '" autocomplete="' +
      autocomplete +
      '" placeholder="Leave blank to keep current">' +
      (field.help ? '<p class="form-hint">' + esc(field.help) + '</p>' : '') +
      '<p class="form-hint ' +
      statusClass +
      '">Status: ' +
      esc(sourceLabel(field.source)) +
      '</p>' +
      (field.configured
        ? '<label class="form-switch text-sm"><input type="checkbox" name="clear_' +
          esc(key) +
          '" value="1"><span>Remove stored value (UI only)</span></label>'
        : '') +
      '</div>'
    );
  }

  function renderGroup(container, groupData) {
    if (!container || !groupData) return;
    const groupKey = groupData.group || 'meta';
    const fields = groupData.fields || [];
    const tests = GROUP_TESTS[groupKey] || [];

    let html =
      '<form class="form-stack admin-platform-credentials-form" data-cred-group="' +
      esc(groupKey) +
      '">' +
      '<p class="text-sm text-muted">' +
      esc(groupData.description || '') +
      ' Encrypted at rest; .env is used only when a field is empty here.</p>';

    fields.forEach(function (field) {
      html += renderField(field);
    });

    html += '<div class="form-actions" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">';
    html += '<button type="submit" class="btn btn-primary">Save ' + esc(groupData.label || groupKey) + '</button>';
    tests.forEach(function (t) {
      html +=
        '<button type="button" class="btn btn-secondary" data-cred-test="' +
        esc(t.platform) +
        '">' +
        esc(t.label) +
        '</button>';
    });
    html += '</div></form>';

    container.innerHTML = html;

    container.querySelectorAll('[data-cred-test]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const platform = btn.getAttribute('data-cred-test');
        window
          .api('admin_test_integration', 'GET', { platform: platform })
          .then(function (r) {
            window.Toast.success((r && r.message) || 'Connection OK');
          })
          .catch(function (err) {
            window.Toast.error(err.message || 'Test failed');
          });
      });
    });

    const form = container.querySelector('.admin-platform-credentials-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        save(form, groupKey, function (updated) {
          renderGroup(container, updated);
        });
      });
    }
  }

  function renderAll(container, groups) {
    if (!container) return;
    if (!groups || !groups.length) {
      container.innerHTML = '<p class="text-muted text-sm">No credential groups configured.</p>';
      return;
    }

    container.innerHTML = '';
    groups.forEach(function (groupData) {
      const wrap = document.createElement('div');
      wrap.className = 'admin-cred-group';
      wrap.style.marginBottom = '20px';
      wrap.style.paddingBottom = '16px';
      wrap.style.borderBottom = '1px solid var(--color-border, rgba(255,255,255,0.08))';
      const heading = document.createElement('h4');
      heading.className = 'card-title';
      heading.style.marginBottom = '12px';
      heading.textContent = groupData.label || groupData.group || 'Provider';
      wrap.appendChild(heading);
      const inner = document.createElement('div');
      wrap.appendChild(inner);
      container.appendChild(wrap);
      renderGroup(inner, groupData);
    });
  }

  function collectPayload(form, group) {
    const fd = new FormData(form);
    const payload = { group: group };
    fd.forEach(function (value, key) {
      payload[key] = String(value);
    });
    return payload;
  }

  function save(form, group, onUpdated) {
    const payload = collectPayload(form, group);
    return window
      .api('admin_update_platform_credentials', 'POST', payload)
      .then(function (res) {
        const warnings = (res.data && res.data.validation && res.data.validation.warnings) || [];
        if (warnings.length && window.Toast && window.Toast.warning) {
          window.Toast.warning(warnings.join(' '));
        }
        window.Toast.success((res && res.message) || 'Credentials saved');
        const updated = res.data && res.data.credentials ? res.data.credentials : null;
        if (typeof onUpdated === 'function' && updated) {
          onUpdated(updated);
        }
        return res;
      })
      .catch(function (err) {
        window.Toast.error(err.message || 'Could not save credentials');
        throw err;
      });
  }

  window.AdminPlatformCredentials = {
    render: renderGroup,
    renderAll: renderAll,
    save: save,
  };
})();
