(function () {
  const PER_PAGE = 24;
  let page = 1;
  let typeFilter = '';

  function fetchMediaList() {
    const q = { page: page, per_page: PER_PAGE };
    if (typeFilter) q.type = typeFilter;
    return fetch(window.routeQuery('media_list', q), {
      headers: { Accept: 'application/json' },
    }).then(function (r) {
      return r.json();
    });
  }

  function formatBytes(n) {
    const x = Number(n) || 0;
    if (x < 1024) return x + ' B';
    if (x < 1048576) return (x / 1024).toFixed(1) + ' KB';
    return (x / 1048576).toFixed(1) + ' MB';
  }

  function renderCard(f) {
    const card = document.createElement('div');
    card.className = 'media-page-card';
    const thumb = f.thumbnail_url || f.cdn_url || '';
    const isVideo = String(f.mime_type || '').indexOf('video/') === 0;

    let thumbHtml = '<div class="thumb-fallback">No preview</div>';
    if (thumb) {
      thumbHtml = isVideo
        ? '<video src="' + Utils.escapeHtml(f.cdn_url || thumb) + '" muted playsinline></video>'
        : '<img src="' + Utils.escapeHtml(thumb) + '" alt="">';
    }

    card.innerHTML =
      '<div class="thumb-wrap">' +
      thumbHtml +
      '</div>' +
      '<div class="card-meta">' +
      '<div class="card-name" title="' +
      Utils.escapeHtml(f.original_name || '') +
      '">' +
      Utils.escapeHtml(Utils.truncate(f.original_name || f.file_name || '', 36)) +
      '</div>' +
      '<div class="card-meta-row"><span>' +
      Utils.escapeHtml(String(f.mime_type || '').split('/')[0] || 'file') +
      '</span><span>' +
      formatBytes(f.file_size) +
      '</span></div>' +
      '<div class="card-actions">' +
      '<button type="button" class="btn btn-ghost btn-sm" data-copy="' +
      Utils.escapeHtml(String(f.id)) +
      '">Copy URL</button>' +
      '<button type="button" class="btn btn-ghost btn-sm" data-del="' +
      Utils.escapeHtml(String(f.id)) +
      '">Delete</button>' +
      '</div></div>';

    card.querySelector('[data-copy]').addEventListener('click', function () {
      const url = f.cdn_url || '';
      if (!url) return;
      Utils.copyToClipboard(url).then(function (ok) {
        window.Toast[ok ? 'success' : 'error'](ok ? 'URL copied' : 'Copy failed');
      });
    });

    card.querySelector('[data-del]').addEventListener('click', function () {
      window.Media.deleteMedia(Number(f.id)).then(function (ok) {
        if (ok) {
          window.Toast.success('File deleted');
          loadGrid();
        }
      }).catch(function (e) {
        window.Toast.error(e.message || 'Delete failed');
      });
    });

    return card;
  }

  function renderPagination(meta) {
    const el = document.getElementById('mediaPagePagination');
    if (!el) return;
    const total = meta.total || 0;
    const totalPages = meta.total_pages || 0;
    const p = meta.page || 1;
    if (totalPages < 2) {
      el.innerHTML = '<span class="text-muted">' + total + ' files</span>';
      return;
    }
    el.innerHTML =
      '<span class="text-muted">' +
      total +
      ' files · page ' +
      p +
      ' / ' +
      totalPages +
      '</span>' +
      '<button type="button" class="btn btn-sm btn-ghost" id="mediaPgPrev"' +
      (p <= 1 ? ' disabled' : '') +
      '>Previous</button>' +
      '<button type="button" class="btn btn-sm btn-ghost" id="mediaPgNext"' +
      (p >= totalPages ? ' disabled' : '') +
      '>Next</button>';
    document.getElementById('mediaPgPrev')?.addEventListener('click', function () {
      page = Math.max(1, p - 1);
      loadGrid();
    });
    document.getElementById('mediaPgNext')?.addEventListener('click', function () {
      page = Math.min(totalPages, p + 1);
      loadGrid();
    });
  }

  function loadGrid() {
    const grid = document.getElementById('mediaPageGrid');
    if (!grid) return;
    grid.innerHTML =
      '<div class="skeleton" style="grid-column:1/-1;height:200px;border-radius:12px"></div>';

    fetchMediaList()
      .then(function (j) {
        if (!j.success) throw new Error(j.message || 'Failed to load');
        const data = j.data || {};
        const files = data.files || [];
        grid.innerHTML = '';
        if (!files.length) {
          grid.innerHTML =
            '<p class="text-muted" style="grid-column:1/-1;text-align:center;padding:32px">No files yet. Upload or drop files above.</p>';
        } else {
          files.forEach(function (f) {
            grid.appendChild(renderCard(f));
          });
        }
        renderPagination(data.meta || {});
      })
      .catch(function (e) {
        grid.innerHTML = '';
        window.Toast.error(e.message || 'Failed to load media');
      });
  }

  function handleUploadFiles(files) {
    if (!files || !files.length) return;
    let pending = files.length;
    files.forEach(function (file) {
      window.Media.uploadFile(file)
        .then(function (j) {
          if (!j.success) throw new Error(j.message || 'Upload failed');
          window.Toast.success('Uploaded: ' + (file.name || ''));
        })
        .catch(function (err) {
          window.Toast.error(err.message || 'Upload failed');
        })
        .finally(function () {
          pending -= 1;
          if (pending <= 0) {
            page = 1;
            loadGrid();
          }
        });
    });
  }

  function init() {
    if (!document.getElementById('mediaLibraryPage')) return;

    document.getElementById('mediaFilterType')?.addEventListener('change', function (e) {
      typeFilter = e.target.value || '';
      page = 1;
      loadGrid();
    });

    const fi = document.getElementById('mediaPageFileInput');
    document.getElementById('mediaUploadBtn')?.addEventListener('click', function () {
      fi?.click();
    });
    fi?.addEventListener('change', function () {
      if (fi.files && fi.files.length) handleUploadFiles(Array.from(fi.files));
      fi.value = '';
    });

    const dz = document.getElementById('mediaPageDropzone');
    window.Media.initDropZone(dz, handleUploadFiles);

    loadGrid();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
