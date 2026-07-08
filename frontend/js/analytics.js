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

  // Same line icons as dashboard.js (frontend/js/dashboard.js ICONS) -- kept in sync
  // for icon consistency instead of emoji. users/chart reuse the exact sidebar paths.
  const ICONS = {
    post: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.742-.477 3 3 0 00-4.682-2.72m.94 3.198v.75c0 .414-.336.75-.75.75H5.25a.75.75 0 01-.75-.75v-.75m13.5 0a9.09 9.09 0 01-13.5 0m13.5 0a9.09 9.09 0 00-13.5 0m13.5 0v-1.35a3 3 0 00-3-3h-1.5m-7.5 4.35v-1.35a3 3 0 013-3H7.5m0 0A3 3 0 104.5 9.75 3 3 0 007.5 13.5zm9 0a3 3 0 100-6 3 3 0 000 6z" /></svg>',
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm9.75 1.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v5.625c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-5.625zm0-9.75C13.5 5.504 14.004 5 14.625 5h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V6.125z" /></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    signal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>',
  };

  let currentPeriod = '30d';
  let currentSort = 'top';
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

  function hexToRgba(hex, alpha) {
    const h = String(hex || '').replace('#', '');
    if (h.length !== 6) return 'rgba(99,102,241,' + alpha + ')';
    const r = parseInt(h.slice(0, 2), 16);
    const g = parseInt(h.slice(2, 4), 16);
    const b = parseInt(h.slice(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
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
    return hexToRgba(hex, 0.12);
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
        icon: ICONS.users,
        label: 'Total followers',
        value: compactNumber(summary.total_followers),
        trend: trendHtml(summary.follower_growth_pct, { abs: summary.follower_growth }),
        accent: 'stat-card--accent-followers',
        spark: sl.followers || [],
        color: primary,
        deltas: gd.followers,
      },
      {
        icon: ICONS.eye,
        label: 'Impressions',
        value: compactNumber(summary.total_impressions),
        trend: trendHtml(summary.impressions_change_pct),
        spark: sl.impressions || [],
        color: '#0ea5e9',
        deltas: gd.impressions,
      },
      {
        icon: ICONS.signal,
        label: 'Reach',
        value: compactNumber(summary.total_reach),
        trend: trendHtml(summary.reach_change_pct),
        spark: sl.reach || [],
        color: '#8b5cf6',
        deltas: gd.reach,
      },
      {
        icon: ICONS.chart,
        label: 'Avg engagement',
        value: (Number(summary.avg_engagement_rate) || 0).toFixed(1) + '%',
        trend: trendHtml(summary.avg_engagement_change_pct),
        accent: engagementAccent(summary.avg_engagement_rate),
        spark: sl.avg_engagement_rate || [],
        color: '#10b981',
        deltas: gd.engagement_rate,
      },
      {
        icon: ICONS.check,
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
      // Cards with an accent class (followers, avg engagement) get their icon
      // tint from CSS -- avg engagement's low/mid/high color is meaningful and
      // must not be flattened to its sparkline color here.
      const iconStyle = c.accent
        ? ''
        : ' style="background:' + hexToRgba(c.color, 0.16) + ';color:' + c.color + '"';
      html +=
        '<div class="stat-card analytics-stat-card fade-in' +
        ac +
        '">' +
        '<div class="stat-icon" aria-hidden="true"' +
        iconStyle +
        '>' +
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

  const CONTENT_STATE_LABEL = {
    pending: 'Insights pending',
    unavailable: 'Insights unavailable',
  };

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
      const state = p.state || 'ranked';
      const thumb = p.cover_thumb
        ? '<img class="post-thumb" src="' +
          Utils.escapeHtml(p.cover_thumb) +
          '" alt="" loading="lazy" />'
        : '<div class="post-thumb post-thumb--placeholder" aria-hidden="true">' + ICONS.post + '</div>';
      const title = Utils.escapeHtml(Utils.truncate(p.title || 'Untitled', 48));
      const nicePlat = plat.charAt(0).toUpperCase() + plat.slice(1);
      const metricsHtml =
        state === 'ranked'
          ? '<td class="num">' +
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
            '%</td>'
          : '<td class="text-muted" colspan="4">' +
            Utils.escapeHtml(CONTENT_STATE_LABEL[state] || 'Insights unavailable') +
            '</td>';
      html +=
        '<tr' +
        (state !== 'ranked' ? ' class="content-row--' + state + '"' : '') +
        '>' +
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
        metricsHtml +
        '</tr>';
    });
    body.innerHTML = html;
  }

  function renderPlatformComparison(breakdown) {
    const mount = document.getElementById('platformComparisonMount');
    if (!mount) return;
    if (!breakdown || breakdown.length === 0) {
      mount.innerHTML =
        '<p class="text-muted" style="margin:0;font-size:var(--text-sm)">Connect more than one platform to compare performance.</p>';
      return;
    }

    let bestSlug = null;
    let worstSlug = null;
    let bestRate = -Infinity;
    let worstRate = Infinity;
    breakdown.forEach(function (row) {
      const rate = Number(row.engagement_rate) || 0;
      if (rate > bestRate) {
        bestRate = rate;
        bestSlug = row.platform;
      }
      if (rate < worstRate) {
        worstRate = rate;
        worstSlug = row.platform;
      }
    });

    let html = '<div class="table-wrapper"><table class="table platform-comparison-table">' +
      '<thead><tr><th>Platform</th><th class="num">Followers</th><th class="num">Reach</th>' +
      '<th class="num">Eng. rate</th><th></th></tr></thead><tbody>';
    breakdown.forEach(function (row) {
      const plat = String(row.platform || '');
      const isBest = breakdown.length > 1 && plat === bestSlug;
      const isWorst = breakdown.length > 1 && plat === worstSlug;
      html +=
        '<tr>' +
        '<td><span class="platform-badge platform-badge--' +
        Utils.escapeHtml(plat) +
        '">' +
        Utils.escapeHtml(platformLabel(plat)) +
        '</span></td>' +
        '<td class="num">' +
        compactNumber(row.followers) +
        '</td>' +
        '<td class="num">' +
        compactNumber(row.reach) +
        '</td>' +
        '<td class="num">' +
        (Number(row.engagement_rate) || 0).toFixed(1) +
        '%</td>' +
        '<td>' +
        (isBest ? '<span class="platform-callout platform-callout--best">Best</span>' : '') +
        (isWorst ? '<span class="platform-callout platform-callout--worst">Needs focus</span>' : '') +
        '</td>' +
        '</tr>';
    });
    html += '</tbody></table></div>';
    mount.innerHTML = html;
  }

  function platformLabel(p) {
    const labels = { instagram: 'Instagram', tiktok: 'TikTok', youtube: 'YouTube', twitter: 'Twitter' };
    return labels[p] || (p ? p.charAt(0).toUpperCase() + p.slice(1) : 'Unknown');
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
    const q = { period: currentPeriod, sort: currentSort };
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
        renderPlatformComparison(d.platform_breakdown || []);
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

  function initSortButtons() {
    document.querySelectorAll('.content-sort-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const s = btn.getAttribute('data-sort');
        if (!s) return;
        currentSort = s;
        document.querySelectorAll('.content-sort-tab').forEach(function (b) {
          b.classList.toggle('active', b === btn);
        });
        loadAnalytics();
      });
    });
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
    initSortButtons();
    initSeed();
    loadAnalytics();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
