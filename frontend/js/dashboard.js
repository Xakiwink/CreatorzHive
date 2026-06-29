(function () {
  let postChart = null;
  let lastPostStatusBreakdown = null;

  const DASHBOARD_STAT_SKELETON = (
    '<div class="stat-card skeleton-block" data-skeleton>' +
    '<div class="skeleton skeleton-text" style="width:40%;height:14px;margin-bottom:12px"></div>' +
    '<div class="skeleton skeleton-text" style="width:55%;height:28px"></div>' +
    '<div class="skeleton skeleton-text" style="width:30%;height:12px;margin-top:10px"></div>' +
    '</div>'
  ).repeat(6);

  const RECENT_TABLE_SKELETON =
    '<div class="table-wrapper"><table class="table"><thead><tr>' +
    '<th>Title</th><th>Platforms</th><th>Status</th><th>Date</th><th>Actions</th>' +
    '</tr></thead><tbody>' +
    Array.from({ length: 5 }, function () {
      return '<tr><td colspan="5"><div class="skeleton skeleton-text" style="height:32px"></div></td></tr>';
    }).join('') +
    '</tbody></table></div>';

  const UPCOMING_SKELETON = Array.from({ length: 4 }, function () {
    return (
      '<div class="upcoming-item">' +
      '<div class="upcoming-thumb skeleton skeleton-circle" style="border-radius:8px"></div>' +
      '<div class="upcoming-body" style="flex:1">' +
      '<div class="skeleton skeleton-text" style="width:70%;height:14px;margin-bottom:8px"></div>' +
      '<div class="skeleton skeleton-text" style="width:40%;height:12px"></div>' +
      '</div></div>'
    );
  }).join('');

  const PLATFORM_SKELETON = Array.from({ length: 5 }, function () {
    return (
      '<div class="platform-row">' +
      '<div class="skeleton skeleton-circle" style="width:10px;height:10px"></div>' +
      '<div class="skeleton skeleton-text" style="flex:1;height:14px;margin-left:12px"></div>' +
      '</div>'
    );
  }).join('');

  function showDashboardSkeletons() {
    const grid = document.getElementById('statGrid');
    if (grid) {
      grid.innerHTML = DASHBOARD_STAT_SKELETON;
      grid.setAttribute('aria-busy', 'true');
    }
    const recent = document.getElementById('recentPostsMount');
    if (recent) recent.innerHTML = RECENT_TABLE_SKELETON;
    const up = document.getElementById('upcomingMount');
    if (up) up.innerHTML = UPCOMING_SKELETON;
    const plat = document.getElementById('platformStatusMount');
    if (plat) plat.innerHTML = PLATFORM_SKELETON;
  }

  function chartLegendColor() {
    const c = getComputedStyle(document.documentElement)
      .getPropertyValue('--color-text-secondary')
      .trim();
    return c || '#64748b';
  }

  function updatePostChartTheme() {
    if (!postChart) return;
    postChart.options.plugins.legend.labels.color = chartLegendColor();
    postChart.update();
  }

  function getGreeting(name) {
    const raw =
      name !== undefined && name !== null
        ? String(name)
        : String((window.__USER__ || {}).name || '');
    const h = new Date().getHours();
    let part = 'Good evening';
    if (h < 12) part = 'Good morning';
    else if (h < 17) part = 'Good afternoon';
    const first = raw.trim().split(/\s+/)[0] || 'Creator';
    return part + ', ' + first + ' 👋';
  }

  function formatFollowers(n) {
    const x = Number(n) || 0;
    if (x >= 1e6) {
      const v = x / 1e6;
      return (v >= 10 ? v.toFixed(0) : v.toFixed(1)).replace(/\.0$/, '') + 'M';
    }
    if (x >= 1e3) {
      const v = x / 1e3;
      return (v >= 10 ? v.toFixed(0) : v.toFixed(1)).replace(/\.0$/, '') + 'K';
    }
    return String(x);
  }

  function engagementAccentClass(rate) {
    const r = Number(rate) || 0;
    if (r < 2) return 'stat-card--engagement-low';
    if (r <= 5) return 'stat-card--engagement-mid';
    return 'stat-card--engagement-high';
  }

  function trendLine(key, stats) {
    const t = stats['trend_' + key];
    if (t == null || Number(t) === 0) {
      return '<span class="stat-trend">— vs last week</span>';
    }
    const n = Number(t);
    const pos = n > 0;
    return (
      '<span class="stat-trend ' +
      (pos ? 'positive' : 'negative') +
      '">' +
      (pos ? '+' : '') +
      n +
      '% vs last week</span>'
    );
  }

  function statusBadge(status) {
    const s = String(status || 'draft');
    const map = {
      draft: 'badge-grey',
      scheduled: 'badge-info',
      published: 'badge-success',
      failed: 'badge-danger',
    };
    const cls = map[s] || 'badge-grey';
    return (
      '<span class="badge ' + cls + '">' + Utils.escapeHtml(s) + '</span>'
    );
  }

  function platformLabel(p) {
    const labels = {
      instagram: 'Instagram',
      tiktok: 'TikTok',
      youtube: 'YouTube',
      twitter: 'Twitter',
    };
    if (p === 'unknown') return 'Other';
    return labels[p] || Utils.escapeHtml(p);
  }

  function platformBadge(p) {
    const slug =
      String(p || '')
        .toLowerCase()
        .replace(/[^a-z]/g, '') || 'unknown';
    const safe = Utils.escapeHtml(slug);
    return (
      '<span class="platform-badge platform-badge--' +
      safe +
      '">' +
      platformLabel(slug) +
      '</span>'
    );
  }

  function postDateCell(post) {
    if (post.status === 'published' && post.published_at) {
      return Utils.formatDate(post.published_at);
    }
    if (post.status === 'scheduled' && post.scheduled_at) {
      return Utils.formatDate(post.scheduled_at);
    }
    return Utils.formatDate(post.updated_at || post.created_at);
  }

  function renderStatCards(stats) {
    const grid = document.getElementById('statGrid');
    if (!grid) return;
    const rev = Utils.formatCurrency(stats.total_revenue || 0, 'TZS');
    const eng = (Number(stats.avg_engagement_rate) || 0).toFixed(1) + '%';
    grid.innerHTML =
      '<div class="stat-card stat-card--accent-posts fade-in">' +
      '<div class="stat-icon" aria-hidden="true">📄</div>' +
      '<div class="stat-label">Total posts</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(stats.total_posts ?? 0)) +
      '</div>' +
      trendLine('posts', stats) +
      '</div>' +
      '<div class="stat-card stat-card--accent-published fade-in">' +
      '<div class="stat-icon" aria-hidden="true">✅</div>' +
      '<div class="stat-label">Published</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(stats.published_posts ?? 0)) +
      '</div>' +
      trendLine('published', stats) +
      '</div>' +
      '<div class="stat-card stat-card--accent-scheduled fade-in">' +
      '<div class="stat-icon" aria-hidden="true">🕐</div>' +
      '<div class="stat-label">Scheduled</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(stats.scheduled_posts ?? 0)) +
      '</div>' +
      trendLine('scheduled', stats) +
      '</div>' +
      '<div class="stat-card stat-card--accent-followers fade-in">' +
      '<div class="stat-icon" aria-hidden="true">👥</div>' +
      '<div class="stat-label">Followers</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(formatFollowers(stats.total_followers)) +
      '</div>' +
      trendLine('followers', stats) +
      '</div>' +
      '<div class="stat-card fade-in ' +
      engagementAccentClass(stats.avg_engagement_rate) +
      '">' +
      '<div class="stat-icon" aria-hidden="true">📊</div>' +
      '<div class="stat-label">Avg engagement</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(eng) +
      '</div>' +
      '</div>' +
      '<div class="stat-card stat-card--revenue fade-in">' +
      '<div class="stat-icon" aria-hidden="true">💰</div>' +
      '<div class="stat-label">Revenue</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(rev) +
      '</div>' +
      '</div>';
    grid.setAttribute('aria-busy', 'false');
  }

  function renderRecentPosts(posts) {
    const mount = document.getElementById('recentPostsMount');
    if (!mount) return;
    if (!posts || posts.length === 0) {
      mount.innerHTML =
        '<div class="empty-state">' +
        '<div class="empty-icon" aria-hidden="true">📝</div>' +
        '<p>Create your first post to see it here.</p>' +
        '<button type="button" class="btn btn-primary" id="emptyCreatePost">Create your first post</button>' +
        '</div>';
      document.getElementById('emptyCreatePost')?.addEventListener('click', openQuickPostModal);
      return;
    }

    let rows = '';
    posts.forEach((p) => {
      const plats = Array.isArray(p.platforms) ? p.platforms : [];
      const badges = plats.map(platformBadge).join(' ') || '—';
      rows +=
        '<tr data-post-id="' +
        Utils.escapeHtml(String(p.id)) +
        '">' +
        '<td>' +
        Utils.escapeHtml(Utils.truncate(p.title, 48)) +
        '</td>' +
        '<td><div class="platform-badges">' +
        badges +
        '</div></td>' +
        '<td>' +
        statusBadge(p.status) +
        '</td>' +
        '<td>' +
        Utils.escapeHtml(postDateCell(p)) +
        '</td>' +
        '<td><div class="table-actions">' +
        '<a class="btn btn-ghost btn-sm" href="' +
        Utils.escapeHtml(window.routeQuery('planner')) +
        '">Edit</a>' +
        '<button type="button" class="btn btn-ghost btn-sm" data-delete-post="' +
        Utils.escapeHtml(String(p.id)) +
        '">Delete</button>' +
        '</div></td>' +
        '</tr>';
    });

    mount.innerHTML =
      '<div class="table-wrapper"><table class="table">' +
      '<thead><tr><th>Title</th><th>Platforms</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>' +
      '<tbody>' +
      rows +
      '</tbody></table></div>';

    mount.querySelectorAll('[data-delete-post]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-delete-post');
        if (id) deletePost(Number(id));
      });
    });
  }

  function renderUpcomingPosts(posts) {
    const mount = document.getElementById('upcomingMount');
    if (!mount) return;
    if (!posts || posts.length === 0) {
      mount.innerHTML =
        '<p class="text-muted" style="margin:0;font-size:var(--text-sm)">No posts scheduled in the next 14 days.</p>';
      return;
    }
    let html = '';
    posts.forEach((p) => {
      const thumb = p.cover_thumb || p.cover_url || '';
      const plats = Array.isArray(p.platforms) ? p.platforms : [];
      const badges = plats.map(platformBadge).join(' ');
      const when = p.scheduled_at
        ? new Date(p.scheduled_at).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
          })
        : '';
      html +=
        '<div class="upcoming-item">' +
        (thumb
          ? '<img class="upcoming-thumb" src="' +
            Utils.escapeHtml(thumb) +
            '" alt="">'
          : '<div class="upcoming-thumb" aria-hidden="true"></div>') +
        '<div class="upcoming-body">' +
        '<p class="upcoming-title">' +
        Utils.escapeHtml(Utils.truncate(p.title, 60)) +
        '</p>' +
        '<div class="platform-badges" style="margin-top:6px">' +
        badges +
        '</div>' +
        '<p class="upcoming-time">' +
        Utils.escapeHtml(when) +
        '</p>' +
        '</div></div>';
    });
    mount.innerHTML = html;
  }

  function renderPlatformStatus(platforms) {
    const mount = document.getElementById('platformStatusMount');
    if (!mount) return;
    let html = '';
    (platforms || []).forEach((row) => {
      const p = row.platform;
      const conn = row.connected;
      html +=
        '<div class="platform-row">' +
        '<div class="platform-meta">' +
        '<span class="platform-dot' +
        (conn ? ' connected' : '') +
        '" aria-hidden="true"></span>' +
        '<div>' +
        '<div class="platform-name">' +
        platformLabel(p) +
        '</div>' +
        (conn
          ? '<div class="platform-username">@' +
            Utils.escapeHtml(row.username || '') +
            '</div>'
          : '') +
        '</div></div>' +
        (conn
          ? ''
          : '<a class="btn btn-sm btn-secondary" href="' +
            Utils.escapeHtml(window.routeQuery('settings', { tab: 'integrations' })) +
            '">Connect</a>') +
        '</div>';
    });
    mount.innerHTML = html;
  }

  function loadChartScript() {
    return new Promise(function (resolve, reject) {
      if (window.Chart) {
        resolve();
        return;
      }
      const s = document.createElement('script');
      const base =
        typeof window.__BASE_PATH__ === 'string' ? String(window.__BASE_PATH__).replace(/\/$/, '') : '';
      s.src =
        (base === '' ? '' : base + '/') + 'frontend/assets/chart.js/chart.umd.min.js';
      s.async = true;
      s.onload = function () {
        resolve();
      };
      s.onerror = function () {
        reject(new Error('Chart.js failed to load'));
      };
      document.head.appendChild(s);
    });
  }

  function renderPostChart(breakdown) {
    const canvas = document.getElementById('postStatusChart');
    if (!canvas) return;
    const b = breakdown || {};
    lastPostStatusBreakdown = b;
    const draft = Number(b.draft) || 0;
    const scheduled = Number(b.scheduled) || 0;
    const published = Number(b.published) || 0;
    const failed = Number(b.failed) || 0;

    loadChartScript()
      .then(function () {
        if (postChart) {
          postChart.destroy();
          postChart = null;
        }
        const ctx = canvas.getContext('2d');
        postChart = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Draft', 'Scheduled', 'Published', 'Failed'],
            datasets: [
              {
                data: [draft, scheduled, published, failed],
                backgroundColor: ['#94a3b8', '#3b82f6', '#10b981', '#ef4444'],
                borderWidth: 0,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: { color: chartLegendColor() },
              },
            },
          },
        });
      })
      .catch(function () {
        /* optional chart */
      });
  }

  function openQuickPostModal() {
    const body =
      '<div class="quick-post-field">' +
      '<label for="qpTitle">Title</label>' +
      '<input type="text" id="qpTitle" name="title" required maxlength="255">' +
      '</div>' +
      '<div class="quick-post-field">' +
      '<label for="qpContent">Content</label>' +
      '<textarea id="qpContent" name="content" rows="5" required></textarea>' +
      '<div class="char-count" id="qpCharCount">0 characters</div>' +
      '</div>' +
      '<div class="quick-post-field">' +
      '<span style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;color:var(--color-text-muted)">Platforms</span>' +
      '<div class="quick-post-platforms" style="margin-top:8px">' +
      '<label><input type="checkbox" name="qp_platform" value="instagram"> Instagram</label>' +
      '<label><input type="checkbox" name="qp_platform" value="tiktok"> TikTok</label>' +
      '<label><input type="checkbox" name="qp_platform" value="youtube"> YouTube</label>' +
      '</div></div>' +
      '<div class="quick-post-field">' +
      '<span style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;color:var(--color-text-muted)">Status</span>' +
      '<div class="quick-post-status" style="margin-top:8px">' +
      '<label><input type="radio" name="qp_status" value="draft" checked> Draft</label>' +
      '<label><input type="radio" name="qp_status" value="scheduled"> Schedule</label>' +
      '</div></div>' +
      '<div class="quick-post-field" id="qpScheduleRow" style="display:none">' +
      '<label for="qpScheduledAt">Scheduled time</label>' +
      '<input type="datetime-local" id="qpScheduledAt">' +
      '</div>';

    const footer =
      '<button type="button" class="btn btn-primary" id="quickPostSave">Save Post</button>' +
      '<button type="button" class="btn btn-ghost" id="quickPostCancel">Cancel</button>';

    window.Modal.open('New post', body, footer);

    document.getElementById('quickPostSave')?.addEventListener('click', submitQuickPost);
    document.getElementById('quickPostCancel')?.addEventListener('click', function () {
      window.Modal.close();
    });

    const ta = document.getElementById('qpContent');
    const cc = document.getElementById('qpCharCount');
    if (ta && cc) {
      ta.addEventListener('input', function () {
        cc.textContent = ta.value.length + ' characters';
      });
    }

    document.querySelectorAll('input[name="qp_status"]').forEach(function (r) {
      r.addEventListener('change', function () {
        const row = document.getElementById('qpScheduleRow');
        if (!row) return;
        const v = document.querySelector('input[name="qp_status"]:checked')?.value;
        row.style.display = v === 'scheduled' ? 'block' : 'none';
      });
    });
  }

  function submitQuickPost() {
    const titleEl = document.getElementById('qpTitle');
    const contentEl = document.getElementById('qpContent');
    const schedEl = document.getElementById('qpScheduledAt');
    const title = titleEl?.value?.trim() || '';
    const content = contentEl?.value?.trim() || '';
    if (!title || !content) {
      window.Toast.error('Title and content are required.');
      return;
    }

    const status =
      document.querySelector('input[name="qp_status"]:checked')?.value || 'draft';
    const scheduledAt = schedEl?.value?.trim() || '';

    if (status === 'scheduled' && !scheduledAt) {
      window.Toast.error('Pick a date and time for scheduled posts.');
      return;
    }

    const platforms = [];
    document.querySelectorAll('input[name="qp_platform"]:checked').forEach(function (c) {
      platforms.push(c.value);
    });

    const fd = new URLSearchParams();
    fd.append('_token', window.__CSRF__ || '');
    fd.append('title', title);
    fd.append('content', content);
    platforms.forEach(function (p) {
      fd.append('platforms[]', p);
    });
    fd.append('status', status);
    if (status === 'scheduled') {
      fd.append('scheduled_at', scheduledAt);
    }

    fetch(window.routeQuery('create_post'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
      body: fd.toString(),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        if (!j.success) {
          throw new Error(j.message || 'Could not create post');
        }
        window.Toast.success('Post created!');
        window.Modal.close();
        loadDashboard();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Could not create post');
      });
  }

  function deletePost(postId) {
    if (!postId || !window.confirm('Delete this post?')) return;
    window
      .api('delete_post', 'POST', { post_id: String(postId) })
      .then(function () {
        window.Toast.success('Post removed.');
        loadDashboard();
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Delete failed');
      });
  }

  function loadDashboard() {
    showDashboardSkeletons();
    const greet = document.getElementById('dashGreeting');
    if (greet) greet.textContent = getGreeting();

    window
      .api('dashboard_data', 'GET')
      .then(function (res) {
        const d = res.data || {};
        renderStatCards(d.stats || {});
        renderRecentPosts(d.recent_posts || []);
        renderUpcomingPosts(d.upcoming_posts || []);
        renderPlatformStatus(d.platform_status || []);
        renderPostChart(d.post_status_breakdown || {});
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Could not load dashboard');
      });
  }

  document.getElementById('quickPostBtn')?.addEventListener('click', openQuickPostModal);

  new MutationObserver(function () {
    updatePostChartTheme();
  }).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme'],
  });

  window.Dashboard = {
    loadDashboard: loadDashboard,
    getGreeting: getGreeting,
    formatFollowers: formatFollowers,
    renderStatCards: renderStatCards,
    renderRecentPosts: renderRecentPosts,
    renderUpcomingPosts: renderUpcomingPosts,
    renderPlatformStatus: renderPlatformStatus,
    renderPostChart: renderPostChart,
    openQuickPostModal: openQuickPostModal,
    submitQuickPost: submitQuickPost,
    deletePost: deletePost,
  };

  loadDashboard();
})();
