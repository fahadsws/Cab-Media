/* Media library: one browser component used by the Media library page (manage mode) and the picker modal (pick mode).
   Picker API:  MediaLibrary.pick(function (url) { ... })   or any element with  data-media-pick="#inputSelector"  */
(function () {
  const A = window.ADMIN || {}, M = A.media || {};
  if (!M.list) return;
  const esc = t => String(t).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const toast = (t, m) => window.AdminUI && AdminUI.toast(t, m);
  const fmt = b => b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
  const headers = { 'X-CSRF-TOKEN': A.csrf, 'Accept': 'application/json' };

  function api(url, opt) {
    return fetch(url, Object.assign({ headers: headers, credentials: 'same-origin' }, opt)).then(r => r.json().catch(() => ({})).then(j => ({ ok: r.ok, status: r.status, j: j })));
  }

  function Browser(el, opt) {
    const s = { page: 1, root: 'uploads', path: '', q: '', sel: new Set(), data: null, pick: !!opt.pick, multi: false };
    if (s.pick) el.classList.add('ml-flat');
    const $ = sel => el.querySelector(sel);

    function load() {
      $('[data-ml-grid]').innerHTML = '<div class="text-muted p-4">Loading…</div>';
      const qs = new URLSearchParams({ root: s.root, path: s.path, q: s.q, page: s.page }); if (s.pick) qs.set('flat', 1); // picker: all images, no folders
      api(M.list + '?' + qs).then(r => { if (!r.ok) { $('[data-ml-grid]').innerHTML = '<div class="text-danger p-4">Could not load this folder.</div>'; return; } s.data = r.j; s.page = r.j.page; s.sel.clear(); render(); });
    }

    function crumbs() {
      let h = '<a href="#" data-go="">' + esc(M.roots[s.root]) + '</a>', acc = '';
      s.path.split('/').filter(Boolean).forEach(p => { acc += (acc ? '/' : '') + p; h += ' <i class="ti ti-chevron-right small"></i> <a href="#" data-go="' + esc(acc) + '">' + esc(p) + '</a>'; });
      return h;
    }

    function render() {
      const d = s.data;
      $('[data-ml-crumbs]').innerHTML = crumbs();
      let h = '';
      d.folders.forEach(f => {
        h += '<div class="ml-item ml-folder" data-go="' + esc(f.path) + '" title="' + esc(f.name) + '"><div class="ml-thumb"><i class="ti ti-folder-filled"></i></div>' +
          '<div class="ml-name">' + esc(f.name) + '</div><div class="ml-meta">' + f.count + ' file' + (f.count === 1 ? '' : 's') + '</div>' +
          (s.pick ? '' : '<button type="button" class="ml-x" data-del-folder="' + esc(f.path) + '" title="Delete folder"><i class="ti ti-trash"></i></button>') + '</div>';
      });
      d.files.forEach(f => {
        h += '<div class="ml-item ml-file" data-file="' + esc(f.path) + '" title="' + esc(f.name) + '"><div class="ml-thumb">' +
          (f.image ? '<img loading="lazy" src="' + esc(f.url) + '" alt="">' : '<i class="ti ti-file"></i><span>' + esc(f.ext) + '</span>') + '</div>' +
          '<div class="ml-name">' + esc(f.name) + '</div>' + (f.folder ? '<div class="ml-folderlbl" title="' + esc(f.folder) + '"><i class="ti ti-folder"></i> ' + esc(f.folder) + '</div>' : '') + '<div class="ml-meta">' + fmt(f.size) + '</div>' +
          (!s.pick || s.multi ? '<span class="ml-check"><i class="ti ti-check"></i></span>' : '') + '</div>';
      });
      $('[data-ml-grid]').innerHTML = h || '<div class="text-muted p-4">' + (s.q ? 'No images match your search.' : 'This folder is empty.') + '</div>';
      const pg = $('[data-ml-pages]');
      pg.innerHTML = d.pages > 1 ? '<button type="button" class="btn btn-sm btn-label-secondary" data-page="' + (d.page - 1) + '"' + (d.page <= 1 ? ' disabled' : '') + '><i class="ti ti-chevron-left"></i> Prev</button>' +
        '<span class="small text-muted mx-3">Page ' + d.page + ' of ' + d.pages + ' &middot; ' + d.total + ' file' + (d.total === 1 ? '' : 's') + '</span>' +
        '<button type="button" class="btn btn-sm btn-label-secondary" data-page="' + (d.page + 1) + '"' + (d.page >= d.pages ? ' disabled' : '') + '>Next <i class="ti ti-chevron-right"></i></button>' : '';
      $('[data-ml-up]') && ($('[data-ml-up]').disabled = !s.path);
      updateBar();
    }

    function updateBar() {
      if (s.pick) { opt.onSel && opt.onSel(s.sel.size); el.querySelectorAll('.ml-file').forEach(n => n.classList.toggle('sel', s.sel.has(n.dataset.file))); return; }
      const bar = $('[data-ml-bar]'); if (!bar) return;
      bar.classList.toggle('d-none', !s.sel.size);
      $('[data-ml-selcount]').textContent = s.sel.size + ' selected';
      el.querySelectorAll('.ml-file').forEach(n => n.classList.toggle('sel', s.sel.has(n.dataset.file)));
    }

    function fileByPath(p) { return s.data.files.find(f => f.path === p); }

    function go(path) { s.path = path; s.page = 1; s.q = ''; const q = $('[data-ml-search]'); if (q) q.value = ''; load(); }

    function del(paths, force) {
      return api(M.destroy, { method: 'DELETE', headers: Object.assign({ 'Content-Type': 'application/json' }, headers), body: JSON.stringify({ root: s.root, paths: paths, force: force ? 1 : 0 }) }).then(r => {
        if (r.status === 409) {
          const lines = Object.entries(r.j.in_use).slice(0, 6).map(([f, u]) => '<li><b>' + esc(f) + '</b><br><small class="text-muted">' + esc(u.join(', ')) + '</small></li>').join('');
          return AdminUI.confirm({ title: 'Still in use', danger: true, icon: 'ti-alert-triangle', okText: 'Delete anyway',
            html: esc(r.j.message) + ' Deleting will leave broken images on the site.<ul class="text-start small mt-3 mb-0">' + lines + '</ul>' })
            .then(c => c.confirmed ? del(paths, true) : null);
        }
        if (!r.ok) { toast('error', r.j.message || 'Delete failed.'); return null; }
        toast('success', r.j.deleted + ' file(s) deleted.'); load();
      });
    }

    function upload(files) {
      if (!files.length) return;
      const fd = new FormData(); fd.append('root', s.root); fd.append('path', s.path);
      Array.from(files).forEach(f => fd.append('files[]', f));
      const st = $('[data-ml-status]'); st.textContent = 'Uploading ' + files.length + ' file(s)…';
      api(M.upload, { method: 'POST', body: fd }).then(r => {
        st.textContent = '';
        if (!r.ok) { toast('error', (r.j.errors ? Object.values(r.j.errors)[0][0] : r.j.message) || 'Upload failed.'); return; }
        toast('success', r.j.saved.length + ' file(s) uploaded.'); load();
      });
    }

    el.addEventListener('click', function (e) {
      const t = e.target;
      let n;
      if ((n = t.closest('[data-page]'))) { if (!n.disabled) { s.page = +n.dataset.page; load(); } return; }
      if ((n = t.closest('[data-del-folder]'))) { e.stopPropagation(); AdminUI.confirm({ title: 'Delete folder?', danger: true, icon: 'ti-trash', html: 'The folder and every file inside it will be deleted.', okText: 'Delete' }).then(c => c.confirmed && del([n.dataset.delFolder])); return; }
      if ((n = t.closest('[data-go]'))) { e.preventDefault(); go(n.dataset.go); return; }
      if (t.closest('[data-ml-up]')) { go(s.path.split('/').slice(0, -1).join('/')); return; }
      if ((n = t.closest('[data-root]'))) { s.root = n.dataset.root; el.querySelectorAll('[data-root]').forEach(b => b.classList.toggle('active', b === n)); go(''); return; }
      if (t.closest('[data-ml-newfolder]')) {
        const name = prompt('New folder name (letters, numbers, space, - and _):'); if (!name) return;
        api(M.folder, { method: 'POST', headers: Object.assign({ 'Content-Type': 'application/json' }, headers), body: JSON.stringify({ root: s.root, path: s.path, name: name }) })
          .then(r => r.ok ? (toast('success', 'Folder created.'), load()) : toast('error', (r.j.errors ? Object.values(r.j.errors)[0][0] : r.j.message) || 'Could not create folder.'));
        return;
      }
      if (t.closest('[data-ml-delete]')) { const paths = Array.from(s.sel); AdminUI.confirm({ title: 'Delete ' + paths.length + ' file(s)?', danger: true, icon: 'ti-trash', html: 'This removes the file(s) from the server.', okText: 'Delete' }).then(c => c.confirmed && del(paths)); return; }
      if (t.closest('[data-ml-clear]')) { s.sel.clear(); updateBar(); return; }
      if (t.closest('[data-ml-copy]')) { const f = fileByPath(Array.from(s.sel)[0]); if (f) navigator.clipboard.writeText(f.url).then(() => toast('success', 'URL copied.')); return; }
      if ((n = t.closest('.ml-file'))) {
        const f = fileByPath(n.dataset.file);
        if (s.pick && !s.multi) { opt.onPick && opt.onPick([f]); return; }
        if (s.pick) { s.sel.has(f.path) ? s.sel.delete(f.path) : s.sel.add(f.path); updateBar(); return; }
        if (e.ctrlKey || e.metaKey || t.closest('.ml-check')) { s.sel.has(f.path) ? s.sel.delete(f.path) : s.sel.add(f.path); }
        else { const was = s.sel.size === 1 && s.sel.has(f.path); s.sel.clear(); if (!was) s.sel.add(f.path); }
        updateBar();
      }
    });
    const up = $('[data-ml-upload]'); up && up.addEventListener('change', () => { upload(up.files); up.value = ''; });
    const q = $('[data-ml-search]'); let tm; q && q.addEventListener('input', () => { clearTimeout(tm); tm = setTimeout(() => { s.q = q.value; s.page = 1; load(); }, 300); });
    el.addEventListener('dragover', e => { if (e.dataTransfer && Array.from(e.dataTransfer.types).includes('Files')) { e.preventDefault(); el.classList.add('ml-drop'); } });
    el.addEventListener('dragleave', e => { if (e.target === el) el.classList.remove('ml-drop'); });
    el.addEventListener('drop', e => { if (e.dataTransfer && e.dataTransfer.files.length) { e.preventDefault(); el.classList.remove('ml-drop'); upload(e.dataTransfer.files); } });

    load();
    return { reload: load, setMulti: m => { s.multi = !!m; s.sel.clear(); if (s.data) render(); }, chosen: () => Array.from(s.sel).map(fileByPath).filter(Boolean) };
  }

  window.MediaLibrary = {
    mount: Browser,
    /* Open the picker modal; cb receives an array of chosen files [{url, name, ...}]. opts.multi allows several. */
    pick: function (cb, opts) {
      const modalEl = document.getElementById('mediaPickerModal'); if (!modalEl) return toast('error', 'Media library is not available for your role.');
      const Modal = (window.bootstrap && window.bootstrap.Modal) || window.Modal, modal = Modal.getOrCreateInstance(modalEl);
      const host = modalEl.querySelector('[data-ml-host]'), use = modalEl.querySelector('[data-ml-use]'), multi = !!(opts && opts.multi);
      if (!host._ml) {
        host._ml = Browser(host, { pick: true, onPick: files => { modal.hide(); host._cb && host._cb(files); }, onSel: n => { use.classList.toggle('d-none', !host._multi); use.textContent = 'Use ' + n + ' selected'; use.disabled = !n; } });
        use.addEventListener('click', () => { const f = host._ml.chosen(); if (!f.length) return; modal.hide(); host._cb && host._cb(f); });
      } else host._ml.reload();
      host._multi = multi; host._cb = cb; host._ml.setMulti(multi); use.classList.toggle('d-none', !multi); modal.show();
    },
  };

  // <button data-media-pick="#input">: fills the input with the chosen URL. "^" = the input in the same .input-group.
  // Optional data-media-preview="#img": shows the chosen image there.
  document.addEventListener('click', function (e) {
    const b = e.target.closest('[data-media-pick]'); if (!b) return;
    e.preventDefault();
    MediaLibrary.pick(function (files) {
      const f = files[0], input = b.dataset.mediaPick === '^' ? b.closest('.input-group').querySelector('input') : document.querySelector(b.dataset.mediaPick); if (!input) return;
      input.value = f.url; input.dispatchEvent(new Event('input', { bubbles: true })); input.dispatchEvent(new Event('change', { bubbles: true }));
      const pv = b.dataset.mediaPreview && document.querySelector(b.dataset.mediaPreview); if (pv) { pv.src = f.url; pv.classList.remove('d-none'); }
    });
  });

  // <div data-media-multi="field[]" data-media-list="#thumbs"><button data-media-pick-multi>: adds one hidden input per chosen image.
  document.addEventListener('click', function (e) {
    const b = e.target.closest('[data-media-pick-multi]'); if (!b) return;
    e.preventDefault();
    const box = b.closest('[data-media-multi]'), list = document.querySelector(box.dataset.mediaList);
    MediaLibrary.pick(function (files) {
      files.forEach(f => {
        if (box.querySelector('input[value="' + f.url.replace(/"/g, '') + '"]')) return;
        const w = document.createElement('div'); w.className = 'position-relative';
        w.innerHTML = '<img src="' + esc(f.url) + '" style="height:60px;border-radius:6px"><input type="hidden" name="' + esc(box.dataset.mediaMulti) + '" value="' + esc(f.url) + '"><button type="button" class="btn-close position-absolute top-0 end-0 bg-white" style="font-size:.6rem"></button>';
        w.querySelector('.btn-close').onclick = () => w.remove();
        list.appendChild(w);
      });
    }, { multi: true });
  });

  // Manage page
  const page = document.querySelector('[data-ml-page]'); if (page) Browser(page, { pick: false });
})();
