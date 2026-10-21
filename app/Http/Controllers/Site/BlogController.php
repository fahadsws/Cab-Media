<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Public blog: listing, category filter, article page and RSS feed. Content is managed in Admin > Blogs. */
class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $r, ?Category $category = null)
    {
        $q = trim((string) $r->query('q', ''));
        $filtered = $q !== '' || $category !== null;

        $featured = null;
        if (! $filtered && $r->integer('page', 1) <= 1) {
            $featured = Article::published()->with('category')->where('is_featured', true)->latest('published_at')->first()
                ?? Article::published()->with('category')->latest('published_at')->first();
        }

        $posts = Article::published()->with('category')
            ->when($category, fn ($x) => $x->where('category_id', $category->id))
            ->when($q !== '', fn ($x) => $x->where(fn ($y) => $y->where('title', 'like', "%$q%")->orWhere('excerpt', 'like', "%$q%")->orWhere('body', 'like', "%$q%")))
            ->when($featured, fn ($x) => $x->where('id', '!=', $featured->id))
            ->latest('published_at')->paginate(self::PER_PAGE)->withQueryString();

        $categories = Category::where('is_active', true)->withCount(['articles as published_count' => fn ($x) => $x->published()])->orderBy('name')->get()->filter(fn ($c) => $c->published_count > 0);

        return view('site.blog.index', compact('posts', 'featured', 'categories', 'category', 'q'));
    }

    public function show(string $slug)
    {
        $article = Article::with(['category', 'author'])->where('slug', $slug)->firstOrFail();
        if ($article->duplicate_of && $main = Article::find($article->duplicate_of)) return redirect($main->url, 301);
        abort_unless($article->status === 'published' && $article->published_at && $article->published_at->lte(now()), 404);

        $this->countView($article);

        $related = Article::published()->with('category')->where('id', '!=', $article->id)
            ->when($article->category_id, fn ($x) => $x->orderByRaw('category_id = ? desc', [$article->category_id]))
            ->latest('published_at')->take(3)->get();

        return view('site.blog.show', compact('article', 'related'));
    }

    public function feed()
    {
        $items = Article::published()->latest('published_at')->take(30)->get();
        $base = rtrim(config('site.url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Cab Media Blog</title><link>'.e($base.'/blog').'</link><description>News and ideas from Cab Media</description><language>en</language>';
        foreach ($items as $a) {
            $xml .= '<item><title>'.e($a->title).'</title><link>'.e($base.'/blog/'.$a->slug).'</link><guid>'.e($base.'/blog/'.$a->slug).'</guid><pubDate>'.$a->published_at->toRssString().'</pubDate><description>'.e((string) $a->excerpt).'</description></item>';
        }

        return response($xml.'</channel></rss>', 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /** One view per visitor per article every 30 minutes (keeps the admin "top posts" honest). */
    private function countView(Article $a): void
    {
        $ua = strtolower((string) request()->userAgent());
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview/', $ua) || auth()->check()) return;
        if (! Cache::add('blogview.'.request()->ip().'.'.$a->id, 1, 1800)) return;

        $a->increment('views');
        $day = now()->toDateString();
        $updated = DB::table('article_views_daily')->where('article_id', $a->id)->where('day', $day)->increment('views');
        if (! $updated) DB::table('article_views_daily')->insert(['article_id' => $a->id, 'day' => $day, 'views' => 1]);
    }
}
