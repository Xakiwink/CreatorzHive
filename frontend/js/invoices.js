(function () {
  function formatMoney(amount, currency) {
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

  function statusBadge(inv) {
    const s = String(inv.display_status || inv.status || 'draft');
    const label = s.charAt(0).toUpperCase() + s.slice(1);
    return (
      '<span class="invoice-status invoice-status--' +
      Utils.escapeHtml(s) +
      '">' +
      Utils.escapeHtml(label) +
      '</span>'
    );
  }

  function fetchJson(route, query) {
    return fetch(window.routeQuery(route, query || {}), {
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function loadInvoices() {
    const st = document.getElementById('invoiceStatusFilter');
    const status = st && st.value ? st.value : '';
    const q = { page: '1', per_page: '50' };
    if (status) q.status = status;

    fetchJson('invoices_data', q)
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Failed');
        const d = res.data || {};
        const rows = d.invoices || [];
        const body = document.getElementById('invoicesTableBody');
        const paidEl = document.getElementById('invoicesPaidTotal');
        if (paidEl) {
          paidEl.innerHTML =
            '<strong>' +
            Utils.escapeHtml(formatMoney(d.paid_total, d.currency || 'TZS')) +
            '</strong>';
        }
        if (!body) return;
        if (rows.length === 0) {
          body.innerHTML =
            '<tr><td colspan="6" class="text-muted">No invoices yet.</td></tr>';
          return;
        }
        let html = '';
        rows.forEach(function (inv) {
          const due = inv.due_date ? Utils.formatDate(inv.due_date, 'medium') : '—';
          const canPay = String(inv.status || '') !== 'paid' && String(inv.status || '') !== 'cancelled';
          html +=
            '<tr data-invoice-id="' +
            Utils.escapeHtml(String(inv.id)) +
            '">' +
            '<td>' +
            Utils.escapeHtml(String(inv.invoice_number || '')) +
            '</td>' +
            '<td>' +
            Utils.escapeHtml(String(inv.recipient_name || '')) +
            '</td>' +
            '<td class="num">' +
            Utils.escapeHtml(formatMoney(inv.total, inv.currency)) +
            '</td>' +
            '<td>' +
            statusBadge(inv) +
            '</td>' +
            '<td>' +
            Utils.escapeHtml(due) +
            '</td>' +
            '<td><div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end">' +
            '<button type="button" class="btn btn-ghost btn-sm" data-act="view">View</button>' +
            (canPay
              ? '<button type="button" class="btn btn-primary btn-sm" data-act="pay">Mark paid</button>'
              : '') +
            '<button type="button" class="btn btn-ghost btn-sm" data-act="pdf">PDF</button>' +
            '</div></td>' +
            '</tr>';
        });
        body.innerHTML = html;

        body.querySelectorAll('button[data-act]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            const tr = btn.closest('tr');
            const id = tr ? parseInt(tr.getAttribute('data-invoice-id'), 10) : 0;
            const act = btn.getAttribute('data-act');
            if (act === 'view') showInvoice(id);
            if (act === 'pay') markPaid(id);
            if (act === 'pdf') window.Toast?.warning('PDF download coming soon');
          });
        });
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Could not load invoices');
      });
  }

  function markPaid(id) {
    window
      .api('mark_invoice_paid', 'POST', { id: String(id) })
      .then(function () {
        window.Toast?.success('Marked paid');
        loadInvoices();
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Failed');
      });
  }

  function showInvoice(id) {
    fetchJson('invoice', { id: String(id) })
      .then(function (res) {
        if (!res.success) throw new Error(res.message || 'Failed');
        const inv = res.data || {};
        const lines = Array.isArray(inv.line_items) ? inv.line_items : [];
        let liHtml = '<ul class="activity-list">';
        lines.forEach(function (l) {
          liHtml +=
            '<li>' +
            Utils.escapeHtml(String(l.description || '')) +
            ' × ' +
            Utils.escapeHtml(String(l.qty ?? 1)) +
            ' @ ' +
            Utils.escapeHtml(formatMoney(l.unit_price, inv.currency)) +
            '</li>';
        });
        liHtml += '</ul>';
        const html =
          '<div class="deal-detail-kv">' +
          '<div><span>Number</span><span>' +
          Utils.escapeHtml(String(inv.invoice_number || '')) +
          '</span></div>' +
          '<div><span>Client</span><span>' +
          Utils.escapeHtml(String(inv.recipient_name || '')) +
          '</span></div>' +
          '<div><span>Email</span><span>' +
          Utils.escapeHtml(String(inv.recipient_email || '')) +
          '</span></div>' +
          '<div><span>Total</span><span>' +
          Utils.escapeHtml(formatMoney(inv.total, inv.currency)) +
          '</span></div>' +
          '<div><span>Status</span><span>' +
          Utils.escapeHtml(String(inv.display_status || inv.status || '')) +
          '</span></div>' +
          '</div>' +
          '<h4 style="margin:16px 0 8px;font-size:var(--text-sm)">Line items</h4>' +
          liHtml;
        window.Modal.open('Invoice', html, '');
      })
      .catch(function (e) {
        window.Toast?.error(e.message || 'Could not load invoice');
      });
  }

  function boot() {
    document.getElementById('invoiceStatusFilter')?.addEventListener('change', loadInvoices);
    loadInvoices();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
