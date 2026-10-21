@extends('admin.layout')
@section('title', 'Articles')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h4 class="mb-0">Articles</h4>
  @can('articles.create')<div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#importArticlesModal"><i class="ti ti-link me-1"></i>Add articles from links</button><a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New article</a></div>@endcan
</div>

@can('articles.create')
@include('admin.partials.article-import-modal')
<script>
(() => {
  const f = document.getElementById('artImpForm'); if (!f) return;
  const log = document.getElementById('artImpLog'), btn = document.getElementById('artImpGo');
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const seo = document.getElementById('seoWrap');
  const sync = () => { seo.hidden = f.mode.value === 'ai'; };   // AI rewrite already produces SEO fields
  f.querySelectorAll('input[name=mode]').forEach(r => r.addEventListener('change', sync)); sync();
  f.addEventListener('submit', async e => {
    const links = [...new Set(f.urls.value.split(/[\s,]+/).filter(u => /^https?:\/\//i.test(u)))];
    if (links.length < 1) return;                                   // let the server explain
    if (links.length === 1) return;                                 // a single page: normal submit, opens the editor
    e.preventDefault(); btn.disabled = true; log.hidden = false; log.innerHTML = '';
    let ok = 0;
    for (const [i, u] of links.entries()) {                         // one link per request: no timeouts, live progress
      const row = document.createElement('div'); row.className = 'py-1'; row.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + (i + 1) + '/' + links.length + ' · ' + esc(u); log.appendChild(row);
      try {
        const body = new FormData(); body.append('_token', f._token.value); body.append('url', u); body.append('mode', f.mode.value);
        if (f.category_id.value) body.append('category_id', f.category_id.value); if (f.draft.checked) body.append('draft', '1'); if (f.ai_seo.checked) body.append('ai_seo', '1');
        const r = await fetch(f.action, { method: 'POST', body, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        let d = {}; try { d = await r.json(); } catch (er) {}
        if (r.ok && d.ok) { ok++; row.innerHTML = '<span class="text-success">✔</span> <a href="' + d.edit_url + '" target="_blank">' + esc(d.title) + '</a> · ' + d.words + ' words · ' + esc(d.status); }
        else row.innerHTML = '<span class="text-danger">✖</span> ' + esc(u) + ' — ' + esc(d.message || ('Failed (' + r.status + ')'));
      } catch (er) { row.innerHTML = '<span class="text-danger">✖</span> ' + esc(u) + ' — connection problem'; }
    }
    btn.disabled = false;
    const done = document.createElement('div'); done.className = 'mt-2 fw-semibold'; done.innerHTML = ok + ' of ' + links.length + ' imported. <a href="">Refresh the list</a>'; log.appendChild(done);
  });
})();
</script>
@endcan

<div class="card dt-wrap">
  <div class="card-header border-bottom">
    <div class="row g-3 align-items-end">
      <div class="col-6 col-md-3 col-xl-2"><label class="form-label small mb-1">Status</label>
        <select class="dt-filter" name="status" data-search="off"><option value="">All statuses</option>@foreach (['published', 'scheduled', 'draft'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach<option value="duplicate">Merged duplicates</option></select></div>
      <div class="col-6 col-md-3 col-xl-2"><label class="form-label small mb-1">Category</label>
        <select class="dt-filter" name="category"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
      <div class="col-6 col-md-3 col-xl-2"><label class="form-label small mb-1">Origin</label>
        <select class="dt-filter" name="origin" data-search="off"><option value="">AI + manual</option><option value="ai">AI generated</option><option value="manual">Written manually</option></select></div>
      @can('articles.edit_all')
      <div class="col-6 col-md-3 col-xl-2"><label class="form-label small mb-1">Author</label>
        <select class="dt-filter" name="author"><option value="">Anyone</option><option value="none">Automation desk</option>@foreach ($authors as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
      @endcan
      <div class="col-md-4 col-xl-3"><label class="form-label small mb-1">Created between</label><input class="flatpickr-range dt-filter" name="range" placeholder="Pick a date range"></div>
      <div class="col-auto"><button type="button" class="btn btn-label-secondary dt-reset"><i class="ti ti-refresh me-1"></i>Reset</button></div>
    </div>
  </div>
  @include('admin.partials.dt', [
    'url' => route('admin.articles.data'), 'order' => [[4, 'desc']],
    'columns' => [
      ['title' => 'Article', 'orderable' => true], ['title' => 'Status', 'orderable' => true], ['title' => 'Category', 'class' => 'd-none d-lg-table-cell'], ['title' => 'Author', 'class' => 'd-none d-xl-table-cell'],
      ['title' => 'Created', 'orderable' => true], ['title' => 'Views', 'orderable' => true, 'class' => 'd-none d-xxl-table-cell'], ['title' => '', 'class' => 'text-end'],
    ],
  ])
</div>
@endsection
