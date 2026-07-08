(function () {
  /** @type {Record<string, Chart>} */
  const charts = {};
  /** @type {Chart[]} */
  let sparkCharts = [];

  const PLATFORM_COLORS = {
    instagram: '#E4405F',
    tiktok: '#000000',
    youtube: '#FF0000',
    twitter: '#1DA1F2',
    default: '#6366f1',
  };

  let currentPeriod = '30d';
  let customStart = '';
  let customEnd = '';

  function chartDefaults() {
    const grid =
      getComputedStyle(document.documentElement).getPropertyValue('--color-border').trim() ||
      '#e2e8f0';
    const tick =
      getComputedStyle(document.documentElement).getPropertyValue('--color-text-muted').trim() ||
      '#64748b';
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(15, 23, 42, 0.92)',
          titleColor: '#f8fafc',
          bodyColor: '#e2e8f0',
          padding: 10,
          cornerRadius: 8,
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: tick, maxRotation: 0, autoSkip: true },
        },
        y: {
          grid: { color: grid },
          ticks: { color: tick },
        },
      },
    };
  }

  function platformColor(slug) {
    const k = String(slug || '').toLowerCase();
    return PLATFORM_COLORS[k] || PLATFORM_COLORS.default;
  }

  function compactNumber(n) {
    const x = Number(n) || 0;
    if (x >= 1e6) return (x / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (x >= 1e3) return (x / 1e3).toFixed(1).replace(/\.0$/, '') + 'k';
    return String(Math.round(x));
  }

  function trendHtml(pct, options) {
    const p = Number(pct) || 0;
    const opts = options || {};
    if (opts.abs !== undefined) {
      const g = Number(opts.abs) || 0;
      const cls = g > 0 ? 'positive' : g < 0 ? 'negative' : '';
      const arrow = g > 0 ? '↑' : g < 0 ? '↓' : '→';
      const mid =
        arrow +
        ' ' +
        (g >= 0 ? '+' : '') +
        compactNumber(Math.abs(g)) +
        ' (' +
        (p >= 0 ? '+' : '') +
        p.toFixed(1) +
        '%)';
      return '<div class="stat-trend ' + cls + '">' + mid + '</div>';
    }
    const cls = p > 0 ? 'positive' : p < 0 ? 'negative' : '';
    const arrow = p > 0 ? '↑' : p < 0 ? '↓' : '→';
    const mid = arrow + ' ' + (p >= 0 ? '+' : '') + p.toFixed(1) + '% vs prev.';
    return '<div class="stat-trend ' + cls + '">' + mid + '</div>';
  }

  function microDeltaRow(metricDeltas, isDecimalMetric) {
    if (!metricDeltas) return '';
    const windows = [
      { key: 'today', label: 'Today' },
      { key: 'week', label: 'Week' },
      { key: 'month', label: 'Month' },
    ];
    const cells = windows
      .map(function (w) {
        const win = metricDeltas[w.key];
        if (!win) return '';
        const d = Number(win.delta) || 0;
        const cls = d > 0 ? 'positive' : d < 0 ? 'negative' : '';
        const arrow = d > 0 ? '↑' : d < 0 ? '↓' : '→';
        const sign = d >= 0 ? '+' : '';
        const formatted = isDecimalMetric ? d.toFixed(1) : compactNumber(d);
        return (
          '<span class="kpi-delta-cell ' +
          cls +
          '"><span class="kpi-delta-label">' +
          w.label +
          '</span>' +
          arrow +
          ' ' +
          sign +
          formatted +
          '</span>'
        );
      })
      .join('');
    return cells ? '<div class="kpi-delta-row">' + cells + '</div>' : '';
  }

  function destroySparklines() {
    sparkCharts.forEach(function (c) {
      try {
        c.destroy();
      } catch (e) {
        /* ignore */
      }
    });
    sparkCharts = [];
  }

  function destroyChart(key) {
    if (charts[key]) {
      try {
        charts[key].destroy();
      } catch (e) {
        /* ignore */
      }
      delete charts[key];
    }
  }

  function destroyAllMainCharts() {
    Object.keys(charts).forEach(destroyChart);
  }

  /** Simple hex fill */
  function sparkFill(hex) {
    if (hex.charAt(0) === '#') {
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return 'rgba(' + r + ',' + g + ',' + b + ',0.12)';
    }
    return 'rgba(99,102,241,0.12)';
  }

  function renderSparklineFixed(canvas, values, color) {
    if (!canvas || !values || values.length === 0) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const fill = sparkFill(color);
    const c = new Chart(ctx, {
      type: 'line',
      data: {
        labels: values.map(function (_, i) {
          return String(i);
        }),
        datasets: [
          {
            data: values,
            borderColor: color,
            backgroundColor: fill,
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.35,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: {
          x: { display: false },
          y: { display: false },
        },
      },
    });
    sparkCharts.push(c);
  }

  function renderSummaryCards(summary, sparklines, growthDeltas) {
    const grid = document.getElementById('analyticsKpiGrid');
    if (!grid) return;
    const sl = sparklines || {};
    const gd = (growthDeltas && growthDeltas.available && growthDeltas.metrics) || {};
    const primary =
      getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() ||
      '#6366f1';

    const cards = [
      {
        icon: '👥',
        label: 'Total followers',
        value: compactNumber(summary.total_followers),
        trend: trendHtml(summary.follower_growth_pct, { abs: summary.follower_growth }),
        accent: 'stat-card--accent-followers',
        spark: sl.followers || [],
        color: primary,
        deltas: gd.followers,
      },
      {
        icon: '👁️',
        label: 'Impressions',
        value: compactNumber(summary.total_impressions),
        trend: trendHtml(summary.impressions_change_pct),
        spark: sl.impressions || [],
        color: '#0ea5e9',
        deltas: gd.impressions,
      },
      {
        icon: '📡',
        label: 'Reach',
        value: compactNumber(summary.total_reach),
        trend: trendHtml(summary.reach_change_pct),
        spark: sl.reach || [],
        color: '#8b5cf6',
        deltas: gd.reach,
      },
      {
        icon: '📊',
        label: 'Avg engagement',
        value: (Number(summary.avg_engagement_rate) || 0).toFixed(1) + '%',
        trend: trendHtml(summary.avg_engagement_change_pct),
        accent: engagementAccent(summary.avg_engagement_rate),
        spark: sl.avg_engagement_rate || [],
        color: '#10b981',
        deltas: gd.engagement_rate,
      },
      {
        icon: '✅',
        label: 'Posts published',
        value: String(summary.posts_published ?? 0),
        trend: trendHtml(summary.posts_published_change_pct),
        spark: sl.posts_published || [],
        color: '#f59e0b',
      },
    ];

    let html = '';
    cards.forEach(function (c, i) {
      const ac = c.accent ? ' ' + c.accent : '';
      html +=
        '<div class="stat-card analytics-stat-card fade-in' +
        ac +
        '">' +
        '<div class="stat-icon" aria-hidden="true">' +
        c.icon +
        '</div>' +
        '<div class="stat-label">' +
        Utils.escapeHtml(c.label) +
        '</div>' +
        '<div class="stat-value">' +
        Utils.escapeHtml(c.value) +
        '</div>' +
        c.trend +
        microDeltaRow(c.deltas, c.label === 'Avg engagement') +
        '<div class="stat-sparkline"><canvas id="spark-' +
        i +
        '" width="120" height="40"></canvas></div>' +
        '</div>';
    });
    grid.innerHTML = html;

    destroySparklines();
    cards.forEach(function (c, i) {
      const cv = document.getElementById('spark-' + i);
      renderSparklineFixed(cv, c.spark, c.color);
    });
  }

  function engagementAccent(rate) {
    const r = Number(rate) || 0;
    if (r < 2) return 'stat-card--engagement-low';
    if (r <= 5) return 'stat-card--engagement-mid';
    return 'stat-card--engagement-high';
  }

  function renderFollowerChart(rows) {
    destroyChart('followers');
    const el = document.getElementById('chartFollowers');
    if (!el || !rows || rows.length === 0) return;
    const primary =
      getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() ||
      '#6366f1';
    const labels = rows.map(function (r) {
      return Utils.formatDate(r.date, 'short');
    });
    const data = rows.map(function (r) {
      return Number(r.followers) || 0;
    });
    charts.followers = new Chart(el.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Followers',
            data: data,
            borderColor: primary,
            backgroundColor: sparkFill(primary),
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
          },
        ],
      },
      options: chartDefaults(),
    });
  }

  function renderEngagementChart(rows) {
    destroyChart('engagement');
    const el = document.getElementById('chartEngagement');
    if (!el || !rows || rows.length === 0) return;
    const labels = rows.map(function (r) {
      return Utils.formatDate(r.date, 'short');
    });
    const data = rows.map(function (r) {
      return Number(r.rate) || 0;
    });
    charts.engagement = new Chart(el.getContext('2d'), {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Engagement %',
            data: data,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 0,
          },
        ],
      },
      options: chartDefaults(),
    });
  }

  function renderPostingChart(rows) {
    destroyChart('posting');
    const el = document.getElementById('chartPosting');
    if (!el) return;
    const labels = (rows || []).map(function (r) {
      return String(r.week || '');
    });
    const data = (rows || []).map(function (r) {
      return Number(r.count) || 0;
    });
    charts.posting = new Chart(el.getContext('2d'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Posts',
            data: data,
            backgroundColor: 'rgba(99, 102, 241, 0.55)',
            borderRadius: 6,
            borderSkipped: false,
          },
        ],
      },
      options: chartDefaults(),
    });
  }

  function renderPlatformChart(rows) {
    destroyChart('platforms');
    const el = document.getElementById('chartPlatforms');
    const legend = document.getElementById('platformLegend');
    if (!el) return;
    if (!rows || rows.length === 0) {
      if (legend) legend.innerHTML = '';
      return;
    }
    const labels = rows.map(function (r) {
      return String(r.platform || '').charAt(0).toUpperCase() + String(r.platform || '').slice(1);
    });
    const data = rows.map(function (r) {
      return Number(r.followers) || 0;
    });
    const colors = rows.map(function (r) {
      return platformColor(r.platform);
    });
    const defs = chartDefaults();
    charts.platforms = new Chart(el.getContext('2d'), {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Followers',
            data: data,
            backgroundColor: colors,
            borderRadius: 6,
            borderSkipped: false,
          },
        ],
      },
      options: Object.assign({}, defs, {
        indexAxis: 'y',
        scales: {
          x: {
            grid: { color: defs.scales.y.grid.color },
            ticks: { color: defs.scales.x.ticks.color },
          },
          y: {
            grid: { display: false },
            ticks: { color: defs.scales.x.ticks.color },
          },
        },
      }),
    });

    if (legend) {
      let h = '';
      rows.forEach(function (r) {
        const name = String(r.platform || '');
        const nice = name.charAt(0).toUpperCase() + name.slice(1);
        const f = compactNumber(r.followers);
        const er = (Number(r.engagement_rate) || 0).toFixed(1);
        h +=
          '<div class="legend-item"><span class="legend-dot" style="background:' +
          platformColor(name) +
          '"></span><span>' +
          Utils.escapeHtml(nice) +
          ' · ' +
          Utils.escapeHtml(f) +
          ' followers · ' +
          Utils.escapeHtml(er) +
          '% ER</span></div>';
      });
      legend.innerHTML = h;
    }
  }

  function renderTopPosts(posts) {
    const body = document.getElementById('topPostsBody');
    if (!body) return;
    if (!posts || posts.length === 0) {
      body.innerHTML =
        '<tr><td colspan="7" class="text-muted">No published posts in this filter.</td></tr>';
      return;
    }
    let html = '';
    posts.forEach(function (p) {
      const plat = String(p.platform || 'instagram');
      const thumb = p.cover_thumb
        ? '<img class="post-thumb" src="' +
          Utils.escapeHtml(p.cover_thumb) +
          '" alt="" loading="lazy" />'
        : '<div class="post-thumb post-thumb--placeholder" aria-hidden="true">📝</div>';
      const title = Utils.escapeHtml(Utils.truncate(p.title || 'Untitled', 48));
      const nicePlat = plat.charAt(0).toUpperCase() + plat.slice(1);
      html +=
        '<tr>' +
        '<td>' +
        thumb +
        '</td>' +
        '<td>' +
        title +
        '</td>' +
        '<td><span class="platform-badge platform-badge--' +
        Utils.escapeHtml(plat) +
        '">' +
        Utils.escapeHtml(nicePlat) +
        '</span></td>' +
        '<td class="num">' +
        Utils.escapeHtml(String(p.likes ?? 0)) +
        '</td>' +
        '<td class="num">' +
        Utils.escapeHtml(String(p.comments ?? 0)) +
        '</td>' +
        '<td class="num">' +
        Utils.escapeHtml(String(p.reach ?? 0)) +
        '</td>' +
        '<td class="num">' +
        Utils.escapeHtml(String(p.engagement_rate ?? 0)) +
        '%</td>' +
        '</tr>';
    });
    body.innerHTML = html;
  }

  const SEVERITY_ICON = { positive: '↑', negative: '↓', neutral: '•' };

  function renderInsights(insights) {
    const mount = document.getElementById('insightsMount');
    if (!mount) return;
    if (!insights || insights.length === 0) {
      mount.innerHTML =
        '<p class="text-muted" style="margin:0;font-size:var(--text-sm)">Not enough history yet to surface insights. Check back after a few more analytics refreshes.</p>';
      return;
    }
    let html = '';
    insights.forEach(function (ins) {
      const sev = ['positive', 'negative', 'neutral'].indexOf(ins.severity) !== -1 ? ins.severity : 'neutral';
      html +=
        '<div class="insight-item insight-item--' +
        sev +
        '"><span class="insight-icon" aria-hidden="true">' +
        (SEVERITY_ICON[sev] || '•') +
        '</span><span class="insight-message">' +
        Utils.escapeHtml(ins.message || '') +
        '</span></div>';
    });
    mount.innerHTML = html;
  }

  const PREDICTION_METRIC_LABEL = { followers: 'Followers', engagement_rate: 'Engagement rate' };
  const PREDICTION_HORIZON_LABEL = { next_week: 'next week', next_month: 'next month' };

  function formatPredictionValue(metric, value) {
    return metric === 'engagement_rate' ? Number(value).toFixed(1) + '%' : compactNumber(value);
  }

  function renderPredictions(predictions) {
    const mount = document.getElementById('predictionsMount');
    if (!mount) return;
    if (!predictions || predictions.length === 0) {
      mount.innerHTML =
        '<p class="text-muted" style="margin:0;font-size:var(--text-sm)">Not enough historical snapshots yet to predict trends.</p>';
      return;
    }
    let html = '';
    predictions.forEach(function (p) {
      const metricLabel = PREDICTION_METRIC_LABEL[p.metric] || Utils.escapeHtml(p.metric);
      const horizonLabel = PREDICTION_HORIZON_LABEL[p.horizon] || Utils.escapeHtml(p.horizon);
      html +=
        '<div class="prediction-card">' +
        '<div class="prediction-label">' +
        metricLabel +
        ' — ' +
        horizonLabel +
        '</div>' +
        '<div class="prediction-value">~' +
        formatPredictionValue(p.metric, p.predicted_value) +
        '</div>' +
        '<div class="prediction-meta">' +
        (p.method === 'linear_regression' ? 'Linear trend' : 'Moving average') +
        ' · ' +
        p.based_on_snapshots +
        ' snapshots</div>' +
        '</div>';
    });
    mount.innerHTML = html;
  }

  function fetchAnalytics(query) {
    return fetch(window.routeQuery('analytics_data', query), {
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function setViewState(hasData) {
    const main = document.getElementById('analyticsMain');
    const empty = document.getElementById('analyticsEmpty');
    if (main) main.classList.toggle('hidden', !hasData);
    if (empty) empty.classList.toggle('hidden', hasData);
  }

  function loadAnalytics() {
    const platEl = document.getElementById('platformFilter');
    const platform = platEl ? String(platEl.value || '') : '';
    const q = { period: currentPeriod };
    if (currentPeriod === 'custom' && customStart && customEnd) {
      q.start_date = customStart;
      q.end_date = customEnd;
    }
    if (platform) q.platform = platform;

    fetchAnalytics(q)
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Failed to load');
        const d = res.data || {};
        const has = !!d.has_data;
        setViewState(has);
        if (!has) {
          destroyAllMainCharts();
          destroySparklines();
          return;
        }
        renderSummaryCards(d.summary || {}, d.sparklines || {}, d.growth_deltas || {});
        renderFollowerChart(d.follower_trend || []);
        renderEngagementChart(d.engagement_trend || []);
        renderPostingChart(d.posting_frequency || []);
        renderPlatformChart(d.platform_breakdown || []);
        renderTopPosts(d.top_posts || []);
        renderInsights(d.insights || []);
        renderPredictions(d.predictions || []);
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Could not load analytics');
      });
  }

  function initPeriodTabs() {
    document.querySelectorAll('.period-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const p = btn.getAttribute('data-period');
        if (!p) return;
        currentPeriod = p;
        document.querySelectorAll('.period-tab').forEach(function (b) {
          b.classList.toggle('active', b === btn);
          b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        const custom = document.getElementById('customRange');
        if (custom) custom.classList.toggle('hidden', p !== 'custom');
        if (p !== 'custom') loadAnalytics();
      });
    });
  }

  function initCustomRange() {
    const apply = document.getElementById('applyCustomRange');
    if (apply) {
      apply.addEventListener('click', function () {
        const s = document.getElementById('startDate');
        const e = document.getElementById('endDate');
        customStart = s && s.value ? s.value : '';
        customEnd = e && e.value ? e.value : '';
        if (!customStart || !customEnd) {
          window.Toast?.warning('Pick start and end dates');
          return;
        }
        loadAnalytics();
      });
    }
  }

  function initPlatformFilter() {
    const sel = document.getElementById('platformFilter');
    if (sel) sel.addEventListener('change', loadAnalytics);
  }

  function initSeed() {
    const btn = document.getElementById('analyticsSeedBtn');
    if (!btn) return;
    const env = String(window.__APP_ENV__ || '');
    if (env === 'development') btn.classList.remove('hidden');
    btn.addEventListener('click', function () {
      window
        .api('seed_analytics', 'POST', {})
        .then(function () {
          window.Toast?.success('Demo analytics loaded');
          loadAnalytics();
        })
        .catch(function (e) {
          window.Toast?.error(e.message || 'Seed failed');
        });
    });
  }

  function boot() {
    if (typeof Chart === 'undefined') {
      window.Toast?.error('Chart library failed to load');
      return;
    }
    initPeriodTabs();
    initCustomRange();
    initPlatformFilter();
    initSeed();
    loadAnalytics();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
