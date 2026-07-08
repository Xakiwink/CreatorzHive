(function () {
  const EMAIL_PREF_FIELDS = [
    { key: 'email_post_published', label: 'Post published' },
    { key: 'email_post_failed', label: 'Post failed' },
    { key: 'email_deal_updated', label: 'Deal updated' },
    { key: 'email_invoice_paid', label: 'Invoice paid' },
    { key: 'email_weekly_summary', label: 'Weekly summary' },
  ];
  const PUSH_PREF_FIELDS = [
    { key: 'push_post_published', label: 'Post published' },
    { key: 'push_deal_updated', label: 'Deal updated' },
  ];

  // Same glyphs as the Planner composer's platform cards/preview tabs
  // (frontend/pages/planner/post-create.html) -- kept in sync for icon consistency.
  const PLATFORM_ICONS = {
    instagram:
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
    tiktok:
      '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.81a8.18 8.18 0 004.78 1.52V6.89a4.85 4.85 0 01-1.01-.2z"/></svg>',
    youtube:
      '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 001.46 6.42 29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.4a2.78 2.78 0 001.95-1.97A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>',
    twitter:
      '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
  };

  const PLATFORMS = [
    { id: 'instagram', name: 'Instagram' },
    { id: 'tiktok', name: 'TikTok' },
    { id: 'youtube', name: 'YouTube' },
    { id: 'twitter', name: 'X / Twitter' },
  ];

  let oauthPlatforms = [];

  function isAdminRole() {
    return String((window.__USER__ || {}).role || '').toLowerCase() === 'admin';
  }

  function routeUrl(route, query) {
    return window.routeQuery(route, query || {});
  }

  async function apiForm(routeName, formData) {
    formData.append('_token', window.__CSRF__ || '');
    const r = await fetch(routeUrl(routeName), {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    });
    const text = await r.text();
    let j;
    try {
      j = JSON.parse(text);
    } catch (parseErr) {
      const hint = text.replace(/\s+/g, ' ').trim().slice(0, 160);
      throw new Error(
        'Server returned non-JSON (HTTP ' +
          r.status +
          '). ' +
          (hint ? hint : 'empty body') +
          ' — check the request URL matches this app (subdirectory / base path).'
      );
    }
    if (!j.success) {
      let msg = j.message || 'Request failed';
      if (j.errors && typeof j.errors === 'object') {
        const parts = [];
        Object.keys(j.errors).forEach(function (k) {
          const arr = j.errors[k];
          if (Array.isArray(arr)) {
            arr.forEach(function (e) {
              parts.push(String(e));
            });
          }
        });
        if (parts.length) {
          msg = parts.join(' ');
        }
      }
      throw new Error(msg);
    }
    return j;
  }

  /** Persist choice as light | dark | system; DOM follows via syncThemeFromStorage (app.js). */
  function applyThemeFromPrefs(theme) {
    const t = theme === 'dark' || theme === 'system' || theme === 'light' ? theme : 'system';
    localStorage.setItem('theme', t);
    if (typeof window.syncThemeFromStorage === 'function') {
      window.syncThemeFromStorage();
    } else {
      const eff =
        t === 'system'
          ? window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light'
          : t;
      document.documentElement.setAttribute('data-theme', eff);
    }
  }

  function loadTimezones(select, searchInput, current) {
    let zones =
      typeof Intl !== 'undefined' && typeof Intl.supportedValuesOf === 'function'
        ? Intl.supportedValuesOf('timeZone')
        : ['UTC', 'Africa/Dar_es_Salaam', 'Europe/London', 'America/New_York', 'Asia/Tokyo'];

    function render(filter) {
      const q = (filter || '').toLowerCase().trim();
      select.innerHTML = '';
      zones
        .filter(function (z) {
          return !q || z.toLowerCase().indexOf(q) !== -1;
        })
        .forEach(function (z) {
          const opt = document.createElement('option');
          opt.value = z;
          opt.textContent = z;
          if (z === current) opt.selected = true;
          select.appendChild(opt);
        });
      if (!select.value && current) {
        const opt = document.createElement('option');
        opt.value = current;
        opt.textContent = current;
        opt.selected = true;
        select.appendChild(opt);
      }
    }

    render('');
    searchInput.addEventListener('input', function () {
      render(searchInput.value);
    });
  }

  async function loadProfile() {
    const res = await window.api('profile_data', 'GET');
    const d = res.data || {};
    const u = d.user || {};
    const prefs = d.preferences || {};

    document.getElementById('pfName').value = u.name || '';
    document.getElementById('pfUsername').value = u.username || '';
    document.getElementById('pfEmail').value = u.email || '';
    document.getElementById('pfBio').value = u.bio || '';
    document.getElementById('pfWebsite').value = u.website_url || '';

    const tzSel = document.getElementById('pfTimezone');
    const tzSearch = document.getElementById('pfTzSearch');
    loadTimezones(tzSel, tzSearch, u.timezone || 'Africa/Dar_es_Salaam');

    const preview = document.getElementById('avatarPreview');
    const av = u.avatar_url || window.__USER__.avatar_url;
    if (preview && av) {
      preview.style.backgroundImage = 'url("' + String(av).replace(/"/g, '\\"') + '")';
      preview.style.backgroundSize = 'cover';
      preview.style.backgroundPosition = 'center';
    }

    document.getElementById('prefTheme').value = prefs.theme || 'system';
    var pt = prefs.theme;
    if (
      localStorage.getItem('theme') === null &&
      (pt === 'light' || pt === 'dark' || pt === 'system')
    ) {
      localStorage.setItem('theme', pt);
      if (typeof window.syncThemeFromStorage === 'function') {
        window.syncThemeFromStorage();
      }
    }
    document.getElementById('prefLang').value = prefs.language || 'en';
    document.getElementById('prefCurrency').value = prefs.default_currency || 'TZS';
    document.getElementById('prefDateFmt').value = prefs.date_format || 'Y-m-d';
    document.getElementById('prefTimeFmt').value = prefs.time_format || '24h';
    document.getElementById('prefWeekStart').checked = parseInt(prefs.week_starts_on, 10) === 1;

    if (typeof window.syncThemeFromStorage === 'function') {
      window.syncThemeFromStorage();
    }
  }

  function previewAvatar(file) {
    if (!file || !file.type.match(/^image\//)) return;
    const reader = new FileReader();
    reader.onload = function () {
      const preview = document.getElementById('avatarPreview');
      if (preview) {
        preview.style.backgroundImage = 'url("' + String(reader.result).replace(/"/g, '\\"') + '")';
        preview.style.backgroundSize = 'cover';
      }
    };
    reader.readAsDataURL(file);
  }

  async function submitProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('profileSaveBtn');
    btn.classList.add('loading');
    btn.disabled = true;
    try {
      const fd = new FormData(document.getElementById('profileForm'));
      const fileInput = document.getElementById('avatarInput');
      if (fileInput && fileInput.files && fileInput.files[0]) {
        fd.append('avatar', fileInput.files[0]);
      }
      const res = await apiForm('update_profile', fd);
      const u = res.data && res.data.user;
      if (u && window.hydrateSidebarUser) {
        window.hydrateSidebarUser({
          name: u.name,
          username: u.username,
          role: u.role,
          avatar_url: u.avatar_url || '',
        });
      }
      window.Toast.success('Profile updated');
    } catch (err) {
      window.Toast.error(err.message || 'Update failed');
    } finally {
      btn.classList.remove('loading');
      btn.disabled = false;
    }
  }

  function debounceCheckUsername() {
    const input = document.getElementById('pfUsername');
    const hint = document.getElementById('usernameHint');
    const uid = (window.__USER__ && window.__USER__.id) || 0;
    const run = Utils.debounce(function () {
      const v = (input.value || '').trim();
      if (v.length < 3) {
        hint.textContent = '';
        return;
      }
      window
        .api('check_username', 'GET', {
          username: v,
          exclude_user_id: String(uid),
        })
        .then(function (r) {
          const ok = r.data && r.data.available;
          hint.textContent = ok ? 'Username is available' : 'Username is already taken';
          hint.style.color = ok ? 'var(--color-success)' : 'var(--color-danger)';
        })
        .catch(function () {
          hint.textContent = '';
        });
    }, 400);
    input.addEventListener('input', run);
  }

  function passwordStrength(pw) {
    let s = 0;
    if (pw.length >= 8) s++;
    if (pw.length >= 12) s++;
    if (/[0-9]/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++;
    return Math.min(4, s);
  }

  function bindPasswordMeter() {
    const pw = document.getElementById('pwNew');
    const bar = document.getElementById('pwMeterBar');
    const label = document.getElementById('pwMeterLabel');
    const wrap = bar && bar.parentElement;
    const levels = ['weak', 'fair', 'good', 'strong'];
    pw.addEventListener('input', function () {
      const lvl = passwordStrength(pw.value || '');
      const pct = (lvl / 4) * 100;
      bar.style.width = pct + '%';
      if (wrap) {
        wrap.className = 'password-meter';
        const idx = Math.min(3, Math.max(0, lvl - 1));
        if (pw.value) {
          wrap.classList.add('password-meter--' + levels[idx]);
        }
      }
      const labels = ['Too weak', 'Fair', 'Good', 'Strong'];
      const idx = Math.min(3, Math.max(0, lvl - 1));
      label.textContent = pw.value ? labels[idx] : '';
    });
  }

  async function submitPassword(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('passwordForm'));
    try {
      await apiForm('update_password', fd);
      document.getElementById('passwordForm').reset();
      window.Toast.success('Password updated');
    } catch (err) {
      window.Toast.error(err.message || 'Could not update password');
    }
  }

  function parseUa(ua) {
    const s = String(ua || 'Unknown');
    if (s.length > 80) return s.slice(0, 77) + '…';
    return s;
  }

  async function loadSessions() {
    const res = await window.api('user_sessions', 'GET');
    const body = document.getElementById('sessionsBody');
    body.innerHTML = '';
    const sessions = res.data.sessions || [];
    const cur = res.data.current_session_id || '';
    sessions.forEach(function (row) {
      const sid = String(row.id || '');
      const isCur = !!row.is_current || sid === String(cur);
      const tr = document.createElement('tr');
      if (isCur) tr.classList.add('session-current');
      tr.innerHTML =
        '<td>' +
        Utils.escapeHtml(parseUa(row.user_agent)) +
        '</td>' +
        '<td>' +
        Utils.escapeHtml(row.ip_address || '—') +
        '</td>' +
        '<td>' +
        Utils.escapeHtml(Utils.formatDate(row.last_active, 'medium') + ' · ' + Utils.timeAgo(row.last_active)) +
        '</td>' +
        '<td></td>';
      const td = tr.querySelector('td:last-child');
      if (!isCur) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-ghost btn-sm';
        b.textContent = 'Revoke';
        b.onclick = function () {
          revokeSession(sid);
        };
        td.appendChild(b);
      } else {
        td.textContent = 'Current';
      }
      body.appendChild(tr);
    });
  }

  async function revokeSession(sessionId) {
    try {
      await window.api('revoke_session', 'POST', { session_id: sessionId });
      window.Toast.success('Session revoked');
      await loadSessions();
    } catch (e) {
      window.Toast.error(e.message || 'Revoke failed');
    }
  }

  async function revokeAllOthers() {
    try {
      await window.api('revoke_all_sessions', 'POST', {});
      window.Toast.success('Other sessions revoked');
      await loadSessions();
    } catch (e) {
      window.Toast.error(e.message || 'Could not revoke sessions');
    }
  }

  function renderIntegrationCards(accounts) {
    const mount = document.getElementById('integrationsMount');
    mount.innerHTML = '';
    const byPlat = {};
    (accounts || []).forEach(function (a) {
      byPlat[a.platform] = a;
    });

    PLATFORMS.forEach(function (p) {
      const acc = byPlat[p.id];
      const active = acc && parseInt(acc.is_active, 10) === 1;
      const card = document.createElement('div');
      card.className = 'integration-card';
      if (active && acc) {
        const followers = Number(acc.follower_count || 0).toLocaleString();
        const exp = acc.token_expires_at
          ? Utils.formatDate(acc.token_expires_at, 'medium')
          : '—';
        card.innerHTML =
          '<div class="integration-card-head">' +
          '<span class="integration-icon integration-icon--' +
          p.id +
          '">' +
          (PLATFORM_ICONS[p.id] || '') +
          '</span>' +
          '<div><strong>' +
          Utils.escapeHtml(p.name) +
          '</strong><br><span class="text-muted text-sm">@' +
          Utils.escapeHtml(acc.username || '') +
          ' · ' +
          followers +
          ' followers</span></div></div>' +
          '<p class="text-sm text-muted">Token expires: ' +
          Utils.escapeHtml(exp) +
          '</p>' +
          '<button type="button" class="btn btn-secondary btn-sm integration-disconnect" data-platform="' +
          p.id +
          '">Disconnect</button>';
      } else {
        card.innerHTML =
          '<div class="integration-card-head">' +
          '<span class="integration-icon integration-icon--' +
          p.id +
          '">' +
          (PLATFORM_ICONS[p.id] || '') +
          '</span>' +
          '<div><strong>' +
          Utils.escapeHtml(p.name) +
          '</strong><br><span class="text-muted text-sm">Not connected</span></div></div>' +
          (function () {
            const oauthReady = oauthPlatforms.indexOf(p.id) >= 0;
            return (
              '<button type="button" class="btn btn-primary btn-sm integration-connect" data-platform="' +
              p.id +
              '">' +
              'Connect ' + Utils.escapeHtml(p.name) +
              '</button>' +
              (oauthReady
                ? '<p class="text-xs text-muted mt-2">Secure OAuth — admin must configure credentials first.</p>'
                : '<p class="text-xs text-muted mt-2">Quick connect for development, or ask admin to enable OAuth.</p>')
            );
          })();
      }
      mount.appendChild(card);
    });

    mount.querySelectorAll('.integration-disconnect').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const plat = btn.getAttribute('data-platform');
        if (!confirm('Disconnect ' + plat + '?')) return;
        disconnectPlatform(plat);
      });
    });
    mount.querySelectorAll('.integration-connect').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const plat = btn.getAttribute('data-platform');
        connectPlatform(plat);
      });
    });
  }

  function renderAdminApiPanel(data) {
    const mount = document.getElementById('integrationsMount');
    if (!mount) return;
    const integrations = data.integrations || [];
    const summary = data.summary || {};
    const esc = Utils.escapeHtml;
    const prefix = typeof window.__BASE_PATH__ === 'string' ? String(window.__BASE_PATH__).replace(/\/$/, '') : '';
    const baseOrigin = window.location.origin || '';

    let rows = '';
    integrations.forEach(function (row) {
      const key = 'integration_enabled_' + row.platform;
      rows +=
        '<tr><td>' +
        esc(row.label || row.platform) +
        '</td><td><label class="form-switch"><input type="checkbox" data-int-toggle="' +
        esc(key) +
        '"' +
        (row.enabled ? ' checked' : '') +
        '></label></td><td>' +
        (row.token_configured
          ? '<span class="text-success">' +
            esc(row.token_source === 'ui' ? 'UI' : row.token_source === 'env' ? '.env' : 'OK') +
            '</span>'
          : '<span class="text-danger">Missing</span>') +
        '</td><td>' +
        esc(String(row.connected_accounts || 0)) +
        '</td><td>' +
        (parseInt(row.expiring_soon, 10) > 0
          ? '<span class="text-warning">' + esc(String(row.expiring_soon)) + '</span>'
          : '<span class="text-muted">0</span>') +
        '</td><td><button type="button" class="btn btn-sm btn-secondary" data-int-test="' +
        esc(row.platform) +
        '">Test</button></td></tr>';
    });

    mount.innerHTML =
      '<div class="form-stack admin-api-stack">' +
      '<div class="card card--feature admin-api-subcard" style="border:1px solid var(--color-border);">' +
      '<div class="card-header"><h4 class="card-title">HTTP API</h4></div><div class="card-body">' +
      '<p class="text-sm text-muted mb-2">JSON routes use the <code>?route=…</code> query on this app URL. Send <code>Accept: application/json</code>. Authenticated calls rely on the session cookie from login.</p>' +
      '<div class="form-row"><label class="form-label">Origin</label><input class="form-input" readonly type="text" value="' +
      esc(baseOrigin) +
      '"></div>' +
      '<div class="form-row"><label class="form-label">Base path</label><input class="form-input" readonly type="text" value="' +
      esc(prefix || '/') +
      '"></div>' +
      '<div class="form-row"><label class="form-label">Example (GET ping)</label><input class="form-input" readonly type="text" value="' +
      esc(routeUrl('ping')) +
      '"></div>' +
      '<p class="text-sm text-muted">POST requests must include <code>_token</code> with your CSRF value (see <code>window.__CSRF__</code> in the page), or use <code>Authorization: Bearer …</code> for machine clients.</p>' +
      '<p class="text-sm text-muted">Discovery: <code>' +
      esc(routeUrl('api_me')) +
      '</code> (session + CSRF) and <code>' +
      esc(routeUrl('api_catalog')) +
      '</code> (routes for your role). CORS: set <code>API_CORS_ORIGINS</code> in <code>.env</code>. See <code>docs/api.md</code>.</p>' +
      '</div></div>' +
      '<div class="card card--feature admin-api-subcard mt-3" style="border:1px solid var(--color-border);">' +
      '<div class="card-header"><h4 class="card-title">Platform snapshot</h4></div><div class="card-body">' +
      '<ul class="text-sm" style="margin:0;padding-left:1.25rem;">' +
      '<li>Total users: <strong>' +
      esc(String(summary.users_total != null ? summary.users_total : '—')) +
      '</strong></li>' +
      '<li>Active users: <strong>' +
      esc(String(summary.users_active != null ? summary.users_active : '—')) +
      '</strong></li>' +
      '<li>Active sessions: <strong>' +
      esc(String(summary.sessions_active != null ? summary.sessions_active : '—')) +
      '</strong></li>' +
      '<li>Pending jobs: <strong>' +
      esc(String(summary.jobs_pending != null ? summary.jobs_pending : '—')) +
      '</strong></li>' +
      '</ul></div></div>' +
      '<div class="card card--feature admin-api-subcard mt-3" style="border:1px solid var(--color-border);">' +
      '<div class="card-header"><h4 class="card-title">Platform API credentials</h4></div><div class="card-body">' +
      '<p class="text-sm text-muted mb-2">Configure Meta, TikTok, YouTube, and X tokens below (encrypted at rest).</p>' +
      '<div id="adminPlatformCredentialsMount"></div></div></div>' +
      '<div class="card card--feature admin-api-subcard mt-3" style="border:1px solid var(--color-border);">' +
      '<div class="card-header"><h4 class="card-title">Provider toggles</h4></div><div class="card-body">' +
      '<p class="text-sm text-muted mb-2">Enable providers for creators. Token column: <strong>UI</strong> or <strong>.env</strong>.</p>' +
      '<div class="table-wrapper"><table class="table"><thead><tr><th>Provider</th><th>Enabled</th><th>Token</th><th>Connected</th><th>Expiring &lt;7d</th><th></th></tr></thead><tbody>' +
      rows +
      '</tbody></table></div>' +
      '<button type="button" class="btn btn-primary mt-3" id="adminApiPanelSaveProviders">Save provider toggles</button>' +
      '</div></div></div>';

    const credMount = mount.querySelector('#adminPlatformCredentialsMount');
    const credGroups = data.platform_credentials || [];
    if (credMount && window.AdminPlatformCredentials) {
      if (window.AdminPlatformCredentials.renderAll) {
        window.AdminPlatformCredentials.renderAll(credMount, credGroups);
      } else if (credGroups[0]) {
        window.AdminPlatformCredentials.render(credMount, credGroups[0]);
      }
    }

    mount.querySelectorAll('[data-int-test]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const platform = btn.getAttribute('data-int-test');
        window
          .api('admin_test_integration', 'GET', { platform: platform })
          .then(function (r) {
            window.Toast.success((r && r.message) || 'OK');
          })
          .catch(function (err) {
            window.Toast.error(err.message || 'Test failed');
          });
      });
    });

    const saveBtn = mount.querySelector('#adminApiPanelSaveProviders');
    if (saveBtn) {
      saveBtn.addEventListener('click', function () {
        const payload = {};
        mount.querySelectorAll('[data-int-toggle]').forEach(function (input) {
          const key = input.getAttribute('data-int-toggle');
          if (key) payload[key] = input.checked ? '1' : '0';
        });
        window
          .api('admin_update_settings', 'POST', payload)
          .then(function () {
            window.Toast.success('Provider toggles saved');
          })
          .catch(function (err) {
            window.Toast.error(err.message || 'Save failed');
          });
      });
    }
  }

  async function loadIntegrations() {
    if (isAdminRole()) {
      const res = await window.api('admin_overview', 'GET');
      renderAdminApiPanel(res.data || {});
      return;
    }
    const res = await window.api('integrations_data', 'GET');
    oauthPlatforms = (res.data && res.data.oauth_platforms) || [];
    renderIntegrationCards(res.data.accounts || []);
  }

  async function disconnectPlatform(platform) {
    try {
      await window.api('disconnect_platform', 'POST', { platform: platform });
      window.Toast.success('Disconnected');
      await loadIntegrations();
    } catch (e) {
      window.Toast.error(e.message || 'Could not disconnect');
    }
  }

  function showOauthFlash() {
    const flash = window.__OAUTH_FLASH__ || {};
    if (flash.success && window.Toast) {
      window.Toast.success(String(flash.success));
    }
    if (flash.error && window.Toast) {
      window.Toast.error(String(flash.error));
    }
    window.__OAUTH_FLASH__ = null;
  }

  async function connectPlatform(platform) {
    const slug = String(platform || '').trim().toLowerCase();
    if (!slug) return;

    if (oauthPlatforms.indexOf(slug) >= 0) {
      const routeName = slug + '-connect';
      const target =
        (typeof window.routeUrl === 'function' ? window.routeUrl(routeName) : null) ||
        (typeof window.routeQuery === 'function' ? window.routeQuery(routeName) : null);
      if (target) {
        window.location.href = target;
        return;
      }
    }

    const handle = window.prompt(
      'Enter your ' + slug + ' username to connect:',
      ''
    );
    if (handle === null) return;

    const username = String(handle).trim().replace(/^@+/, '');
    if (!username) {
      window.Toast.warning('Connection cancelled: username is required');
      return;
    }

    try {
      await window.api('connect_platform', 'POST', {
        platform: slug,
        username: username,
      });
      window.Toast.success('Connected ' + slug + ' account');
      await loadIntegrations();
    } catch (e) {
      window.Toast.error(e.message || 'Could not connect account');
    }
  }

  async function loadNotificationPrefs() {
    const res = await window.api('notification_prefs', 'GET');
    const d = res.data || {};
    const emailMount = document.getElementById('emailPrefs');
    const pushMount = document.getElementById('pushPrefs');
    emailMount.innerHTML = '';
    pushMount.innerHTML = '';

    function row(label, key, val) {
      const id = 'pref-' + key;
      const wrap = document.createElement('label');
      wrap.className = 'pref-row form-switch';
      wrap.innerHTML =
        '<span>' +
        Utils.escapeHtml(label) +
        '</span>' +
        '<input type="checkbox" id="' +
        id +
        '" data-key="' +
        key +
        '"' +
        (val ? ' checked' : '') +
        '>';
      const input = wrap.querySelector('input');
      input.addEventListener('change', function () {
        togglePref(key, input.checked);
      });
      return wrap;
    }

    EMAIL_PREF_FIELDS.forEach(function (f) {
      emailMount.appendChild(row(f.label, f.key, parseInt(d[f.key], 10) === 1));
    });
    PUSH_PREF_FIELDS.forEach(function (f) {
      pushMount.appendChild(row(f.label, f.key, parseInt(d[f.key], 10) === 1));
    });
  }

  async function togglePref(key, value) {
    try {
      await window.api('update_notification_prefs', 'POST', { [key]: value ? '1' : '0' });
      window.Toast.success('Saved');
    } catch (e) {
      window.Toast.error(e.message || 'Could not save');
      await loadNotificationPrefs();
    }
  }

  async function submitPreferences(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('prefsForm'));
    const body = {
      theme: fd.get('theme'),
      language: fd.get('language'),
      default_currency: fd.get('default_currency'),
      date_format: fd.get('date_format'),
      time_format: fd.get('time_format'),
      week_starts_on: document.getElementById('prefWeekStart').checked ? '1' : '0',
    };
    try {
      await window.api('update_preferences', 'POST', body);
      applyThemeFromPrefs(body.theme);
      window.Toast.success('Preferences saved');
    } catch (err) {
      window.Toast.error(err.message || 'Could not save');
    }
  }

  function initTabs() {
    const panels = document.querySelectorAll('.settings-panel');

    function show(panel) {
      panels.forEach(function (p) {
        p.classList.toggle('d-none', p.id !== 'panel-' + panel);
      });
    }

    function syncPanel() {
      let activePanel = String(window.__SETTINGS_PANEL__ || 'profile');
      if (isAdminRole() && activePanel === 'notifications') {
        window.location.replace(routeUrl('settings-profile'));
        return;
      }
      if (!document.getElementById('panel-' + activePanel)) {
        show('profile');
        return;
      }
      show(activePanel);
      if (activePanel === 'security') loadSessions().catch(function () {});
      if (activePanel === 'integrations') {
        showOauthFlash();
        loadIntegrations().catch(function () {});
      }
      if (activePanel === 'notifications') loadNotificationPrefs().catch(function () {});
    }

    syncPanel();
  }

  function boot() {
    initTabs();
    loadProfile().catch(function (e) {
      window.Toast.error(e.message || 'Could not load profile');
    });
    document.getElementById('profileForm').addEventListener('submit', submitProfile);
    document.getElementById('avatarInput').addEventListener('change', function () {
      if (this.files && this.files[0]) previewAvatar(this.files[0]);
    });
    debounceCheckUsername();
    document.getElementById('passwordForm').addEventListener('submit', submitPassword);
    bindPasswordMeter();
    document.getElementById('revokeAllSessionsBtn').addEventListener('click', revokeAllOthers);
    document.getElementById('prefsForm').addEventListener('submit', submitPreferences);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
