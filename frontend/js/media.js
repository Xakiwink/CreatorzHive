(function () {
  function uploadFile(file, onProgress) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', window.__CSRF__ || '');
    return fetch(window.routeQuery('upload_media'), {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: fd,
    }).then(function (r) {
      return r.json();
    });
  }

  function initDropZone(el, onFiles) {
    if (!el) return;
    el.addEventListener('dragover', function (e) {
      e.preventDefault();
      el.classList.add('dragover');
    });
    el.addEventListener('dragleave', function () {
      el.classList.remove('dragover');
    });
    el.addEventListener('drop', function (e) {
      e.preventDefault();
      el.classList.remove('dragover');
      const files = e.dataTransfer?.files;
      if (files && files.length) onFiles(Array.from(files));
    });
  }

  function renderMediaGrid(files, onPick) {
    const wrap = document.createElement('div');
    wrap.className = 'media-lib-grid';
    (files || []).forEach(function (f) {
      const card = document.createElement('button');
      card.type = 'button';
      card.className = 'media-lib-item';
      const thumb = f.thumbnail_url || f.cdn_url || '';
      card.innerHTML =
        (thumb
          ? '<img src="' + Utils.escapeHtml(thumb) + '" alt="">'
          : '<span class="media-lib-fallback">File</span>') +
        '<span class="media-lib-name">' +
        Utils.escapeHtml(Utils.truncate(f.original_name || '', 24)) +
        '</span>';
      card.addEventListener('click', function () {
        onPick(f);
      });
      wrap.appendChild(card);
    });
    return wrap;
  }

  function openMediaLibrary(onPick) {
    fetch(window.routeQuery('media_list', { per_page: 48 }), {
      headers: { Accept: 'application/json' },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        if (!j.success) throw new Error(j.message || 'Failed to load media');
        const files = (j.data && j.data.files) || [];
        const grid = renderMediaGrid(files, function (f) {
          window.Modal.close();
          onPick(f);
        });
        window.Modal.open(
          'Media library',
          '<div class="media-lib-body"></div>',
          '<button type="button" class="btn btn-ghost" id="mediaLibClose">Close</button>',
        );
        const b = document.querySelector('#modalBody .media-lib-body');
        if (b) b.appendChild(grid);
        document.getElementById('mediaLibClose')?.addEventListener('click', function () {
          window.Modal.close();
        });
      })
      .catch(function (e) {
        window.Toast.error(e.message || 'Media library failed');
      });
  }

  function deleteMedia(mediaId) {
    if (!mediaId || !window.confirm('Delete this file?')) return Promise.resolve(false);
    return window
      .api('delete_media', 'POST', { id: String(mediaId) })
      .then(function () {
        return true;
      });
  }

  window.Media = {
    uploadFile: uploadFile,
    initDropZone: initDropZone,
    renderMediaGrid: renderMediaGrid,
    openMediaLibrary: openMediaLibrary,
    deleteMedia: deleteMedia,
  };
})();
