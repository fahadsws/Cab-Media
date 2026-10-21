<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesBulk;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Services\AiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    use HandlesBulk;

    private function canEdit(Article $a): bool
    {
        $u = auth()->user();
        return $u->can('articles.edit_all') || ($u->can('articles.edit_own') && $a->user_id === $u->id);
    }

    public function index()
    {
        return view('admin.articles.index', [
            'categories' => Category::orderBy('name')->get(),
            'authors' => \App\Models\User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Current list filters (shared by the table and "select all matching" bulk actions). */
    private function filtered(Request $r)
    {
        $u = $r->user();
        $q = Article::with(['category', 'author'])
            ->when(! $u->can('articles.edit_all'), fn ($x) => $x->where('user_id', $u->id))
            ->when($r->status === 'duplicate', fn ($x) => $x->whereNotNull('duplicate_of'), fn ($x) => $x->whereNull('duplicate_of'))    // merged duplicates only show under their own filter
            ->when($r->status && $r->status !== 'duplicate', fn ($x) => $x->where('status', $r->status))
            ->when($r->category, fn ($x, $c) => $x->where('category_id', $c))
            ->when($r->origin === 'ai', fn ($x) => $x->where('is_ai_generated', true))
            ->when($r->origin === 'manual', fn ($x) => $x->where('is_ai_generated', false))
            ->when($r->author === 'none', fn ($x) => $x->whereNull('user_id'))
            ->when($r->author && $r->author !== 'none' && $u->can('articles.edit_all'), fn ($x) => $x->where('user_id', $r->author));
        \App\Support\DataTable::dateRange($q, $r->range);

        return $q;
    }

    public function data(Request $r)
    {
        $q = $this->filtered($r);

        return \App\Support\DataTable::make($q, $r, ['articles.title', 'status', null, null, 'created_at', 'views', null], ['title', 'excerpt', 'slug'],
            fn (Article $a) => [
                \App\Support\Ui::thumb($a->image_url, \Illuminate\Support\Str::limit($a->title, 70), ($a->is_ai_generated ? 'AI · ' : '').($a->published_at?->format('d M Y, H:i') ?? 'not published'), route('admin.articles.edit', $a)),
                \App\Support\Ui::status($a->status),
                e($a->category?->name ?? '—'),
                e($a->author?->name ?? 'Automation desk'),
                \App\Support\Ui::date($a->created_at),
                number_format($a->views),
                \App\Support\Ui::actions([
                    'view' => $a->status === 'published' ? $a->url : route('admin.articles.preview', $a), 'view_title' => $a->status === 'published' ? 'View on website' : 'Preview (not public)', 'edit' => route('admin.articles.edit', $a),
                    'delete' => auth()->user()->can('articles.delete') ? route('admin.articles.destroy', $a) : null, 'delete_msg' => 'This deletes the blog post permanently.',
                ]),
            ]);
    }
    /** Show the article exactly as the public page would, even while it is a draft/scheduled. */
    public function preview(Article $article)
    {
        abort_unless($this->canEdit($article) || auth()->user()->can('articles.view') && $article->user_id === auth()->id(), 403);
        $article->load(['category', 'author']);
        return view('admin.preview', ['title' => $article->title, 'image' => $article->has_image ? $article->image_url : null, 'body' => $article->body, 'meta' => 'Blog preview · '.ucfirst($article->status).($article->category ? ' · '.$article->category->name : '')]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article(['status' => 'draft']), 'categories' => Category::orderBy('name')->get()]);
    }

    public function edit(Article $article)
    {
        abort_unless($this->canEdit($article), 403);
        return view('admin.articles.form', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $r)
    {
        $article = new Article(['user_id' => $r->user()->id]);
        return $this->save($r, $article);
    }

    public function update(Request $r, Article $article)
    {
        abort_unless($this->canEdit($article), 403);
        return $this->save($r, $article);
    }

    private function save(Request $r, Article $article)
    {
        $data = $r->validate([
            'title' => 'required|string|max:250',
            'category_id' => 'nullable|exists:categories,id',
            'excerpt' => 'nullable|string|max:600',
            'body' => 'required|string',
            'image' => 'nullable|image|max:5120',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:320',
            'status' => 'required|in:draft,scheduled,published',
            'published_at' => 'nullable|date',
            'tags_text' => 'nullable|string|max:300',
            'tldr_text' => 'nullable|string|max:900',
        ] + ['meta_title' => 'nullable|string|max:70'] + \App\Support\SeoRules::rules(\App\Support\SeoRules::ARTICLE_SCHEMA_TYPES));
        if ($err = \App\Support\SeoRules::schemaError($data)) return back()->withInput()->withErrors(['schema_json' => $err]);

        // Users without the publish permission can only save drafts.
        if (! $r->user()->can('articles.publish')) $data['status'] = 'draft';
        if ($data['status'] === 'published' && empty($data['published_at'])) $data['published_at'] = $article->published_at ?? now();
        if ($data['status'] === 'scheduled' && empty($data['published_at'])) $data['published_at'] = now()->addHour();

        $data['slug'] = $article->exists && $article->slug ? $article->slug : Article::uniqueSlug($data['title']);
        $data['body'] = \App\Services\Editorial::cleanEditorHtml($data['body']);

        if ($r->hasFile('image')) {
            if ($article->image_path && ! Str::startsWith($article->image_path, 'http')) Storage::disk('public')->delete($article->image_path);
            $data['image_path'] = $this->storePublicUpload($r->file('image'), 'articles');
        }
        elseif ($lib = \App\Support\MediaRef::clean($r->input('image_library'))) {
            if ($article->image_path && ! Str::startsWith($article->image_path, 'http')) Storage::disk('public')->delete($article->image_path);
            $data['image_path'] = $lib;
        }
        unset($data['image']);
        $data['is_featured'] = $r->boolean('is_featured');
        $data = array_merge(array_diff_key($data, array_flip(['faq', 'tags_text', 'tldr_text'])), \App\Support\SeoRules::attributes($data), [
            'tags' => array_slice(array_values(array_filter(array_map(fn ($t) => Str::lower(trim($t)), explode(',', (string) ($data['tags_text'] ?? ''))))), 0, 10) ?: null,
            'tldr' => array_slice(array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['tldr_text'] ?? ''))))), 0, 3) ?: null,
        ]);

        $article->fill($data)->save();

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Blog post saved.');
    }

    private function storePublicUpload($file, string $module): string
    {
        $folder = public_path('uploads/'.$module.'/'.date('Y/m'));
        if (! is_dir($folder)) mkdir($folder, 0755, true);
        $name = uniqid($module.'_', true).'.'.$file->getClientOriginalExtension();
        $file->move($folder, $name);
        return asset('uploads/'.$module.'/'.date('Y/m').'/'.$name);
    }

    public function destroy(Article $article)
    {
        abort_unless(auth()->user()->can('articles.delete') && $this->canEdit($article), 403);
        if ($article->image_path && ! Str::startsWith($article->image_path, 'http')) Storage::disk('public')->delete($article->image_path);
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Blog post deleted.');
    }

    /**
     * Import articles from links (one or many, one per line).
     *   mode=ai    fetch, rewrite in our own words with the same facts, fact-check (needs the AI provider)
     *   mode=exact scrape the page exactly as published, no AI; the source is credited (ai_seo=1 adds AI meta/FAQ only)
     * The admin page sends one link per request (JSON) to show live progress; a plain form post loops over them.
     */
    public function importUrl(Request $r)
    {
        $d = $r->validate([
            'url' => 'nullable|string|max:700',
            'urls' => 'nullable|string|max:12000',
            'category_id' => 'nullable|exists:categories,id',
            'mode' => 'nullable|in:ai,exact',
        ]);
        $links = collect(preg_split('/[\s,]+/', trim(($d['urls'] ?? '')."\n".($d['url'] ?? '')), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($u) => trim($u))->filter(fn ($u) => filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u))->unique()->values();
        if ($links->isEmpty()) return $this->importReply($r, false, 'Paste at least one valid link (starting with http:// or https://).');
        if ($links->count() > 30) return $this->importReply($r, false, 'Please import at most 30 links at a time.');

        $mode = ($d['mode'] ?? 'ai') === 'exact' ? 'exact' : 'ai';
        $draft = $r->boolean('draft') || ! $r->user()->can('articles.publish');
        $publish = $draft ? false : ($mode === 'exact' ? true : null);   // null = the news desk's own auto-publish setting
        $aiSeo = $r->boolean('ai_seo');
        $cat = $d['category_id'] ?? null;
        @set_time_limit(280);
        $importer = new \App\Services\NewsImporter();

        $made = []; $failed = []; $deadline = microtime(true) + 240;
        foreach ($links as $u) {
            if (microtime(true) > $deadline) { $failed[] = "$u — skipped (time limit; import it again)"; continue; }
            $res = $importer->importUrl($u, $cat, $publish, $mode, $aiSeo);
            if ($res instanceof Article) { if ($r->user()->id && ! $res->user_id) $res->update(['user_id' => $r->user()->id]); $made[] = $res; }
            else $failed[] = "$u — $res";
        }

        if ($r->expectsJson()) {
            $a = $made[0] ?? null;
            return response()->json(['ok' => (bool) $a, 'message' => $a ? "Imported as {$a->status}." : ($failed[0] ?? 'Import failed.')]
                + ($a ? ['title' => $a->title, 'status' => $a->status, 'words' => str_word_count(strip_tags($a->body)), 'edit_url' => route('admin.articles.edit', $a)] : []), $a ? 200 : 422);
        }
        if (count($made) === 1 && ! $failed) return redirect()->route('admin.articles.edit', $made[0])->with('success', "Imported as {$made[0]->status}. Review and edit it below, then save.");
        $msg = count($made).' of '.$links->count().' articles imported.'.($failed ? ' Not imported: '.implode(' | ', $failed) : '');
        return redirect()->route('admin.articles.index')->with($made ? 'success' : 'error', $msg);
    }

    private function importReply(Request $r, bool $ok, string $message)
    {
        if ($r->expectsJson()) return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
        return back()->withInput()->with($ok ? 'success' : 'error', $message);
    }

    /** Draft an article from a topic/outline with the configured AI provider. */
    public function aiDraft(Request $r)
    {
        $r->validate(['topic' => 'required|string|max:500']);
        if (! AiClient::configured()) return response()->json(['error' => 'AI provider is not configured (Settings → AI).'], 422);

        $data = AiClient::json([
            ['role' => 'system', 'content' => AiClient::brief().' You are a senior writer. Write an original, accurate, engaging article on the given topic. Do not invent specific figures, dates or quotes you are not sure about. Reply with ONE JSON object only: {"title": "...", "excerpt": "max 200 chars", "body_html": "350-600 words using only <p>, <h2>, <ul>, <li>, <strong>", "meta_title": "max 60 chars", "meta_description": "max 155 chars", "meta_keywords": "6-10 comma separated keywords", "tldr": ["3 short takeaways"], "tags": ["4-6 lowercase tags"], "faq": [{"q": "", "a": ""}]} with exactly 3 faq items'],
            ['role' => 'user', 'content' => $r->topic],
        ], ['temperature' => 0.8, 'max_tokens' => 3000]);

        return $data ? response()->json($data) : response()->json(['error' => 'The AI provider did not return a usable draft. Try again.'], 502);
    }
    /** AI-written meta/keywords/FAQ/takeaways for an existing article; returns suggestions for the form to fill (nothing is saved). */
    public function aiSeo(Request $r, Article $article)
    {
        abort_unless($this->canEdit($article), 403);
        if (! AiClient::configured()) return response()->json(['error' => 'AI provider is not configured (Settings → AI).'], 422);
        $in = $r->validate(['title' => 'nullable|string|max:250', 'excerpt' => 'nullable|string|max:600', 'body' => 'nullable|string']);
        $s = \App\Services\ArticleSeo::suggest(['title' => $in['title'] ?? $article->title, 'excerpt' => $in['excerpt'] ?? $article->excerpt, 'body' => $in['body'] ?? $article->body]);

        return $s ? response()->json($s) : response()->json(['error' => 'The AI provider did not return usable suggestions. Try again.'], 502);
    }

    protected function bulkSearchable(): array { return ['title', 'excerpt', 'slug']; }

    protected function bulkFiltered(Request $r): ?\Illuminate\Database\Eloquent\Builder { return $this->filtered($r); }

    protected function bulkBase(Request $r): \Illuminate\Database\Eloquent\Builder
    {
        $u = $r->user();
        return Article::query()->when(! $u->can('articles.edit_all'), fn ($x) => $x->where('user_id', $u->id));
    }

    protected function bulkActions(Request $r): array
    {
        $u = $r->user(); $a = [];
        if ($u->can('articles.publish')) {
            $a['enable'] = ['label' => 'Enable (publish)', 'do' => fn (Article $m) => $m->update(['status' => 'published', 'published_at' => $m->published_at ?? now()]) || true];
            $a['disable'] = ['label' => 'Disable (move to draft)', 'do' => fn (Article $m) => $m->update(['status' => 'draft']) || true];
        }
        $a['category'] = ['label' => 'Change category', 'options' => array_merge([['', '— No category —']], Category::orderBy('name')->get()->map(fn ($c) => [(string) $c->id, $c->name])->all()),
            'do' => fn (Article $m, Request $r) => $m->update(['category_id' => $r->value ?: null]) || true];
        $a['set_featured'] = ['label' => 'Mark as Featured', 'do' => fn (Article $m) => $m->update(['is_featured' => true]) || true];
        $a['unset_featured'] = ['label' => 'Remove Featured mark', 'do' => fn (Article $m) => $m->update(['is_featured' => false]) || true];
        $a['ai_seo'] = ['label' => 'Generate missing SEO (AI)', 'confirm' => 'Fills only EMPTY meta title/description, keywords, takeaways, tags and FAQ. Existing text is never changed.', 'do' => fn (Article $m) => \App\Services\ArticleSeo::fillMissing($m)];
        if ($u->can('articles.delete')) {
            $a['delete'] = ['label' => 'Delete permanently', 'danger' => true, 'confirm' => 'This cannot be undone.', 'do' => function (Article $m) {
                if ($m->image_path && ! Str::startsWith($m->image_path, 'http')) Storage::disk('public')->delete($m->image_path);
                return (bool) $m->delete();
            }];
        }
        return $a;
    }
}
