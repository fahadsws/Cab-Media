{{-- Articles → "Add articles from links" modal. Expects $categories. Stacked layout, no side-by-side blocks, so nothing overlaps. --}}
<div class="modal fade" id="importArticlesModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="ti ti-link me-1 text-primary"></i>Import articles from links</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <p class="text-muted small mb-3">Paste one or many news page links, <b>one per line</b> (up to 30). Each article opens in the editor for you to check and publish.</p>
    <form method="POST" action="{{ route('admin.articles.import-url') }}" id="artImpForm">@csrf
      <div class="mb-3"><label class="form-label small mb-1">Article links (one per line)</label><textarea name="urls" id="artImpUrls" rows="5" class="form-control" placeholder="https://www.example.com/news/story-one&#10;https://www.example.com/news/story-two" required>{{ old('urls', old('url')) }}</textarea></div>
      <label class="form-label small mb-1">How should we bring the article in?</label>
      <div class="d-flex flex-column gap-2 mb-3">
        <label class="border rounded p-3 d-flex gap-2 align-items-start" for="mode-exact"><input class="form-check-input mt-1 flex-shrink-0" type="radio" name="mode" id="mode-exact" value="exact" @checked(old('mode', 'exact') === 'exact')>
          <span><span class="d-block fw-semibold">Exact copy: 100% accurate (no AI)</span><span class="d-block small text-muted">Scraped exactly as published: every fact, number, table, quote and picture, in the original order. The source is credited.</span></span></label>
        <label class="border rounded p-3 d-flex gap-2 align-items-start" for="mode-ai"><input class="form-check-input mt-1 flex-shrink-0" type="radio" name="mode" id="mode-ai" value="ai" @checked(old('mode') === 'ai')>
          <span><span class="d-block fw-semibold">AI rewrite: our own words, same facts</span><span class="d-block small text-muted">Rewritten and fact-checked by AI so it is unique. Needs the AI provider (Settings, AI provider).</span></span></label>
      </div>
      <div class="mb-3"><label class="form-label small mb-1">Category (optional)</label><select name="category_id" class="form-select"><option value="">Detect automatically</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
      <div class="form-check mb-1"><input class="form-check-input" type="checkbox" name="draft" id="imp-draft" value="1" checked><label class="form-check-label small" for="imp-draft">Save as draft</label></div>
      <div class="form-check mb-3" id="seoWrap"><input class="form-check-input" type="checkbox" name="ai_seo" id="imp-seo" value="1"><label class="form-check-label small" for="imp-seo">Add AI meta, FAQ &amp; takeaways (the article body is never touched)</label></div>
      <button class="btn btn-primary w-100" id="artImpGo"><i class="ti ti-download me-1"></i>Import</button>
    </form>
    <div id="artImpLog" class="mt-3 small" hidden></div>
  </div>
</div></div></div>
