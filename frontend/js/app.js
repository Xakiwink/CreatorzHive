(function () {
  const basePath = typeof window.__BASE_PATH__ === 'string' ? window.__BASE_PATH__ : '';

  function assetPath(rel) {
    rel = String(rel || '').replace(/^\//, '');
    return (basePath ? basePath + '/' : '/') + rel;
  }

  /**
   * Path prefix for the front controller (same host as the current page).
   * Uses __BASE_PATH__ when set; otherwise derives from location.pathname so
   * subdirectory installs still POST to /creatorzhive/?route=… not /?route=…
   */
  function apiPathPrefix() {
    if (basePath) {
      return basePath.replace(/\/$/, '');
    }
    let path = window.location.pathname || '/';
    if (/\/[^/]+\.(php|html)$/i.test(path)) {
      path = path.replace(/\/[^/]+$/, '');
    }
    path = path.replace(/\/$/, '');
    if (path === '' || path === '/') {
      return '';
    }
    return path;
  }

  function routeQuery(route, extra) {
    const p = new URLSearchParams();
    p.set('route', route);
    if (extra && typeof extra === 'object') {
      Object.keys(extra).forEach((k) => {
        const v = extra[k];
        if (v !== undefined && v !== null) {
          p.set(k, String(v));
        }
      });
    }
    const qs = p.toString();
    const prefix = apiPathPrefix();
    if (prefix === '') {
      return '/?' + qs;
    }
    return prefix + '/?' + qs;
  }

  const qp = (k) => new URLSearchParams(location.search).get(k);
  const route = qp('route') || 'dashboard';
  const mapTitle = {
    dashboard: 'Dashboard',
    planner: 'Planner',
    analytics: 'Analytics',
    deals: 'Deals',
    invoices: 'Invoices',
    media: 'Media',
    notifications: 'Notifications',
    settings: 'Settings',
    'settings-profile': 'Settings',
    'settings-security': 'Settings',
    'settings-integrations': 'Settings',
    'settings-notifications': 'Settings',
    'settings-preferences': 'Settings',
    'admin-users': 'User Management',
  };

  function applyRoleNavigation() {
    const user = window.__USER__ || {};
    const role = String(user.role || '').toLowerCase();
    if (role !== 'admin') return;

    const adminOnly = document.querySelectorAll('.admin-only');
    adminOnly.forEach((el) => el.classList.remove('d-none'));

    document.querySelectorAll('.creator-only').forEach((el) => {
      el.classList.add('d-none');
    });

    const apiNav = document.querySelector('.nav-item[data-route="settings-integrations"]');
    if (apiNav) {
      const lab = apiNav.querySelector('.label');
      if (lab) lab.textContent = 'API configuration';
      apiNav.setAttribute('data-tooltip', 'API configuration');
    }

    const contentRoutes = ['planner', 'analytics', 'deals', 'invoices', 'media', 'dashboard', 'notifications'];
    contentRoutes.forEach((r) => {
      document.querySelectorAll('.nav-item[data-route="' + r + '"]').forEach((el) => el.remove());
    });

    if (contentRoutes.indexOf(route) !== -1) {
      window.location.href = routeQuery('settings');
    }
  }

  async function loadComponent(relUrl, target) {
    const el = document.querySelector(target);
    if (!el) return;
    const res = await fetch(assetPath(relUrl));
    el.innerHTML = await res.text();
  }

  function setActiveNav() {
    document.querySelectorAll('.nav-item').forEach((a) => {
      a.classList.toggle('active', a.dataset.route === route);
    });
    const t = document.querySelector('#pageTitle');
    if (t) {
      t.textContent = mapTitle[route] || 'CreatorzHive';
    }

    const settingsDropdown = document.querySelector('#settingsNavDropdown');
    if (settingsDropdown && route.indexOf('settings') === 0) {
      settingsDropdown.classList.add('open');
      const trigger = settingsDropdown.querySelector('.dropdown-trigger');
      if (trigger) trigger.setAttribute('aria-expanded', 'true');
    }
  }

  /** Resolve light|dark|system (and legacy values) to light or dark for data-theme. */
  function resolveStoredThemeToAppearance(raw) {
    if (raw === 'dark') {
      return 'dark';
    }
    if (raw === 'system') {
      if (typeof window.matchMedia === 'function') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }
      return 'light';
    }
    return 'light';
  }

  function syncThemeFromStorage() {
    const raw = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', resolveStoredThemeToAppearance(raw));
  }

  function initTheme() {
    syncThemeFromStorage();
    const btn = document.querySelector('#themeToggle');
    if (btn) btn.onclick = toggleTheme;
  }

  function toggleTheme() {
    const onDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const next = onDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
  }

  window.syncThemeFromStorage = syncThemeFromStorage;
  syncThemeFromStorage();

  window.addEventListener('storage', function (e) {
    if (e.key === 'theme') {
      syncThemeFromStorage();
    }
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
      syncThemeFromStorage();
    }
  });

  function toggleSidebar() {
    const side = document.querySelector('#sidebar');
    if (!side) return;
    if (window.innerWidth < 768) {
      const open = !side.classList.contains('open');
      side.classList.toggle('open', open);
      document.body.classList.toggle('drawer-open', open);
      return;
    }
    document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem(
      'sidebarCollapsed',
      document.body.classList.contains('sidebar-collapsed') ? '1' : '0',
    );
  }

  function closeMobileDrawer() {
    const side = document.querySelector('#sidebar');
    if (side) {
      side.classList.remove('open');
      document.body.classList.remove('drawer-open');
    }
  }

  function initSidebarState() {
    if (localStorage.getItem('sidebarCollapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
    }
  }

  async function api(routeName, method = 'GET', data = {}) {
    const token = window.__CSRF__ || '';
    const opts = { method, headers: { Accept: 'application/json' } };
    const query =
      method === 'GET' && data && typeof data === 'object'
        ? data
        : undefined;
    const url = routeQuery(routeName, query);
    if (method !== 'GET') {
      const fd = new URLSearchParams({ ...data, _token: token });
      opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      opts.body = fd.toString();
    }
    const r = await fetch(url, opts);
    const text = await r.text();
    let j;
    try {
      j = JSON.parse(text);
    } catch (e) {
      const hint = text.replace(/\s+/g, ' ').trim().slice(0, 160);
      throw new Error(
        'Non-JSON response (HTTP ' + r.status + '): ' + (hint || 'empty') + '. Check app URL / base path.'
      );
    }
    if (!j.success) {
      throw new Error(j.message || 'Request failed');
    }
    return j;
  }

  class ModalClass {
    open(title, bodyHtml, footerHtml = '') {
      const o = document.querySelector('#modalOverlay');
      if (!o) return;
      document.querySelector('#modalTitle').textContent = title;
      document.querySelector('#modalBody').innerHTML = bodyHtml;
      document.querySelector('#modalFooter').innerHTML = footerHtml;
      o.classList.add('open');
    }
    close() {
      document.querySelector('#modalOverlay')?.classList.remove('open');
    }
  }

  class ToastClass {
    show(message, type = 'info') {
      const c = document.querySelector('#toastContainer');
      if (!c) return;
      const el = document.createElement('div');
      el.className = `toast ${type} fade-in`;
      el.textContent = message;
      c.appendChild(el);
      setTimeout(() => el.remove(), 4000);
    }
    success(m) {
      this.show(m, 'success');
    }
    error(m) {
      this.show(m, 'error');
    }
    warning(m) {
      this.show(m, 'warning');
    }
  }

  window.Modal = new ModalClass();
  window.Toast = new ToastClass();
  window.api = api;
  window.routeQuery = routeQuery;
  window.assetPath = assetPath;

  function hydrateSidebarUser(u) {
    if (!u || typeof u !== 'object') return;
    const nameEl = document.querySelector('.sidebar-user .user-name');
    const roleEl = document.querySelector('.sidebar-user .user-role');
    const av = document.querySelector('.sidebar-user .avatar');
    if (nameEl && u.name) nameEl.textContent = u.name;
    if (roleEl && u.role) roleEl.textContent = u.role;
    if (av && u.avatar_url) {
      av.style.backgroundImage = 'url("' + String(u.avatar_url).replace(/"/g, '\\"') + '")';
      av.style.backgroundSize = 'cover';
      av.style.backgroundPosition = 'center';
    }
    window.__USER__ = Object.assign({}, window.__USER__ || {}, u);
  }
  window.hydrateSidebarUser = hydrateSidebarUser;

  async function refreshNotifBadge() {
    const badge = document.querySelector('#notifBadge');
    if (!badge) return;
    try {
      const res = await api('notifications_count');
      const n = parseInt(res.data && res.data.unread_count, 10) || 0;
      if (n > 0) {
        badge.classList.remove('d-none');
        badge.textContent = String(n > 99 ? '99+' : n);
      } else {
        badge.classList.add('d-none');
        badge.textContent = '0';
      }
    } catch (e) {
      /* ignore */
    }
  }
  window.refreshNotifBadge = refreshNotifBadge;

  /** Wires up every dropdown-family menu (navbar user menu, sidebar Settings
   *  accordion, ...) with one consistent open/close behavior: click the
   *  trigger to toggle, click outside or Escape to close, only one open
   *  at a time. Sidebar dropdowns are skipped while the sidebar is
   *  collapsed to icon-only mode, where their trigger just navigates
   *  normally instead (no room for an inline accordion there). */
  function initDropdowns() {
    const containers = Array.from(document.querySelectorAll('.dropdown, .nav-dropdown'));

    function closeAll(except) {
      containers.forEach((container) => {
        if (container === except) return;
        container.classList.remove('open');
        const t = container.querySelector('.dropdown-trigger');
        if (t) t.setAttribute('aria-expanded', 'false');
      });
    }

    containers.forEach((container) => {
      const trigger = container.querySelector('.dropdown-trigger');
      if (!trigger) return;
      trigger.addEventListener('click', (e) => {
        if (container.classList.contains('nav-dropdown') && document.body.classList.contains('sidebar-collapsed')) {
          return;
        }
        e.preventDefault();
        const willOpen = !container.classList.contains('open');
        closeAll(container);
        container.classList.toggle('open', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });
    });

    document.addEventListener('click', (e) => {
      containers.forEach((container) => {
        if (!container.contains(e.target)) {
          container.classList.remove('open');
          const t = container.querySelector('.dropdown-trigger');
          if (t) t.setAttribute('aria-expanded', 'false');
        }
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeAll(null);
    });
  }

  function initModalClose() {
    document.addEventListener('click', (e) => {
      if (e.target.id === 'modalClose' || e.target.id === 'modalOverlay') {
        window.Modal.close();
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') window.Modal.close();
    });
  }

  function initMobileMenu() {
    document.querySelector('#mobileMenuBtn')?.addEventListener('click', toggleSidebar);
    document.querySelector('#toggleSidebar')?.addEventListener('click', toggleSidebar);
    document.querySelector('#sidebarOverlay')?.addEventListener('click', closeMobileDrawer);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMobileDrawer();
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) closeMobileDrawer();
    });
  }

  async function initNotif() {
    const user = window.__USER__ || {};
    if (String(user.role || '').toLowerCase() === 'admin') {
      return;
    }
    await refreshNotifBadge();
    document.querySelector('#notificationBtn')?.addEventListener('click', function () {
      window.location.href = routeQuery('notifications');
    });
  }

  /**
   * Widen the marquee strip on large viewports: two phrase copies are shorter than
   * the main column, leaving empty bands. Rebuild to an even segment count so
   * translateX(-50%) stays seamless and ResizeObserver catches sidebar / window changes.
   */
  function initEditorialTicker() {
    const viewport = document.querySelector('.editorial-ticker__viewport');
    const strip = document.querySelector('.editorial-ticker__strip');
    if (!viewport || !strip) return;

    let proto = null;
    let debounceId = null;
    let layoutRetries = 0;

    function apply() {
      const first = strip.querySelector('.editorial-ticker__segment');
      if (!first) return;
      if (!proto) {
        proto = first.cloneNode(true);
      }

      const tw = first.getBoundingClientRect().width;
      if (!tw || tw < 4) {
        if (layoutRetries < 12) {
          layoutRetries += 1;
          requestAnimationFrame(function () {
            apply();
          });
        }
        return;
      }
      layoutRetries = 0;

      const vw = viewport.getBoundingClientRect().width;
      let n = Math.max(2, Math.ceil(vw / tw));
      if (n % 2) n += 1;
      if (n > 24) n = 24;

      const countNow = strip.querySelectorAll('.editorial-ticker__segment').length;
      if (countNow !== n) {
        strip.replaceChildren();
        for (let i = 0; i < n; i++) {
          const seg = proto.cloneNode(true);
          if (i > 0) {
            seg.setAttribute('aria-hidden', 'true');
          }
          strip.appendChild(seg);
        }
      }

      const stripW = strip.offsetWidth;
      const halfPx = Math.max(1, Math.round(stripW / 2));
      strip.style.setProperty('--marquee-shift', halfPx + 'px');

      const durationSec = Math.max(20, Math.round(stripW / 35));
      const prevDur = strip.dataset.marqueeDur;
      if (prevDur !== String(durationSec)) {
        strip.dataset.marqueeDur = String(durationSec);
        strip.style.animationDuration = durationSec + 's';
      }
    }

    function schedule() {
      clearTimeout(debounceId);
      debounceId = setTimeout(apply, 60);
    }

    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(schedule).observe(viewport);
    } else {
      window.addEventListener('resize', schedule);
    }
    apply();
  }

  async function boot() {
    await loadComponent('frontend/components/sidebar.html', '#sidebar-container');
    const logoMark = document.querySelector('#sidebar-container .logo-mark');
    if (logoMark) logoMark.src = assetPath('frontend/assets/icon.svg');
    /* Strip creator-only nav for admins before paint/hydrate — avoids a flash of dashboard/planner/etc. */
    applyRoleNavigation();
    if (window.__USER__) {
      hydrateSidebarUser(window.__USER__);
    }
    await loadComponent('frontend/components/navbar.html', '#header-container');
    await loadComponent('frontend/components/modal.html', '#modal-container');
    await loadComponent('frontend/components/toast.html', '#toast-container');
    initSidebarState();
    setActiveNav();
    initEditorialTicker();
    initTheme();
    initDropdowns();
    initModalClose();
    initMobileMenu();
    initNotif();
  }

  boot();
})();
