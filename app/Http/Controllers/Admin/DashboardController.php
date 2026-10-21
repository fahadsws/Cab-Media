<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AutomationLog;
use App\Models\NewsSource;
use App\Models\Page;
use App\Services\AiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $perDay = Article::where('created_at', '>=', $days->first())->get()->groupBy(fn ($a) => $a->created_at->format('Y-m-d'));

        $trending = fn (string $from) => Schema::hasTable('article_views_daily')
            ? DB::table('article_views_daily')->where('day', '>=', $from)->selectRaw('article_id, SUM(views) v')->groupBy('article_id')->orderByDesc('v')->limit(5)->pluck('v', 'article_id')
            : collect();
        $top = function ($rows) {
            $arts = Article::with('category')->whereIn('id', $rows->keys())->get()->keyBy('id');
            return $rows->map(fn ($v, $id) => $arts->get($id) ? ['article' => $arts[$id], 'views' => (int) $v] : null)->filter()->values();
        };

        return view('admin.dashboard', [
            'topToday' => $top($trending(now()->toDateString())),
            'topWeek' => $top($trending(now()->subDays(6)->toDateString())),
            'topAll' => Article::with('category')->where('views', '>', 0)->orderByDesc('views')->take(5)->get()->map(fn ($a) => ['article' => $a, 'views' => (int) $a->views]),
            'stats' => [
                'articles' => Article::count(),
                'published' => Article::where('status', 'published')->count(),
                'ai_articles' => Article::where('is_ai_generated', true)->count(),
                'drafts' => Article::whereIn('status', ['draft', 'scheduled'])->count(),
                'sources' => NewsSource::where('is_active', true)->count(),
                'pages' => Page::count(),
            ],
            'chart' => [
                'labels' => $days->map->format('d M')->all(),
                'articles' => $days->map(fn ($d) => $perDay->get($d->format('Y-m-d'), collect())->count())->all(),
            ],
            'recentArticles' => Article::with('category')->when(! $user->can('articles.edit_all'), fn ($q) => $q->where('user_id', $user->id))->latest()->take(6)->get(),
            'logs' => $user->can('automation.manage') ? AutomationLog::latest('id')->take(6)->get() : collect(),
            'health' => [
                'AI provider' => AiClient::configured(),
                'Blog sources added' => NewsSource::count() > 0,
                'Automation ran in last 24h' => AutomationLog::where('created_at', '>=', now()->subDay())->exists(),
            ],
        ]);
    }
}
