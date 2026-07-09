(function () {
  let calYear;
  let calMonth;
  let listPage = 1;
  let currentView = 'calendar';
  let allTags = [];
  let activePreviewPlatform = 'instagram';
  const composerMedia = [];
  /** @type {Set<number>} */
  const selectedTagIds = new Set();
  const selectedPostIds = new Set();

  const PLATFORM_LIMITS = {
    instagram: 2200,
    twitter: 280,
    tiktok: 2200,
    youtube_title: 100,
    youtube: 5000,
  };

  function fetchRouteJson(route, query) {
    return fetch(window.routeQuery(route, query || {}), {
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function pad2(n) {
    return String(n).padStart(2, '0');
  }

  function statusClass(s) {
    return String(s || 'draft');
  }

  function postPill(post) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'post-pill ' + statusClass(post.status);
    btn.textContent = Utils.truncate(post.title, 28);
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      openEditModal(Number(post.id));
    });
    return btn;
  }

  function renderCalendarGrid(data) {
    const dates = data.dates || {};
    const month = Number(data.month) - 1;
    const year = Number(data.year);
    const label = document.getElementById('calMonthLabel');
    if (label) {
      label.textContent = new Date(year, month, 1).toLocaleString(undefined, {
        month: 'long',
        year: 'numeric',
      });
    }

    const cells = document.getElementById('calendarCells');
    if (!cells) return;
    cells.innerHTML = '';

    const first = new Date(year, month, 1);
    const startPad = first.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    const isToday = today.getFullYear() === year && today.getMonth() === month;

    for (let i = 0; i < startPad; i++) {
      const c = document.createElement('div');
      c.className = 'calendar-cell out';
      cells.appendChild(c);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const key = year + '-' + pad2(month + 1) + '-' + pad2(d);
      const list = dates[key] || [];
      const cell = document.createElement('div');
      cell.className = 'calendar-cell';
      if (isToday && d === today.getDate()) cell.classList.add('today');

      const num = document.createElement('div');
      num.className = 'cell-date';
      num.textContent = String(d);
      cell.appendChild(num);

      const pills = document.createElement('div');
      pills.className = 'cell-pills';
      const maxPills = 3;
      list.slice(0, maxPills).forEach(function (p) {
        pills.appendChild(postPill(p));
      });
      if (list.length > maxPills) {
        const more = document.createElement('div');
        more.className = 'post-pill draft';
        more.textContent = '+' + (list.length - maxPills) + ' more';
        pills.appendChild(more);
      }
      cell.appendChild(pills);

      cell.addEventListener('click', function () {
        openCreateModal(key);
      });

      cells.appendChild(cell);
    }
  }

  function loadCalendar(month, year) {
    const cells = document.getElementById('calendarCells');
    if (cells) {
      cells.innerHTML =
        '<div class="skeleton" style="grid-column:1/-1;height:400px;border-radius:8px"></div>';
    }
    fetchRouteJson('posts_calendar', { month: month, year: year })
      .then(function (j) {
        if (!j.success) throw new Error(j.message || 'Calendar failed');
        renderCalendarGrid(j.data || {});
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Calendar failed');
      });
  }

  function loadListView() {
    const tbody = document.getElementById('plannerTableBody');
    if (tbody) {
      tbody.innerHTML =
        '<tr><td colspan="8"><div class="skeleton skeleton-text" style="height:200px"></div></td></tr>';
    }

    const st = document.getElementById('filterStatus')?.value || '';
    const plat = document.getElementById('filterPlatform')?.value || '';
    const df = document.getElementById('filterDateFrom')?.value || '';
    const dt = document.getElementById('filterDateTo')?.value || '';
    const q = document.getElementById('filterSearch')?.value || '';
    const sortVal = document.getElementById('filterSort')?.value || 'date:desc';
    const parts = sortVal.split(':');
    const sort = parts[0] || 'date';
    const dir = parts[1] || 'desc';

    const query = {
      page: listPage,
      per_page: 10,
      sort: sort,
      dir: dir,
    };
    if (st) query.status = st;
    if (plat) query.platform = plat;
    if (df) query.date_from = df;
    if (dt) query.date_to = dt;
    if (q) query.search = q;

    fetchRouteJson('posts', query)
      .then(function (j) {
        if (!j.success) throw new Error(j.message || 'List failed');
        const pack = j.data || {};
        const posts = pack.posts || [];
        renderListRows(posts);
        renderPagination(pack);
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'List failed');
      });
  }

  function platformBadges(post) {
    const plats = Array.isArray(post.platforms) ? post.platforms : [];
    if (!plats.length) return '—';
    return plats
      .map(function (p) {
        const slug =
          String(p || '')
            .toLowerCase()
            .replace(/[^a-z]/g, '') || 'unknown';
        return (
          '<span class="platform-badge platform-badge--' +
          Utils.escapeHtml(slug) +
          '">' +
          Utils.escapeHtml(String(p)) +
          '</span>'
        );
      })
      .join(' ');
  }

  function postListDate(post) {
    if (post.status === 'published' && post.published_at) return Utils.formatDate(post.published_at);
    if (post.status === 'scheduled' && post.scheduled_at) return Utils.formatDate(post.scheduled_at);
    return Utils.formatDate(post.updated_at || post.created_at);
  }

  function tagChipsRow(post) {
    const tags = post.tags || [];
    if (!tags.length) return '—';
    return tags
      .map(function (t) {
        return (
          '<span class="tag-chip" style="background:' +
          Utils.escapeHtml(t.color || '#6C5CE7') +
          '">' +
          Utils.escapeHtml(t.name) +
          '</span>'
        );
      })
      .join(' ');
  }

  function renderListRows(posts) {
    const tbody = document.getElementById('plannerTableBody');
    if (!tbody) return;
    if (!posts.length) {
      tbody.innerHTML =
        '<tr><td colspan="8" class="text-muted" style="padding:24px;text-align:center">No posts match your filters.</td></tr>';
      return;
    }

    tbody.innerHTML = '';
    posts.forEach(function (p) {
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td class="th-check"><input type="checkbox" data-post-id="' +
        Utils.escapeHtml(String(p.id)) +
        '"></td>' +
        '<td class="thumb-cell">' +
        (p.cover_thumb || p.cover_url
          ? '<img src="' + Utils.escapeHtml(p.cover_thumb || p.cover_url) + '" alt="">'
          : '') +
        '</td>' +
        '<td>' +
        Utils.escapeHtml(Utils.truncate(p.title, 60)) +
        '</td>' +
        '<td><div class="platform-badges">' +
        platformBadges(p) +
        '</div></td>' +
        '<td><span class="badge badge-' +
        (p.status === 'published'
          ? 'success'
          : p.status === 'scheduled'
            ? 'info'
            : p.status === 'failed'
              ? 'danger'
              : 'grey') +
        '">' +
        Utils.escapeHtml(p.status) +
        '</span></td>' +
        '<td>' +
        Utils.escapeHtml(postListDate(p)) +
        '</td>' +
        '<td>' +
        tagChipsRow(p) +
        '</td>' +
        '<td><div class="table-actions">' +
        '<button type="button" class="btn btn-ghost btn-sm" data-edit="' +
        p.id +
        '">Edit</button>' +
        '<button type="button" class="btn btn-ghost btn-sm" data-dup="' +
        p.id +
        '">Duplicate</button>' +
        '<button type="button" class="btn btn-ghost btn-sm" data-del="' +
        p.id +
        '">Delete</button>' +
        '</div></td>';
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('input[type="checkbox"][data-post-id]').forEach(function (cb) {
      cb.addEventListener('change', function () {
        const id = Number(cb.getAttribute('data-post-id'));
        if (cb.checked) selectedPostIds.add(id);
        else selectedPostIds.delete(id);
        updateBulkBar();
      });
    });
    tbody.querySelectorAll('[data-edit]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openEditModal(Number(btn.getAttribute('data-edit')));
      });
    });
    tbody.querySelectorAll('[data-dup]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        duplicatePost(Number(btn.getAttribute('data-dup')));
      });
    });
    tbody.querySelectorAll('[data-del]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        deletePost(Number(btn.getAttribute('data-del')));
      });
    });
  }

  function renderPagination(pack) {
    const el = document.getElementById('listPagination');
    if (!el) return;
    const totalPages = pack.total_pages || 0;
    const page = pack.page || 1;
    if (totalPages < 2) {
      el.innerHTML = '<span class="text-muted">' + (pack.total || 0) + ' posts</span>';
      return;
    }
    el.innerHTML =
      '<span class="text-muted">Page ' +
      page +
      ' / ' +
      totalPages +
      '</span>' +
      '<button type="button" class="btn btn-sm btn-ghost" id="pgPrev"' +
      (page <= 1 ? ' disabled' : '') +
      '>Prev</button>' +
      '<button type="button" class="btn btn-sm btn-ghost" id="pgNext"' +
      (page >= totalPages ? ' disabled' : '') +
      '>Next</button>';
    document.getElementById('pgPrev')?.addEventListener('click', function () {
      listPage = Math.max(1, page - 1);
      loadListView();
    });
    document.getElementById('pgNext')?.addEventListener('click', function () {
      listPage = Math.min(totalPages, page + 1);
      loadListView();
    });
  }

  function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    const c = document.getElementById('bulkCount');
    if (!bar || !c) return;
    const n = selectedPostIds.size;
    bar.hidden = n < 1;
    c.textContent = n + ' selected';
  }

  function switchView(v) {
    currentView = v;
    document.getElementById('calendarView').hidden = v !== 'calendar';
    document.getElementById('listView').hidden = v !== 'list';
    document.getElementById('tabCalendar')?.classList.toggle('active', v === 'calendar');
    document.getElementById('tabList')?.classList.toggle('active', v === 'list');
    if (v === 'list') {
      listPage = 1;
      loadListView();
    } else {
      loadCalendar(calMonth, calYear);
    }
  }

  function prevMonth() {
    calMonth -= 1;
    if (calMonth < 1) {
      calMonth = 12;
      calYear -= 1;
    }
    loadCalendar(calMonth, calYear);
  }

  function nextMonth() {
    calMonth += 1;
    if (calMonth > 12) {
      calMonth = 1;
      calYear += 1;
    }
    loadCalendar(calMonth, calYear);
  }

  function assetPath(rel) {
    rel = String(rel || '').replace(/^\//, '');
    const b = typeof window.__BASE_PATH__ === 'string' ? window.__BASE_PATH__ : '';
    return (b ? b + '/' : '/') + rel;
  }

  function refreshTagDatalist() {
    const dl = document.getElementById('pmTagList');
    if (!dl) return;
    dl.innerHTML = '';
    allTags.forEach(function (t) {
      const o = document.createElement('option');
      o.value = t.name;
      o.setAttribute('data-id', String(t.id));
      dl.appendChild(o);
    });
  }

  function loadTags() {
    return fetchRouteJson('tags', {})
      .then(function (j) {
        if (!j.success) throw new Error(j.message || 'Tags failed');
        allTags = j.data || [];
        refreshTagDatalist();
      })
      .catch(function () {
        allTags = [];
      });
  }

  function renderComposerTags() {
    const wrap = document.getElementById('pmTagChips');
    if (!wrap) return;
    wrap.innerHTML = '';
    allTags
      .filter(function (t) {
        return selectedTagIds.has(Number(t.id));
      })
      .forEach(function (t) {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.style.background = t.color || '#6C5CE7';
        chip.innerHTML =
          Utils.escapeHtml(t.name) +
          ' <button type="button" aria-label="Remove">&times;</button>';
        chip.querySelector('button').addEventListener('click', function () {
          selectedTagIds.delete(Number(t.id));
          renderComposerTags();
        });
        wrap.appendChild(chip);
      });
  }

  function renderComposerMedia() {
    const wrap = document.getElementById('pmMediaPreview');
    if (!wrap) return;
    wrap.innerHTML = '';
    composerMedia.forEach(function (item, idx) {
      const div = document.createElement('div');
      div.className = 'media-preview-item-v2' + (idx === 0 ? ' is-cover' : '');
      const url = item.thumb || item.url;
      const isVideo = item.mime && item.mime.indexOf('video/') === 0;
      if (isVideo) {
        div.innerHTML = '<video src="' + Utils.escapeHtml(item.url) + '" muted></video>' +
          '<span class="pm-type-badge">VID</span>';
      } else {
        div.innerHTML = '<img src="' + Utils.escapeHtml(url) + '" alt="">';
      }
      if (idx === 0) {
        div.insertAdjacentHTML('beforeend', '<span class="pm-cover-badge">Cover</span>');
      }
      const rm = document.createElement('button');
      rm.type = 'button';
      rm.className = 'pm-rm';
      rm.setAttribute('aria-label', 'Remove');
      rm.textContent = '×';
      rm.addEventListener('click', function () {
        composerMedia.splice(idx, 1);
        renderComposerMedia();
        updatePreview();
      });
      div.appendChild(rm);
      wrap.appendChild(div);
    });
    updatePreview();
  }

  function resetComposer() {
    const pid = document.getElementById('pmPostId');
    if (pid) pid.value = '';
    const title = document.getElementById('pmTitle');
    if (title) title.value = '';
    const content = document.getElementById('pmContent');
    if (content) content.value = '';
    const cap = document.getElementById('pmCaption');
    if (cap) cap.value = '';
    document.querySelectorAll('input[name="pm_platform"]').forEach(function (c) {
      c.checked = false;
    });
    const draft = document.querySelector('input[name="pm_status"][value="draft"]');
    if (draft) draft.checked = true;
    const schedRow = document.getElementById('pmScheduleRow');
    if (schedRow) schedRow.style.display = 'none';
    const schedAt = document.getElementById('pmScheduledAt');
    if (schedAt) schedAt.value = '';
    document.querySelectorAll('input[name="pm_status"][data-edit-extra="1"]').forEach(function (el) {
      el.closest('label')?.remove();
    });
    composerMedia.length = 0;
    selectedTagIds.clear();
    activePreviewPlatform = 'instagram';
    renderComposerMedia();
    renderComposerTags();
    updateCharCounters();
    updatePreview();
  }

  /* ── Preview engine ──────────────────────────────────────── */

  function updateCharCounters() {
    const ta = document.getElementById('pmContent');
    const cap = document.getElementById('pmCaption');
    const contentLen = ta ? ta.value.length : 0;
    const captionLen = cap ? cap.value.length : 0;

    const cc = document.getElementById('pmCharContent');
    if (cc) {
      cc.textContent = String(contentLen);
      cc.className = 'char-counter' +
        (contentLen > 4500 ? ' over' : contentLen > 4000 ? ' warn' : '');
    }
    const cp = document.getElementById('pmCharCaption');
    if (cp) {
      cp.textContent = captionLen + ' / 2200';
      cp.className = 'char-counter' +
        (captionLen > 2200 ? ' over' : captionLen > 1980 ? ' warn' : '');
    }

    const warnWrap = document.getElementById('pmPlatformCharWarnings');
    if (!warnWrap) return;
    warnWrap.innerHTML = '';
    const text = ta ? ta.value : '';
    const checkedPlatforms = Array.from(document.querySelectorAll('input[name="pm_platform"]:checked'))
      .map(function (c) { return c.value; });
    checkedPlatforms.forEach(function (plat) {
      const limit = PLATFORM_LIMITS[plat] || 0;
      if (!limit) return;
      const len = text.length;
      if (len > limit) {
        const badge = document.createElement('span');
        badge.className = 'pcw-badge over';
        badge.textContent = plat + ': ' + len + '/' + limit + ' (over by ' + (len - limit) + ')';
        warnWrap.appendChild(badge);
      } else if (len > limit * 0.9) {
        const badge = document.createElement('span');
        badge.className = 'pcw-badge warn';
        badge.textContent = plat + ': ' + len + '/' + limit;
        warnWrap.appendChild(badge);
      }
    });
  }

  function getPreviewData() {
    const text = (document.getElementById('pmContent')?.value || '').trim();
    const caption = (document.getElementById('pmCaption')?.value || '').trim();
    const title = (document.getElementById('pmTitle')?.value || '').trim();
    const firstMedia = composerMedia[0] || null;
    const mediaUrl = firstMedia ? (firstMedia.thumb || firstMedia.url) : null;
    const isVideo = firstMedia && firstMedia.mime && firstMedia.mime.indexOf('video/') === 0;
    return { text: text, caption: caption, title: title, mediaUrl: mediaUrl, isVideo: isVideo };
  }

  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function buildInstagramPreview(d) {
    const body = d.caption || d.text;
    const media = d.mediaUrl
      ? (d.isVideo
          ? '<video src="' + escHtml(d.mediaUrl) + '" muted playsinline style="width:100%;height:100%;object-fit:cover"></video>'
          : '<img src="' + escHtml(d.mediaUrl) + '" alt="" style="width:100%;height:100%;object-fit:cover">')
      : '<div class="preview-ig-placeholder"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg><span>No image yet</span></div>';

    const captionHtml = body
      ? '<p class="preview-ig-caption"><span class="ig-username-inline">you</span> ' + escHtml(body).replace(/\n/g, '<br>') + '</p>'
      : '<p class="preview-ig-caption" style="color:#aaa;font-style:italic">Your caption will appear here…</p>';

    return '<div class="post-preview-card">' +
      '<div class="preview-ig-header">' +
        '<div class="preview-ig-avatar">Y</div>' +
        '<span class="preview-ig-username">you</span>' +
        '<span class="preview-ig-more">···</span>' +
      '</div>' +
      '<div class="preview-ig-image">' + media + '</div>' +
      '<div class="preview-ig-actions">' +
        '<svg class="preview-ig-actions" style="width:22px;height:22px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>' +
        '<svg style="width:22px;height:22px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>' +
        '<svg style="width:22px;height:22px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>' +
        '<svg class="ig-save" style="width:22px;height:22px;margin-left:auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>' +
      '</div>' +
      '<div class="preview-ig-body">' +
        '<div class="preview-ig-likes">1,234 likes</div>' +
        captionHtml +
        '<div class="preview-ig-time">Just now</div>' +
      '</div>' +
    '</div>';
  }

  function buildTwitterPreview(d) {
    const body = d.text;
    const limit = PLATFORM_LIMITS.twitter;
    const len = body.length;
    const pct = Math.min(len / limit, 1);
    const over = len > limit;
    const warn = len > limit * 0.9;
    const circumference = 2 * Math.PI * 9;
    const offset = circumference * (1 - pct);
    const ringColor = over ? '#f44336' : warn ? '#f59e0b' : '#1d9bf0';

    const media = d.mediaUrl
      ? (d.isVideo
          ? '<video src="' + escHtml(d.mediaUrl) + '" muted playsinline style="width:100%;height:100%;object-fit:cover"></video>'
          : '<img src="' + escHtml(d.mediaUrl) + '" alt="" style="width:100%;height:100%;object-fit:cover">')
      : '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:.4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';

    const bodyHtml = body
      ? '<div class="preview-tw-body' + (over ? ' over-limit' : '') + '">' + escHtml(body).replace(/\n/g, '<br>') + '</div>'
      : '<div class="preview-tw-body" style="color:#aaa;font-style:italic">What\'s happening?</div>';

    const charLeft = limit - len;
    const charLabel = over
      ? '<span style="color:#f44336;font-weight:700">-' + Math.abs(charLeft) + '</span>'
      : String(charLeft);

    return '<div class="post-preview-card preview-tw">' +
      '<div class="preview-tw-header">' +
        '<div class="preview-tw-avatar">Y</div>' +
        '<div class="preview-tw-meta">' +
          '<div class="preview-tw-name-row"><span class="preview-tw-name">You</span><span class="preview-tw-verified">✓</span></div>' +
          '<div class="preview-tw-handle">@yourhandle</div>' +
        '</div>' +
      '</div>' +
      bodyHtml +
      (d.mediaUrl ? '<div class="preview-tw-image">' + media + '</div>' : '') +
      '<div class="preview-tw-actions">' +
        '<div class="preview-tw-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> Reply</div>' +
        '<div class="preview-tw-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3"/></svg> RT</div>' +
        '<div class="preview-tw-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div>' +
      '</div>' +
      '<div class="preview-tw-char-row">' +
        charLabel +
        '<div class="tw-ring-wrap">' +
          '<svg width="24" height="24" viewBox="0 0 24 24">' +
            '<circle class="tw-ring-bg" cx="12" cy="12" r="9" stroke-dasharray="' + circumference + '" stroke-dashoffset="0"/>' +
            '<circle class="tw-ring-fg' + (over ? ' over' : warn ? ' warn' : '') + '" cx="12" cy="12" r="9"' +
              ' stroke="' + ringColor + '"' +
              ' stroke-dasharray="' + circumference + '"' +
              ' stroke-dashoffset="' + offset + '"/>' +
          '</svg>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function buildTiktokPreview(d) {
    const caption = d.caption || d.text;
    const media = d.mediaUrl
      ? (d.isVideo
          ? '<video src="' + escHtml(d.mediaUrl) + '" muted playsinline loop style="width:100%;height:100%;object-fit:cover"></video>'
          : '<img src="' + escHtml(d.mediaUrl) + '" alt="" style="width:100%;height:100%;object-fit:cover">')
      : '<div class="preview-tiktok-placeholder-bg"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></div>';

    const lines = caption
      ? escHtml(caption).replace(/\n/g, ' ')
      : '<span style="opacity:.5">Your caption here…</span>';

    return '<div class="preview-tiktok-wrap">' +
      '<div class="preview-tiktok-phone">' +
        '<div class="preview-tiktok-video-area">' + media + '</div>' +
        '<div class="preview-tiktok-overlay">' +
          '<div class="preview-tiktok-user">@you <span class="tiktok-follow-btn">Follow</span></div>' +
          '<div class="preview-tiktok-caption">' + lines + '</div>' +
          '<div class="preview-tiktok-sound">♪ Original sound - you</div>' +
        '</div>' +
        '<div class="preview-tiktok-sidebar">' +
          '<div class="tiktok-sidebar-item"><svg viewBox="0 0 24 24" fill="white"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>89K</div>' +
          '<div class="tiktok-sidebar-item"><svg viewBox="0 0 24 24" fill="white"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>4.2K</div>' +
          '<div class="tiktok-sidebar-item"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>Share</div>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function buildYoutubePreview(d) {
    const titleText = d.title || (d.text.substring(0, 100)) || 'Your video title';
    const media = d.mediaUrl
      ? (d.isVideo
          ? '<video src="' + escHtml(d.mediaUrl) + '" muted playsinline style="width:100%;height:100%;object-fit:cover"></video>'
          : '<img src="' + escHtml(d.mediaUrl) + '" alt="" style="width:100%;height:100%;object-fit:cover">')
      : '<div class="preview-yt-placeholder-bg"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></div>';

    return '<div class="post-preview-card">' +
      '<div class="preview-yt-thumbnail">' +
        media +
        '<div class="preview-yt-play-btn"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></div>' +
        '<span class="preview-yt-duration-badge">0:00</span>' +
      '</div>' +
      '<div class="preview-yt-info">' +
        '<div class="preview-yt-channel-avatar">Y</div>' +
        '<div class="preview-yt-meta">' +
          '<div class="preview-yt-title">' + escHtml(titleText) + '</div>' +
          '<div class="preview-yt-channel-name">Your Channel</div>' +
          '<div class="preview-yt-stats">0 views · Just now</div>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function switchPreviewPlatform(platform) {
    activePreviewPlatform = platform;
    document.querySelectorAll('.preview-tab').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-preview-platform') === platform);
    });
    updatePreview();
  }

  function updatePreview() {
    const container = document.getElementById('previewContent');
    const charInfo = document.getElementById('previewCharInfo');
    if (!container) return;
    const d = getPreviewData();
    let html = '';
    switch (activePreviewPlatform) {
      case 'instagram': html = buildInstagramPreview(d); break;
      case 'twitter':   html = buildTwitterPreview(d);   break;
      case 'tiktok':    html = buildTiktokPreview(d);    break;
      case 'youtube':   html = buildYoutubePreview(d);   break;
      default:          html = buildInstagramPreview(d);
    }
    container.innerHTML = html;
    if (charInfo) {
      const limit = PLATFORM_LIMITS[activePreviewPlatform] || 0;
      const len = activePreviewPlatform === 'youtube' ? d.title.length : d.text.length;
      if (limit) {
        const left = limit - len;
        const cls = left < 0 ? 'over' : '';
        charInfo.className = 'preview-char-info ' + cls;
        charInfo.textContent = left < 0
          ? (Math.abs(left) + ' characters over the ' + limit + ' limit')
          : (left + ' characters remaining');
      } else {
        charInfo.className = 'preview-char-info';
        charInfo.textContent = '';
      }
    }
  }

  /* ── End preview engine ─────────────────────────────────── */

  function bindComposerLive() {
    const ta = document.getElementById('pmContent');
    const cap = document.getElementById('pmCaption');
    const title = document.getElementById('pmTitle');

    function onInput() {
      updateCharCounters();
      updatePreview();
    }

    ta?.addEventListener('input', onInput);
    cap?.addEventListener('input', onInput);
    title?.addEventListener('input', onInput);
    ta?.addEventListener('input', function () {
      ta.classList.remove('field-invalid');
    });
    title?.addEventListener('input', function () {
      title.classList.remove('field-invalid');
    });

    document.querySelectorAll('input[name="pm_platform"]').forEach(function (r) {
      r.addEventListener('change', function () {
        updateCharCounters();
        updatePreview();
      });
    });

    document.querySelectorAll('input[name="pm_status"]').forEach(function (r) {
      r.addEventListener('change', function () {
        const v = document.querySelector('input[name="pm_status"]:checked')?.value;
        document.getElementById('pmScheduleRow').style.display = v === 'scheduled' ? 'block' : 'none';
      });
    });

    const dz = document.getElementById('pmDropzone');
    const fi = document.getElementById('pmFileInput');
    document.getElementById('pmBrowse')?.addEventListener('click', function () {
      fi?.click();
    });
    fi?.addEventListener('change', function () {
      if (fi.files && fi.files.length) handleFiles(Array.from(fi.files));
      fi.value = '';
    });
    window.Media.initDropZone(dz, handleFiles);

    document.getElementById('pmPickLibrary')?.addEventListener('click', function () {
      window.Media.openMediaLibrary(function (f) {
        composerMedia.push({
          id: f.id,
          url: f.cdn_url,
          thumb: f.thumbnail_url || f.cdn_url,
          mime: f.mime_type,
        });
        renderComposerMedia();
      });
    });

    document.querySelectorAll('.preview-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        switchPreviewPlatform(btn.getAttribute('data-preview-platform') || 'instagram');
      });
    });

    document.querySelectorAll('.composer-mobile-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const panel = btn.getAttribute('data-panel') || 'form';
        const split = document.getElementById('composerSplit');
        document.querySelectorAll('.composer-mobile-tab').forEach(function (b) {
          b.classList.toggle('active', b === btn);
          b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        if (split) split.classList.toggle('show-mobile-preview', panel === 'preview');
      });
    });

    document.getElementById('pmTagCreate')?.addEventListener('click', function () {
      const name = window.prompt('New tag name');
      if (!name || !name.trim()) return;
      window
        .api('create_tag', 'POST', { name: name.trim(), color: '#6C5CE7' })
        .then(function (res) {
          const t = res.data;
          if (t && t.id) {
            allTags.push(t);
            refreshTagDatalist();
            selectedTagIds.add(Number(t.id));
            renderComposerTags();
          }
        })
        .catch(function (e) {
          window.Toast.error(e.message || 'Could not create tag');
        });
    });

    document.getElementById('pmTagSearch')?.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      const name = e.target.value.trim();
      if (!name) return;
      const tag = allTags.find(function (t) {
        return t.name.toLowerCase() === name.toLowerCase();
      });
      if (tag) {
        selectedTagIds.add(Number(tag.id));
        renderComposerTags();
        e.target.value = '';
      }
    });
  }

  function handleFiles(files) {
    files.forEach(function (file) {
      window.Media.uploadFile(file)
        .then(function (j) {
          if (!j.success) throw new Error(j.message || 'Upload failed');
          const d = j.data || {};
          composerMedia.push({
            id: d.id,
            url: d.cdn_url,
            thumb: d.thumbnail_url || d.cdn_url,
            mime: d.mime_type,
          });
          renderComposerMedia();
          window.Toast.success('Uploaded');
        })
        .catch(function (err) {
          window.Toast.error(err.message || 'Upload failed');
        });
    });
  }

  function openCreateModal(dateStr) {
    composerMedia.length = 0;
    selectedTagIds.clear();
    loadTags().then(function () {
      window.Modal.open(
        'New post',
        '<div id="postComposerMount"><p class="text-muted" style="padding:12px">Loading form…</p></div>',
        '<button type="button" class="btn btn-primary" id="pmSave">Save Post</button><button type="button" class="btn btn-ghost" id="pmCancel">Cancel</button>',
      );
      document.getElementById('modal')?.classList.add('modal--composer');
      document.getElementById('pmSave').addEventListener('click', submitPostForm);
      document.getElementById('pmCancel').addEventListener('click', function () {
        window.Modal.close();
        document.getElementById('modal')?.classList.remove('modal--composer');
      });
      document.getElementById('modalClose')?.addEventListener('click', function () {
        document.getElementById('modal')?.classList.remove('modal--composer');
      }, { once: true });
      fetch(assetPath('frontend/pages/planner/post-create.html') + '?v=' + Date.now())
        .then(function (r) { return r.text(); })
        .then(function (html) {
          document.getElementById('postComposerMount').innerHTML = html;
          bindComposerLive();
          resetComposer();
          renderComposerMedia();
          renderComposerTags();
          updatePreview();
          if (dateStr) {
            const sr = document.querySelector('input[name="pm_status"][value="scheduled"]');
            if (sr) sr.checked = true;
            const row = document.getElementById('pmScheduleRow');
            if (row) row.style.display = 'block';
            const sat = document.getElementById('pmScheduledAt');
            if (sat) sat.value = dateStr + 'T09:00';
          }
        });
    });
  }

  function openEditModal(postId) {
    loadTags()
      .then(function () {
        return fetchRouteJson('post', { id: postId });
      })
      .then(function (j) {
        if (!j.success) throw new Error(j.message || 'Not found');
        const p = j.data;
        window.Modal.open(
          'Edit post',
          '<div id="postComposerMount"><p class="text-muted" style="padding:12px">Loading form…</p></div>',
          '<button type="button" class="btn btn-primary" id="pmSave">Save Post</button><button type="button" class="btn btn-ghost" id="pmCancel">Cancel</button>',
        );
        document.getElementById('modal')?.classList.add('modal--composer');
        document.getElementById('pmSave').addEventListener('click', submitPostForm);
        document.getElementById('pmCancel').addEventListener('click', function () {
          window.Modal.close();
          document.getElementById('modal')?.classList.remove('modal--composer');
        });
        document.getElementById('modalClose')?.addEventListener('click', function () {
          document.getElementById('modal')?.classList.remove('modal--composer');
        }, { once: true });
        return fetch(assetPath('frontend/pages/planner/post-create.html') + '?v=' + Date.now()).then(function (r) {
          return r.text().then(function (html) {
            return { p: p, html: html };
          });
        });
      })
      .then(function (pack) {
        const p = pack.p;
        document.getElementById('postComposerMount').innerHTML = pack.html;
        bindComposerLive();
        document.getElementById('pmPostId').value = String(p.id);
        document.getElementById('pmTitle').value = p.title || '';
        document.getElementById('pmContent').value = p.content || '';
        document.getElementById('pmCaption').value = p.caption || '';
        (p.platforms || []).forEach(function (plat) {
          const cb = document.querySelector('input[name="pm_platform"][value="' + plat + '"]');
          if (cb) cb.checked = true;
        });

        if (p.status === 'published') {
          const statusRow = document.querySelector('.status-toggle-row');
          if (statusRow) {
            const lab = document.createElement('label');
            lab.className = 'status-toggle-opt';
            lab.innerHTML =
              '<input type="radio" name="pm_status" value="published" data-edit-extra="1">' +
              '<span class="status-toggle-lbl">Published</span>';
            statusRow.appendChild(lab);
            lab.querySelector('input').checked = true;
          }
        } else if (p.status === 'scheduled') {
          const sr = document.querySelector('input[name="pm_status"][value="scheduled"]');
          if (sr) sr.checked = true;
        } else {
          const dr = document.querySelector('input[name="pm_status"][value="draft"]');
          if (dr) dr.checked = true;
        }

        const showSched = p.status === 'scheduled';
        document.getElementById('pmScheduleRow').style.display = showSched ? 'block' : 'none';
        if (p.scheduled_at) {
          const dt = new Date(p.scheduled_at);
          if (!Number.isNaN(dt.getTime())) {
            document.getElementById('pmScheduledAt').value = dt.toISOString().slice(0, 16);
          }
        }
        composerMedia.length = 0;
        (p.media || []).forEach(function (m) {
          composerMedia.push({
            id: m.id,
            url: m.cdn_url,
            thumb: m.thumbnail_url || m.cdn_url,
            mime: m.mime_type,
          });
        });
        selectedTagIds.clear();
        (p.tags || []).forEach(function (t) {
          selectedTagIds.add(Number(t.id));
        });
        renderComposerMedia();
        renderComposerTags();
        updateCharCounters();
        updatePreview();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Could not load post');
      });
  }

  function submitPostForm() {
    const id = document.getElementById('pmPostId')?.value;
    const title = document.getElementById('pmTitle')?.value?.trim() || '';
    const content = document.getElementById('pmContent')?.value?.trim() || '';
    const caption = document.getElementById('pmCaption')?.value?.trim() || '';
    const status = document.querySelector('input[name="pm_status"]:checked')?.value || 'draft';
    const sched = document.getElementById('pmScheduledAt')?.value?.trim() || '';

    if (!title || !content) {
      window.Toast.error('Title and content are required.');
      const missing = !title ? document.getElementById('pmTitle') : document.getElementById('pmContent');
      if (missing) {
        missing.classList.add('field-invalid');
        missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
        missing.focus();
      }
      return;
    }

    const platforms = [];
    document.querySelectorAll('input[name="pm_platform"]:checked').forEach(function (c) {
      platforms.push(c.value);
    });
    if (platforms.length < 1) {
      window.Toast.error('Select at least one platform.');
      return;
    }

    if (status === 'scheduled' && !sched) {
      window.Toast.error('Pick a scheduled date and time.');
      return;
    }

    if (status === 'scheduled') {
      const t = new Date(sched).getTime();
      if (t <= Date.now()) {
        window.Toast.error('Scheduled time should be in the future.');
        return;
      }
    }

    const mediaIds = composerMedia.map(function (m) {
      return m.id;
    });
    const tagIds = Array.from(selectedTagIds);

    // "Post now" reuses the scheduled pipeline (queued for immediate publish
    // via the job queue) since that's the only path that actually calls the
    // platform APIs -- there's no separate synchronous immediate-publish path.
    const isPostNow = status === 'now';
    const sendStatus = isPostNow ? 'scheduled' : status;

    const payload = {
      _token: window.__CSRF__ || '',
      title: title,
      content: content,
      caption: caption,
      platforms: JSON.stringify(platforms),
      media_ids: JSON.stringify(mediaIds),
      tag_ids: JSON.stringify(tagIds),
      status: sendStatus,
    };
    if (isPostNow) {
      const now = new Date();
      now.setSeconds(now.getSeconds() + 5);
      payload.scheduled_at = now.toISOString().slice(0, 19).replace('T', ' ');
    } else if (status === 'scheduled') {
      payload.scheduled_at = sched;
    }

    const route = id ? 'update_post' : 'create_post';
    if (id) payload.id = id;

    window
      .api(route, 'POST', payload)
      .then(function () {
        window.Toast.success(id ? 'Post updated' : 'Post created');
        window.Modal.close();
        if (currentView === 'calendar') loadCalendar(calMonth, calYear);
        else loadListView();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Save failed');
      });
  }

  function deletePost(postId) {
    if (!postId || !window.confirm('Delete this post?')) return;
    window
      .api('delete_post', 'POST', { post_id: String(postId) })
      .then(function () {
        window.Toast.success('Deleted');
        if (currentView === 'calendar') loadCalendar(calMonth, calYear);
        else loadListView();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Delete failed');
      });
  }

  function duplicatePost(postId) {
    window
      .api('duplicate_post', 'POST', { post_id: String(postId) })
      .then(function () {
        window.Toast.success('Duplicated');
        if (currentView === 'calendar') loadCalendar(calMonth, calYear);
        else loadListView();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Duplicate failed');
      });
  }

  function bulkAction(action, ids) {
    if (!ids.length) return;
    const payload = {
      action: action,
      ids: JSON.stringify(ids),
    };
    if (action === 'status') {
      payload.status = document.getElementById('bulkStatus')?.value || '';
    }
    window
      .api('bulk_posts', 'POST', payload)
      .then(function () {
        window.Toast.success('Done');
        selectedPostIds.clear();
        updateBulkBar();
        loadListView();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Bulk action failed');
      });
  }

  function init() {
    const t = new Date();
    calYear = t.getFullYear();
    calMonth = t.getMonth() + 1;

    document.getElementById('tabCalendar')?.addEventListener('click', function () {
      switchView('calendar');
    });
    document.getElementById('tabList')?.addEventListener('click', function () {
      switchView('list');
    });
    document.getElementById('newPostBtn')?.addEventListener('click', function () {
      openCreateModal(null);
    });
    document.getElementById('calPrev')?.addEventListener('click', prevMonth);
    document.getElementById('calNext')?.addEventListener('click', nextMonth);
    document.getElementById('filterApply')?.addEventListener('click', function () {
      listPage = 1;
      selectedPostIds.clear();
      const sa = document.getElementById('selectAllPosts');
      if (sa) sa.checked = false;
      updateBulkBar();
      loadListView();
    });
    document.getElementById('selectAllPosts')?.addEventListener('change', function (e) {
      const on = e.target.checked;
      document.querySelectorAll('#plannerTableBody input[type="checkbox"][data-post-id]').forEach(function (cb) {
        cb.checked = on;
        const id = Number(cb.getAttribute('data-post-id'));
        if (on) selectedPostIds.add(id);
        else selectedPostIds.delete(id);
      });
      updateBulkBar();
    });
    document.getElementById('bulkDelete')?.addEventListener('click', function () {
      if (!selectedPostIds.size || !window.confirm('Delete selected posts?')) return;
      bulkAction('delete', Array.from(selectedPostIds));
    });
    document.getElementById('bulkStatusApply')?.addEventListener('click', function () {
      const st = document.getElementById('bulkStatus')?.value;
      if (!st || !selectedPostIds.size) {
        window.Toast.warning('Pick a status and select posts.');
        return;
      }
      bulkAction('status', Array.from(selectedPostIds));
    });

    loadCalendar(calMonth, calYear);
  }

  window.Planner = {
    loadCalendar: loadCalendar,
    loadListView: loadListView,
    openCreateModal: openCreateModal,
    openEditModal: openEditModal,
    submitPostForm: submitPostForm,
    deletePost: deletePost,
    duplicatePost: duplicatePost,
    bulkAction: bulkAction,
    prevMonth: prevMonth,
    nextMonth: nextMonth,
  };

  init();
})();
