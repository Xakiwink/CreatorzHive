(function () {
  const STATUSES = [
    'lead',
    'negotiation',
    'contract',
    'active',
    'completed',
    'cancelled',
  ];

  const STATUS_LABEL = {
    lead: 'Lead',
    negotiation: 'Negotiation',
    contract: 'Contract',
    active: 'Active',
    completed: 'Completed',
    cancelled: 'Cancelled',
  };

  const TYPE_LABEL = {
    sponsored_post: 'Sponsored post',
    affiliate: 'Affiliate',
    ambassador: 'Ambassador',
    gifted: 'Gifted',
    other: 'Other',
  };

  let lastKanban = null;
  let draggedCard = null;
  let draggedFromCol = null;

  function assetPath(rel) {
    const base = typeof window.__BASE_PATH__ === 'string' ? window.__BASE_PATH__ : '';
    rel = String(rel || '').replace(/^\//, '');
    return (base ? base + '/' : '/') + rel;
  }

  function formatCurrency(amount, currency) {
    const c = String(currency || 'TZS').toUpperCase();
    if (c === 'TZS') {
      return Utils.formatCurrency(amount, 'TZS');
    }
    try {
      return new Intl.NumberFormat(undefined, { style: 'currency', currency: c }).format(Number(amount || 0));
    } catch (e) {
      return c + ' ' + Number(amount || 0).toFixed(2);
    }
  }

  function isOverdue(deadline) {
    if (!deadline) return false;
    const d = new Date(deadline + 'T12:00:00');
    if (Number.isNaN(d.getTime())) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return d < today;
  }

  function isDueSoon(deadline) {
    if (!deadline) return false;
    const d = new Date(deadline + 'T12:00:00');
    if (Number.isNaN(d.getTime())) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = (d - today) / 86400000;
    return diff >= 0 && diff <= 7;
  }

  function getDeadlineClass(deadline) {
    if (!deadline) return 'deal-deadline deal-deadline--ok';
    if (isOverdue(deadline)) return 'deal-deadline deal-deadline--overdue';
    if (isDueSoon(deadline)) return 'deal-deadline deal-deadline--soon';
    return 'deal-deadline deal-deadline--ok';
  }

  function deadlineText(deadline) {
    if (!deadline) return 'No deadline';
    return 'Due ' + Utils.formatDate(deadline, 'medium');
  }

  function fetchJson(route, query) {
    return fetch(window.routeQuery(route, query || {}), {
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function renderRevenueSummary(summary) {
    const el = document.getElementById('revenueSummary');
    if (!el || !summary) return;
    const cur = summary.currency || 'TZS';
    const earned = formatCurrency(summary.earned_revenue, cur);
    const pipe = formatCurrency(summary.pipeline_revenue, cur);
    el.innerHTML =
      '<div class="stat-card fade-in">' +
      '<div class="stat-label">Earned revenue</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(earned) +
      '</div>' +
      '<span class="text-success">' +
      Utils.escapeHtml(String(summary.completed_deals || 0)) +
      ' deals completed</span>' +
      '</div>' +
      '<div class="stat-card fade-in">' +
      '<div class="stat-label">Pipeline value</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(pipe) +
      '</div>' +
      '<span class="text-info">' +
      Utils.escapeHtml(String(summary.active_deals || 0)) +
      ' active deals</span>' +
      '</div>' +
      '<div class="stat-card fade-in">' +
      '<div class="stat-label">Total deals</div>' +
      '<div class="stat-value">' +
      Utils.escapeHtml(String(summary.total_deals || 0)) +
      '</div>' +
      '</div>';
  }

  function renderDealCard(deal) {
    const card = document.createElement('div');
    card.className = 'deal-card';
    card.draggable = true;
    card.dataset.dealId = String(deal.id);
    card.dataset.status = String(deal.status || 'lead');

    const logo = deal.brand_logo_url
      ? '<img class="deal-brand-avatar" src="' +
        Utils.escapeHtml(deal.brand_logo_url) +
        '" alt="" loading="lazy" />'
      : (function () {
          const name = String(deal.brand_name || '?');
          const ch = name.trim().charAt(0).toUpperCase() || '?';
          const hue = (deal.id * 47) % 360;
          return (
            '<div class="deal-brand-avatar deal-brand-initial" style="background:hsl(' +
            hue +
            ',55%,42%)">' +
            Utils.escapeHtml(ch) +
            '</div>'
          );
        })();

    const typeKey = String(deal.deal_type || 'other');
    const typeLbl = TYPE_LABEL[typeKey] || typeKey;

    card.innerHTML =
      '<div class="deal-card-top">' +
      logo +
      '<div class="deal-card-meta">' +
      '<div class="deal-card-brand">' +
      Utils.escapeHtml(String(deal.brand_name || '')) +
      '</div>' +
      '<div class="deal-card-title">' +
      Utils.escapeHtml(Utils.truncate(String(deal.title || ''), 52)) +
      '</div>' +
      '</div></div>' +
      '<div class="deal-card-badges">' +
      '<span class="deal-badge deal-badge--money">' +
      Utils.escapeHtml(formatCurrency(deal.amount, deal.currency)) +
      '</span>' +
      '<span class="deal-badge">' +
      Utils.escapeHtml(typeLbl) +
      '</span>' +
      '</div>' +
      '<div class="' +
      getDeadlineClass(deal.deadline_at) +
      '">' +
      Utils.escapeHtml(deadlineText(deal.deadline_at)) +
      '</div>' +
      '<div class="deal-card-actions">' +
      '<button type="button" class="btn btn-ghost btn-sm" data-action="view">View</button>' +
      '<button type="button" class="btn btn-ghost btn-sm" data-action="edit">Edit</button>' +
      '</div>';

    card.addEventListener('dragstart', function (e) {
      draggedCard = card;
      draggedFromCol = card.parentElement;
      card.classList.add('deal-card--dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', String(deal.id));
    });
    card.addEventListener('dragend', function () {
      card.classList.remove('deal-card--dragging');
      draggedCard = null;
      draggedFromCol = null;
    });

    card.addEventListener('click', function (e) {
      if (e.target.closest('button')) return;
      openDealDrawer(Number(deal.id));
    });
    card.querySelector('[data-action="view"]').addEventListener('click', function (e) {
      e.stopPropagation();
      openDealDrawer(Number(deal.id));
    });
    card.querySelector('[data-action="edit"]').addEventListener('click', function (e) {
      e.stopPropagation();
      openEditModal(Number(deal.id));
    });

    return card;
  }

  function renderColumn(status, deals) {
    const col = document.createElement('div');
    col.className = 'kanban-column';
    col.dataset.status = status;
    const head = document.createElement('div');
    head.className = 'kanban-column-header';
    head.innerHTML =
      '<span>' +
      Utils.escapeHtml(STATUS_LABEL[status] || status) +
      '</span> ' +
      '<span class="kanban-column-count">(' +
      deals.length +
      ')</span>';
    const list = document.createElement('div');
    list.className = 'kanban-column-cards';
    deals.forEach(function (d) {
      list.appendChild(renderDealCard(d));
    });
    col.appendChild(head);
    col.appendChild(list);
    const addBtn = document.createElement('button');
    addBtn.type = 'button';
    addBtn.className = 'btn btn-secondary btn-sm kanban-add';
    addBtn.textContent = '+ Add deal';
    addBtn.addEventListener('click', function () {
      openCreateModal(status);
    });
    col.appendChild(addBtn);

    list.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
    });
    list.addEventListener('drop', function (e) {
      e.preventDefault();
      const id = parseInt(e.dataTransfer.getData('text/plain'), 10);
      if (!id) return;
      onDrop(id, status, list);
    });

    return col;
  }

  function onDrop(dealId, newStatus, listEl) {
    const prev = lastKanban ? JSON.parse(JSON.stringify(lastKanban)) : null;
    const card =
      document.querySelector('.deal-card[data-deal-id="' + dealId + '"]') || draggedCard;
    if (card && listEl) {
      listEl.appendChild(card);
      card.dataset.status = newStatus;
    }

    window
      .api('update_deal_status', 'POST', { deal_id: String(dealId), new_status: newStatus })
      .then(function () {
        window.Toast?.success('Status updated');
        loadKanban();
      })
      .catch(function (err) {
        window.Toast?.error(err.message || 'Could not move deal');
        if (prev) {
          lastKanban = prev;
          renderKanbanBoard(prev);
        } else {
          loadKanban();
        }
      });
  }

  function renderKanbanBoard(kanban) {
    const board = document.getElementById('kanbanBoard');
    if (!board) return;
    board.innerHTML = '';
    STATUSES.forEach(function (st) {
      const deals = (kanban && kanban[st]) || [];
      board.appendChild(renderColumn(st, deals));
    });
    board.setAttribute('aria-busy', 'false');
  }

  function loadKanban() {
    const board = document.getElementById('kanbanBoard');
    if (board) board.setAttribute('aria-busy', 'true');
    fetchJson('deals_data')
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Failed');
        const d = res.data || {};
        lastKanban = d.kanban || {};
        renderRevenueSummary(d.revenue_summary || {});
        renderKanbanBoard(lastKanban);
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Could not load deals');
      });
  }

  function openDrawerUi() {
    const d = document.getElementById('dealDrawer');
    const o = document.getElementById('dealDrawerOverlay');
    if (d) {
      d.classList.remove('hidden');
      d.setAttribute('aria-hidden', 'false');
    }
    if (o) {
      o.classList.remove('hidden');
      o.setAttribute('aria-hidden', 'false');
    }
  }

  function closeDealDrawer() {
    const d = document.getElementById('dealDrawer');
    const o = document.getElementById('dealDrawerOverlay');
    if (d) {
      d.classList.add('hidden');
      d.setAttribute('aria-hidden', 'true');
    }
    if (o) {
      o.classList.add('hidden');
      o.setAttribute('aria-hidden', 'true');
    }
    try {
      const u = new URL(window.location.href);
      u.searchParams.delete('id');
      window.history.replaceState({}, '', u.pathname + u.search);
    } catch (e) {
      /* ignore */
    }
  }

  function parseJsonField(raw) {
    if (raw === null || raw === undefined) return null;
    if (typeof raw === 'object') return raw;
    try {
      return JSON.parse(String(raw));
    } catch (e) {
      return String(raw);
    }
  }

  function openDealDrawer(dealId) {
    openDrawerUi();
    const body = document.getElementById('dealDrawerBody');
    const title = document.getElementById('dealDrawerTitle');
    if (body) body.innerHTML = '<p class="text-muted">Loading…</p>';
    if (title) title.textContent = 'Deal';

    try {
      const u = new URL(window.location.href);
      u.searchParams.set('route', 'deals');
      u.searchParams.set('id', String(dealId));
      window.history.replaceState({}, '', u.pathname + '?' + u.searchParams.toString());
    } catch (e) {
      /* ignore */
    }

    fetchJson('deal', { id: String(dealId) })
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Failed');
        const d = res.data || {};
        const deal = d.deal || {};
        if (title) {
          title.textContent = Utils.truncate(String(deal.title || 'Deal'), 48);
        }
        const posts = d.linked_posts || [];
        const inv = d.invoices || [];
        const activity = d.activity || [];

        let postsHtml =
          '<ul class="activity-list">';
        if (posts.length === 0) {
          postsHtml += '<li>No linked posts yet.</li>';
        } else {
          posts.forEach(function (p) {
            postsHtml +=
              '<li><strong>' +
              Utils.escapeHtml(Utils.truncate(p.title || '', 40)) +
              '</strong> · ' +
              Utils.escapeHtml(String(p.status || '')) +
              '</li>';
          });
        }
        postsHtml += '</ul>';

        let invHtml = '<ul class="activity-list">';
        if (inv.length === 0) {
          invHtml += '<li>No invoices yet.</li>';
        } else {
          inv.forEach(function (i) {
            invHtml +=
              '<li><strong>' +
              Utils.escapeHtml(String(i.invoice_number || '')) +
              '</strong> · ' +
              Utils.escapeHtml(String(i.status || '')) +
              ' · ' +
              Utils.escapeHtml(formatCurrency(i.total, i.currency)) +
              '</li>';
          });
        }
        invHtml += '</ul>';

        let actHtml = '<ul class="activity-list">';
        if (activity.length === 0) {
          actHtml += '<li>No activity logged yet.</li>';
        } else {
          activity.forEach(function (a) {
            const oldV = parseJsonField(a.old_values);
            const newV = parseJsonField(a.new_values);
            const detail =
              typeof newV === 'object' && newV !== null && newV.status
                ? String(newV.status)
                : String(a.action || '');
            actHtml +=
              '<li><time>' +
              Utils.escapeHtml(String(a.created_at || '')) +
              '</time>' +
              Utils.escapeHtml(String(a.action || '')) +
              (detail && detail !== a.action ? ' · ' + Utils.escapeHtml(detail) : '') +
              '</li>';
          });
        }
        actHtml += '</ul>';

        if (body) {
          body.innerHTML =
            '<div class="deal-drawer-section">' +
            '<h3>Overview</h3>' +
            '<div class="deal-detail-kv">' +
            '<div><span>Brand</span><span>' +
            Utils.escapeHtml(String(deal.brand_name || '')) +
            '</span></div>' +
            '<div><span>Amount</span><span>' +
            Utils.escapeHtml(formatCurrency(deal.amount, deal.currency)) +
            '</span></div>' +
            '<div><span>Type</span><span>' +
            Utils.escapeHtml(TYPE_LABEL[String(deal.deal_type)] || String(deal.deal_type || '')) +
            '</span></div>' +
            '<div><span>Status</span><span>' +
            Utils.escapeHtml(STATUS_LABEL[String(deal.status)] || String(deal.status || '')) +
            '</span></div>' +
            '<div><span>Deadline</span><span>' +
            Utils.escapeHtml(deal.deadline_at ? Utils.formatDate(deal.deadline_at, 'medium') : '—') +
            '</span></div>' +
            '</div>' +
            '<p style="margin-top:12px;font-size:var(--text-sm)">' +
            Utils.escapeHtml(String(deal.description || '')) +
            '</p>' +
            '</div>' +
            '<div class="deal-drawer-section">' +
            '<h3>Status</h3>' +
            '<select id="dealDrawerStatus" class="input-select">' +
            STATUSES.map(function (s) {
              return (
                '<option value="' +
                s +
                '"' +
                (String(deal.status) === s ? ' selected' : '') +
                '>' +
                STATUS_LABEL[s] +
                '</option>'
              );
            }).join('') +
            '</select>' +
            '</div>' +
            '<div class="deal-drawer-section">' +
            '<h3>Linked posts</h3>' +
            postsHtml +
            '</div>' +
            '<div class="deal-drawer-section">' +
            '<h3>Invoices</h3>' +
            invHtml +
            '<button type="button" class="btn btn-primary btn-sm" id="btnCreateInvoice" style="margin-top:8px">Create invoice</button>' +
            '</div>' +
            '<div class="deal-drawer-section">' +
            '<h3>Activity</h3>' +
            actHtml +
            '</div>' +
            '<div class="deal-drawer-section" style="display:flex;gap:8px;flex-wrap:wrap">' +
            '<button type="button" class="btn btn-secondary btn-sm" id="btnDrawerEdit">Edit deal</button>' +
            '<button type="button" class="btn btn-ghost btn-sm" id="btnDrawerDelete">Delete</button>' +
            '</div>';

          document.getElementById('dealDrawerStatus')?.addEventListener('change', function (ev) {
            const v = ev.target.value;
            window
              .api('update_deal_status', 'POST', {
                deal_id: String(dealId),
                new_status: v,
              })
              .then(function () {
                window.Toast?.success('Status saved');
                loadKanban();
                openDealDrawer(dealId);
              })
              .catch(function (err) {
                window.Toast?.error(err.message || 'Update failed');
              });
          });

          document.getElementById('btnCreateInvoice')?.addEventListener('click', function () {
            window
              .api('create_invoice', 'POST', { deal_id: String(dealId), status: 'draft' })
              .then(function () {
                window.Toast?.success('Invoice created');
                openDealDrawer(dealId);
              })
              .catch(function (err) {
                window.Toast?.error(err.message || 'Failed');
              });
          });

          document.getElementById('btnDrawerEdit')?.addEventListener('click', function () {
            openEditModal(dealId);
          });

          document.getElementById('btnDrawerDelete')?.addEventListener('click', function () {
            if (!window.confirm('Delete this deal?')) return;
            window
              .api('delete_deal', 'POST', { deal_id: String(dealId) })
              .then(function () {
                window.Toast?.success('Deal deleted');
                closeDealDrawer();
                loadKanban();
              })
              .catch(function (err) {
                window.Toast?.error(err.message || 'Failed');
              });
          });
        }
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Could not load deal');
        if (body) body.innerHTML = '<p class="text-muted">Could not load deal.</p>';
      });
  }

  function resetDealForm() {
    const form = document.getElementById('dealForm');
    if (form) form.reset();
    const hid = document.getElementById('dealFormId');
    if (hid) hid.value = '';
  }

  function openCreateModal(defaultStatus) {
    resetDealForm();
    const st = document.getElementById('dealStatus');
    if (st && defaultStatus) st.value = defaultStatus;
    fetch(assetPath('frontend/pages/monetization/deal-create.html'))
      .then(function (r) {
        return r.text();
      })
      .then(function (html) {
        window.Modal.open(
          'New deal',
          html,
          '<button type="button" class="btn btn-ghost btn-sm" id="modalCloseFooter">Cancel</button>',
        );
        document.getElementById('modalCloseFooter')?.addEventListener('click', function () {
          window.Modal.close();
        });
        bindDealFormSubmit();
      })
      .catch(function () {
        window.Toast?.error('Could not load form');
      });
  }

  function openEditModal(dealId) {
    fetchJson('deal', { id: String(dealId) })
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Failed');
        const deal = (res.data && res.data.deal) || {};
        return fetch(assetPath('frontend/pages/monetization/deal-create.html')).then(function (r) {
          return r.text().then(function (html) {
            return { html: html, deal: deal };
          });
        });
      })
      .then(function (o) {
        window.Modal.open('Edit deal', o.html, '');
        const d = o.deal;
        const set = function (id, val) {
          const el = document.getElementById(id);
          if (el) el.value = val !== undefined && val !== null ? String(val) : '';
        };
        set('dealFormId', d.id);
        set('dealBrandName', d.brand_name);
        set('dealBrandEmail', d.brand_email || '');
        set('dealTitle', d.title);
        set('dealType', d.deal_type);
        set('dealStatus', d.status);
        set('dealAmount', d.amount);
        set('dealCurrency', d.currency);
        set('dealDeadline', d.deadline_at || '');
        set('dealDescription', d.description || '');
        set('dealDeliverables', d.deliverables || '');
        set('dealNotes', d.notes || '');
        const foot = document.querySelector('#modalFooter');
        if (foot) {
          foot.innerHTML =
            '<button type="button" class="btn btn-ghost btn-sm" id="modalCloseFooter">Cancel</button>';
        }
        document.getElementById('modalCloseFooter')?.addEventListener('click', function () {
          window.Modal.close();
        });
        bindDealFormSubmit();
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Could not open deal');
      });
  }

  function bindDealFormSubmit() {
    const form = document.getElementById('dealForm');
    if (!form) return;
    form.addEventListener('submit', function onDealFormSubmit(e) {
      e.preventDefault();
      const fd = new FormData(form);
      const payload = {};
      fd.forEach(function (v, k) {
        payload[k] = v;
      });
      const id = (payload.id || '').trim();
      if (!id) {
        delete payload.id;
      }
      const route = id ? 'update_deal' : 'create_deal';
      window
        .api(route, 'POST', payload)
        .then(function (res) {
          window.Toast?.success(id ? 'Deal updated' : 'Deal created');
          form.removeEventListener('submit', onDealFormSubmit);
          window.Modal.close();
          loadKanban();
          if (id) openDealDrawer(Number(id));
        })
        .catch(function (err) {
          window.Toast?.error(err.message || 'Save failed');
        });
    });
  }

  function boot() {
    document.getElementById('btnNewDeal')?.addEventListener('click', function () {
      openCreateModal('lead');
    });
    document.getElementById('dealDrawerClose')?.addEventListener('click', closeDealDrawer);
    document.getElementById('dealDrawerOverlay')?.addEventListener('click', closeDealDrawer);

    loadKanban();

    const openId = Utils.getQueryParam('id');
    if (openId) {
      const n = parseInt(openId, 10);
      if (n > 0) openDealDrawer(n);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
