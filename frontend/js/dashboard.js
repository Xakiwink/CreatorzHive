(function () {
  let postChart = null;
  let lastPostStatusBreakdown = null;

  // Line icons matching the sidebar's icon language (frontend/components/sidebar.html) --
  // chart/users/dollar are the exact paths already used there for Analytics/User
  // Management/Deals, reused here for visual consistency instead of emoji.
  const ICONS = {
    post: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.742-.477 3 3 0 00-4.682-2.72m.94 3.198v.75c0 .414-.336.75-.75.75H5.25a.75.75 0 01-.75-.75v-.75m13.5 0a9.09 9.09 0 01-13.5 0m13.5 0a9.09 9.09 0 00-13.5 0m13.5 0v-1.35a3 3 0 00-3-3h-1.5m-7.5 4.35v-1.35a3 3 0 013-3H7.5m0 0A3 3 0 104.5 9.75 3 3 0 007.5 13.5zm9 0a3 3 0 100-6 3 3 0 000 6z" /></svg>',
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm9.75 1.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v5.625c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-5.625zm0-9.75C13.5 5.504 14.004 5 14.625 5h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V6.125z" /></svg>',
    dollar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    trendUp: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" /></svg>',
    flame: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" /></svg>',
    trophy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0a5.982 5.982 0 01-4.006 0m4.006 0a5.987 5.987 0 003-4.665v-2.61m-11 2.61a5.987 5.987 0 003 4.665m-3-4.665v-2.61a1.5 1.5 0 011.5-1.5h7.5a1.5 1.5 0 011.5 1.5v2.61m-10.5 0a3 3 0 01-3-3v-.75a1.5 1.5 0 011.5-1.5h1.5m10.5 5.25a3 3 0 003-3v-.75a1.5 1.5 0 00-1.5-1.5h-1.5" /></svg>',
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>',
  };

  const DASHBOARD_STAT_SKELETON = (
    '<div class="stat-card skeleton-block" data-skeleton>' +
    '<div class="skeleton skeleton-text" style="width:40%;height:14px;margin-bottom:12px"></div>' +
    '<div class="skeleton skeleton-text" style="width:55%;height:28px"></div>' +
    '<div class="skeleton skeleton-text" style="width:30%;height:12px;margin-top:10px"></div>' +
    '</div>'
  ).repeat(8);

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

  const ACHIEVEMENTS_SKELETON = Array.from({ length: 4 }, function () {
    return (
      '<div class="achievement-badge">' +
      '<div class="skeleton skeleton-circle achievement-icon"></div>' +
      '<div class="achievement-body">' +
      '<div class="skeleton skeleton-text" style="width:70%;height:13px;margin-bottom:6px"></div>' +
      '<div class="skeleton skeleton-text" style="width:50%;height:11px"></div>' +
      '</div></div>'
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
    const ach = document.getElementById('achievementsMount');
    if (ach) ach.innerHTML = '<div class="achievements-grid">' + ACHIEVEMENTS_SKELETON + '</div>';
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

  function formatStreak(weeks) {
    const w = Number(weeks) || 0;
    if (w <= 0) return '—';
    return w + (w === 1 ? ' week' : ' weeks');
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

  function growthScoreAccentClass(score) {
    const s = Number(score) || 0;
    if (s < 40) return 'stat-card--score-low';
    if (s < 70) return 'stat-card--score-mid';
    return 'stat-card--score-high';
  }

  function renderStatCards(stats, scores) {
    const grid = document.getElementById('statGrid');
    if (!grid) return;
    const rev = Utils.formatCurrency(stats.total_revenue || 0, 'TZS');
    const eng = (Number(stats.avg_engagement_rate) || 0).toFixed(1) + '%';
    const sc = scores || {};
    const scoreHtml = sc.available
      ? Utils.escapeHtml(String(Math.round(Number(sc.creator_score) || 0))) + '<span class="stat-value-suffix">/100</span>'
      : '—';
    const scoreSub = sc.available
      ? ''
      : '<span class="stat-trend">Not enough data yet</span>';
    grid.innerHTML =
      '<div class="stat-card stat-card--accent-posts fade-in">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.post + '</div>' +
      '<div class="stat-label">Total posts</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(stats.total_posts ?? 0)) +
      '</div>' +
      trendLine('posts', stats) +
      '</div>' +
      '<div class="stat-card stat-card--accent-published fade-in">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.check + '</div>' +
      '<div class="stat-label">Published</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(stats.published_posts ?? 0)) +
      '</div>' +
      trendLine('published', stats) +
      '</div>' +
      '<div class="stat-card stat-card--accent-scheduled fade-in">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.clock + '</div>' +
      '<div class="stat-label">Scheduled</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(stats.scheduled_posts ?? 0)) +
      '</div>' +
      trendLine('scheduled', stats) +
      '</div>' +
      '<div class="stat-card stat-card--accent-followers fade-in">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.users + '</div>' +
      '<div class="stat-label">Followers</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(formatFollowers(stats.total_followers)) +
      '</div>' +
      trendLine('followers', stats) +
      '</div>' +
      '<div class="stat-card fade-in ' +
      engagementAccentClass(stats.avg_engagement_rate) +
      '">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.chart + '</div>' +
      '<div class="stat-label">Avg engagement</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(eng) +
      '</div>' +
      '</div>' +
      '<div class="stat-card stat-card--revenue fade-in">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.dollar + '</div>' +
      '<div class="stat-label">Revenue</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(rev) +
      '</div>' +
      '</div>' +
      '<div class="stat-card fade-in ' +
      growthScoreAccentClass(sc.creator_score) +
      '">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.trendUp + '</div>' +
      '<div class="stat-label">Growth score</div>' +
      '<div class="stat-value">' +
      scoreHtml +
      '</div>' +
      scoreSub +
      '</div>' +
      '<div class="stat-card stat-card--streak fade-in">' +
      '<div class="stat-icon" aria-hidden="true">' + ICONS.flame + '</div>' +
      '<div class="stat-label">Posting streak</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(formatStreak(stats.posting_streak_weeks)) +
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
        '<div class="empty-icon" aria-hidden="true">' + ICONS.post + '</div>' +
        '<p>Create your first post to see it here.</p>' +
        '<a class="btn btn-primary" href="' +
        Utils.escapeHtml(window.routeQuery('planner')) +
        '">Create your first post</a>' +
        '</div>';
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
        Utils.escapeHtml(window.routeQuery('planner', { id: String(p.id) })) +
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

  function normalizeMediaUrl(url) {
    const value = String(url || '').trim();
    if (value === '') {
      return '';
    }
    if (/^(https?:)?\/\//.test(value) || value.charAt(0) === '/') {
      return value;
    }
    const base = typeof window.__BASE_PATH__ === 'string' ? String(window.__BASE_PATH__).replace(/\/$/, '') : '';
    return (base ? base + '/' : '/') + value.replace(/^\/+/, '');
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
      const thumb = normalizeMediaUrl(p.cover_thumb || p.cover_url || '');
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

  function platformDotClass(conn, health) {
    if (!conn) return '';
    if (health === 'steady') return ' connected platform-dot--steady';
    if (health === 'at_risk') return ' connected platform-dot--at-risk';
    return ' connected';
  }

  function platformHealthNote(conn, health) {
    if (!conn) return '';
    if (health === 'steady') return '<span class="platform-health-note platform-health-note--steady">Steady</span>';
    if (health === 'at_risk')
      return '<span class="platform-health-note platform-health-note--at-risk">Needs attention</span>';
    return '';
  }

  function renderPlatformStatus(platforms) {
    const mount = document.getElementById('platformStatusMount');
    if (!mount) return;
    let html = '';
    (platforms || []).forEach((row) => {
      const p = row.platform;
      const conn = row.connected;
      const health = row.health || 'unknown';
      html +=
        '<div class="platform-row">' +
        '<div class="platform-meta">' +
        '<span class="platform-dot' +
        platformDotClass(conn, health) +
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
          ? platformHealthNote(conn, health)
          : '<a class="btn btn-sm btn-secondary" href="' +
            Utils.escapeHtml(window.routeQuery('settings-integrations' )) +
            '">Connect</a>') +
        '</div>';
    });
    mount.innerHTML = html;
  }

  function renderAchievements(data) {
    const mount = document.getElementById('achievementsMount');
    if (!mount) return;
    const badges = (data && data.badges) || [];
    if (badges.length === 0) {
      mount.innerHTML = '<p class="empty-state-inline">Keep going — badges will show up here.</p>';
      return;
    }

    let html = '<div class="achievements-grid">';
    badges.forEach((b) => {
      const icon = ICONS[b.icon] || ICONS.trophy;
      const locked = !b.unlocked;
      let progressHtml = '';
      if (locked && b.progress) {
        const cur = Number(b.progress.current) || 0;
        const target = Number(b.progress.target) || 1;
        const pct = Math.max(0, Math.min(100, Math.round((cur / target) * 100)));
        progressHtml =
          '<div class="achievement-progress">' +
          '<div class="achievement-progress-bar" style="width:' + pct + '%"></div>' +
          '</div>';
      }
      html +=
        '<div class="achievement-badge' + (locked ? ' is-locked' : '') + '" title="' +
        Utils.escapeHtml(b.description || '') + '">' +
        '<div class="achievement-icon" aria-hidden="true">' + icon + '</div>' +
        '<div class="achievement-body">' +
        '<div class="achievement-label">' + Utils.escapeHtml(b.label || '') + '</div>' +
        progressHtml +
        '</div></div>';
    });
    html += '</div>';
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
        renderStatCards(d.stats || {}, d.scores || {});
        renderRecentPosts(d.recent_posts || []);
        renderUpcomingPosts(d.upcoming_posts || []);
        renderPlatformStatus(d.platform_status || []);
        renderPostChart(d.post_status_breakdown || {});
        renderAchievements(d.achievements || {});
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Could not load dashboard');
      });
  }

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
    renderAchievements: renderAchievements,
    renderPostChart: renderPostChart,
    deletePost: deletePost,
  };

  loadDashboard();
})();
