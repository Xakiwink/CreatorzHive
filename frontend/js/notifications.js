(function () {
  const NOTIF_TYPE_META = {
    post_published: { className: 'notif-type--success', label: 'Post' },
    post_scheduled: { className: 'notif-type--scheduled', label: 'Post' },
    post_failed: { className: 'notif-type--danger', label: 'Post' },
    deal_updated: { className: 'notif-type--deal', label: 'Deal' },
    deal_completed: { className: 'notif-type--win', label: 'Deal' },
    invoice_paid: { className: 'notif-type--money', label: 'Invoice' },
    welcome: { className: 'notif-type--welcome', label: 'Welcome' },
  };

  let state = { tab: 'all', page: 1, perPage: 20, hasMore: false, loading: false };

  function el(html) {
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }

  function notifIcon(type, emojiFallback) {
    const ic =
      type === 'post_published'
        ? '<svg class="notif-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        : type === 'post_scheduled'
        ? '<svg class="notif-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 3"/></svg>'
        : type === 'post_failed'
          ? '<svg class="notif-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
          : type === 'deal_updated'
            ? '<svg class="notif-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3 3 7-7"/></svg>'
            : type === 'deal_completed'
              ? '<svg class="notif-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M19 3v4M4 11h16M9 17l2 2 4-4"/></svg>'
              : type === 'invoice_paid'
                ? '<svg class="notif-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                : '<span class="notif-emoji">' + Utils.escapeHtml(emojiFallback || '🔔') + '</span>';
    return ic;
  }

  async function loadPage(reset) {
    if (state.loading) return;
    state.loading = true;
    if (reset) {
      state.page = 1;
      document.getElementById('notifListMount').innerHTML = '';
    }

    const mount = document.getElementById('notifListMount');
    const empty = document.getElementById('notifEmpty');
    const loadMore = document.getElementById('notifLoadMore');

    try {
      const res = await window.api('notifications_data', 'GET', {
        tab: state.tab,
        page: String(state.page),
        per_page: String(state.perPage),
      });
      const payload = res.data || {};
      const items = payload.notifications || [];
      state.hasMore = !!payload.has_more;

      if (reset && items.length === 0) {
        empty.classList.remove('d-none');
        mount.innerHTML = '';
      } else {
        empty.classList.add('d-none');
      }

      items.forEach(function (n) {
        mount.appendChild(renderItem(n));
      });

      if (state.hasMore) {
        loadMore.classList.remove('d-none');
      } else {
        loadMore.classList.add('d-none');
      }

      if (window.refreshNotifBadge) window.refreshNotifBadge();
    } catch (e) {
      window.Toast.error(e.message || 'Could not load notifications');
    } finally {
      state.loading = false;
    }
  }

  function renderItem(n) {
    const type = String(n.type || '');
    const meta = NOTIF_TYPE_META[type] || { className: '', label: 'Alert' };
    const unread = !parseInt(n.is_read, 10);
    const wrap = el(
      '<article class="notif-item ' +
        (unread ? 'notif-item--unread' : '') +
        '" data-id="' +
        String(n.id) +
        '" role="listitem">' +
        '<div class="notif-item-inner">' +
        '<div class="notif-type-icon ' +
        meta.className +
        '">' +
        notifIcon(type, n.icon) +
        '</div>' +
        '<div class="notif-body">' +
        '<div class="notif-title-row">' +
        '<span class="notif-type-tag">' +
        Utils.escapeHtml(meta.label) +
        '</span>' +
        '<time class="notif-time">' +
        Utils.timeAgo(n.created_at) +
        '</time>' +
        '</div>' +
        '<h4 class="notif-title">' +
        Utils.escapeHtml(n.title || '') +
        '</h4>' +
        '<p class="notif-text">' +
        Utils.escapeHtml(n.body || '') +
        '</p>' +
        '</div>' +
        '<button type="button" class="notif-delete btn btn-ghost btn-sm" title="Delete">×</button>' +
        '</div>' +
        '</article>',
    );

    wrap.querySelector('.notif-item-inner').addEventListener('click', function (e) {
      if (e.target.closest('.notif-delete')) return;
      openNotif(n);
    });
    wrap.querySelector('.notif-delete').addEventListener('click', function (e) {
      e.stopPropagation();
      deleteOne(n.id);
    });

    return wrap;
  }

  async function openNotif(n) {
    try {
      await window.api('mark_read', 'POST', { id: String(n.id) });
      const row = document.querySelector('.notif-item[data-id="' + String(n.id) + '"]');
      if (row) {
        row.classList.remove('notif-item--unread');
      }
      const url = n.action_url;
      if (url && String(url).trim() !== '') {
        window.location.href = url;
      }
      if (window.refreshNotifBadge) window.refreshNotifBadge();
    } catch (e) {
      window.Toast.error(e.message || 'Could not update notification');
    }
  }

  async function deleteOne(id) {
    try {
      await window.api('delete_notification', 'POST', { id: String(id) });
      document.querySelector('.notif-item[data-id="' + String(id) + '"]')?.remove();
      if (!document.querySelector('.notif-item')) {
        loadPage(true);
      }
      if (window.refreshNotifBadge) window.refreshNotifBadge();
      window.Toast.success('Removed');
    } catch (e) {
      window.Toast.error(e.message || 'Delete failed');
    }
  }

  async function markAllRead() {
    try {
      await window.api('mark_all_read', 'POST', {});
      document.querySelectorAll('.notif-item--unread').forEach(function (el) {
        el.classList.remove('notif-item--unread');
      });
      if (window.refreshNotifBadge) window.refreshNotifBadge();
      window.Toast.success('All marked as read');
    } catch (e) {
      window.Toast.error(e.message || 'Could not mark read');
    }
  }

  async function deleteRead() {
    try {
      await window.api('delete_read_notifications', 'POST', {});
      await loadPage(true);
      if (window.refreshNotifBadge) window.refreshNotifBadge();
      window.Toast.success('Read notifications cleared');
    } catch (e) {
      window.Toast.error(e.message || 'Could not delete');
    }
  }

  function bindTabs() {
    document.querySelectorAll('.notif-filter-tabs .tab-pill').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.notif-filter-tabs .tab-pill').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        state.tab = btn.getAttribute('data-tab') || 'all';
        loadPage(true);
      });
    });
  }

  function boot() {
    bindTabs();
    document.getElementById('notifMarkAllRead')?.addEventListener('click', markAllRead);
    document.getElementById('notifDeleteRead')?.addEventListener('click', deleteRead);
    document.getElementById('notifLoadMore')?.addEventListener('click', function () {
      state.page += 1;
      loadPage(false);
    });
    loadPage(true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
