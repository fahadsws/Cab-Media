<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\NewsSource;
use App\Models\Setting;
use App\Services\Crawl\Fetcher;
use App\Services\Crawl\LinkFinder;
use App\Services\Crawl\SourceHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * News desk pipeline:
 *   sources (RSS / listing pages) -> candidate stories -> cluster the same story across outlets
 *   -> fetch + clean the full article text -> Editorial rewrites it (same facts, new words; quality/fact-gated)
 *   -> images stored locally -> published.
 *
 * Two modes (Settings -> News automation, `news.mode`):
 *   ai     rewrite every story with the AI writing desk (default)
 *   scrape copy the page as published (no AI), crediting the source; AI is optional and only fills missing SEO fields
 *
 * The same pipeline serves "import from link" (importUrl) for a single article URL.
 */
class NewsImporter
{
    private array $log = [];
    private ?\Illuminate\Support\Collection $recent = null;
    private const DUPLICATE = 'duplicate';

    private const MIN_WORDS = 70;   // below this the AI would have to invent content

    /** 'ai' (rewrite with the AI desk) or 'scrape' (copy the page as published, no AI). */
    public static function mode(): string
    {
        return Setting::get('news.mode', 'ai') === 'scrape' ? 'scrape' : 'ai';
    }

    public function run(?int $limit = null, ?int $onlySourceId = null): string
    {
        $limit ??= (int) Setting::get('news.per_run', 3);
        if (self::mode() === 'ai' && ! AiClient::configured()) return 'Skipped: AI provider is not configured (Settings → AI). Switch News automation to "Scrape only" to run without AI.';

        // A manual run and the scheduler must not import the same story twice.
        $lock = Cache::lock('news.run', 290);
        if (! $lock->get()) return 'Skipped: another news run is already in progress.';
        try { return $this->runLocked($limit, $onlySourceId); } finally { $lock->release(); }
    }

    private function runLocked(int $limit, ?int $onlySourceId): string
    {
        $forced = (bool) $onlySourceId;
        $deadline = time() + 230;     // each article takes an AI round-trip; stay inside the 280 s request/run limit
        $sources = NewsSource::with('category')->where('is_active', true)
            ->when($onlySourceId, fn ($q) => $q->where('id', $onlySourceId))
            ->orderBy('last_fetched_at')->get();

        // 1. Collect candidate stories from every source that is healthy enough to try.
        $items = []; $cooling = []; $fetched = []; $importedBy = [];
        foreach ($sources as $s) {
            if (! $forced && SourceHealth::coolingDown($s)) { $cooling[] = $s->name; continue; }
            try {
                $found = $this->candidates($s);
                SourceHealth::ok($s, count($found).' new', count($found));
                $fetched[$s->id] = $s;
                foreach ($found as $i) $items[] = $i + ['source' => $s];
            } catch (\Throwable $e) {
                SourceHealth::fail($s, $e->getMessage());
                $this->log[] = "{$s->name}: ".Str::limit($e->getMessage(), 90);
            }
        }

        // 2. Cluster the same story reported by several outlets; multi-outlet stories are the most newsworthy.
        $this->label($items);
        $clusters = $this->cluster($items);
        usort($clusters, fn ($a, $b) => count($b) <=> count($a));

        $done = 0; $failed = 0; $skipped = 0; $aiFailures = 0; $tried = 0;
        foreach ($clusters as $cluster) {
            if ($done >= $limit || $failed >= 6) break;
            if (time() > $deadline) { $this->log[] = 'stopped at the time budget, the rest will import next run'; break; }
            if ($existing = $this->findExisting($cluster)) { $this->mergeInto($existing, $cluster); $skipped++; $this->log[] = "merged into #{$existing->id}: ".Str::limit($existing->title, 45); continue; }

            $tried++;
            $res = $this->import($cluster);
            if ($res === self::DUPLICATE) { $skipped++; continue; }
            if ($res instanceof Article) { $done++; $importedBy[$res->news_source_id] = ($importedBy[$res->news_source_id] ?? 0) + 1; continue; }
            $failed++;
            $this->remember($cluster, str_starts_with($res, 'AI ') ? 0 : 1);          // do not blacklist a story because the AI was down
            if (str_starts_with($res, 'AI ')) $aiFailures++;
            $this->log[] = $res;
        }

        $this->reconcileCounts($fetched, $items, $importedBy);

        $summary = "Imported $done article(s) from ".count($clusters)." story cluster(s), $failed failed, $skipped duplicate(s) skipped"
            .($cooling ? '. Cooling down: '.implode(', ', $cooling) : '')
            .($this->log ? '. '.implode(' | ', array_slice($this->log, 0, 4)) : '');

        // A run that found stories but could not write any because the AI is failing must not look healthy.
        if ($done === 0 && $tried > 0 && $aiFailures === $failed) return 'Error: AI provider is failing - '.$summary;
        return $summary;
    }

    /**
     * The per-source "N new" shown in admin is the number of stories still waiting to be imported. It is worked out at discovery
     * time, so after the run it must be recomputed: imported, duplicate and permanently skipped stories are no longer "new".
     */
    private function reconcileCounts(array $fetched, array $items, array $importedBy): void
    {
        foreach ($fetched as $id => $s) {
            $waiting = collect($items)->filter(fn ($i) => $i['source']->id === $id)
                ->filter(fn ($i) => ! Cache::has('news.skip.'.sha1($i['link'])) && ! Article::where('source_hash', sha1($i['link']))->exists())->count();
            $imp = $importedBy[$id] ?? 0;
            SourceHealth::ok($s, $waiting.' new'.($imp ? ", $imp imported this run" : ''), $waiting);
        }
    }

    /* ---------------------------- discovery ---------------------------- */

    /** @return array<int,array{title:string,link:string,summary:string,content:string,image:?string,date:?\Carbon\Carbon}> */
    private function candidates(NewsSource $s): array
    {
        $items = $s->type === 'page' ? $this->fromPage($s) : $this->fromRss($s);
        if (! $items) throw new \RuntimeException($s->type === 'page' ? 'No story links found on the page (layout changed?)' : 'Feed contains no items');

        $maxAge = max(1, (int) Setting::get('news.max_age_days', 3));
        $out = []; $seen = [];
        foreach ($items as $i) {
            $i['link'] = $this->clean($i['link']);
            if (! $i['link'] || isset($seen[$i['link']])) continue;
            $seen[$i['link']] = true;
            if (Cache::has('news.skip.'.sha1($i['link'])) || Article::where('source_hash', sha1($i['link']))->exists()) continue;
            if (! empty($i['date']) && $i['date']->lt(now()->subDays($maxAge))) continue;     // old story
            $out[] = $i;
        }
        return array_slice($out, 0, 12);
    }

    private function clean(string $url): string
    {
        return LinkFinder::normalize(trim($url));
    }

    private function remember(array $cluster, int $days): void
    {
        if ($days <= 0) return;
        foreach ($cluster as $i) Cache::put('news.skip.'.sha1($i['link']), 1, now()->addDays($days));
    }

    /**
     * One very short AI call labels every candidate headline with a story key ("rbi-repo-rate:update") so the same story from
     * different outlets is recognised however each one words its headline. Headlines about no single subject get no key.
     * Skipped when no AI provider is set up (scrape-only mode): duplicates are then caught by headline matching alone.
     */
    private function label(array &$items): void
    {
        if (! $items || ! AiClient::configured()) return;
        $list = collect($items)->values()->map(fn ($i, $n) => ($n + 1).'. '.Str::limit($i['title'], 110, ''))->implode("\n");
        $out = AiClient::json([
            ['role' => 'system', 'content' => 'For each news headline give a story key "subject-topic:event": lowercase hyphenated company/product + topic (e.g. openai-gpt-pricing, google-gemini-launch), event one of update (a price, result, release or policy change) or event (any other one-off announcement). If it is not about one specific subject use "none". Reply JSON only: {"1":"openai-gpt-pricing:update"}'],
            ['role' => 'user', 'content' => $list],
        ], ['temperature' => 0, 'max_tokens' => 40 + 14 * count($items), 'timeout' => 40]);
        foreach (array_keys($items) as $n => $k) {
            $key = Str::lower(trim((string) ($out[(string) ($n + 1)] ?? '')));
            $items[$k]['key'] = preg_match('/^[a-z0-9-]{3,}:(event|update)$/', $key) ? $key : null;
        }
        if (! $out) $this->log[] = 'story pre-check unavailable, using headline matching';
    }

    /**
     * An already-published article covering the same story: by story key first (reliable), then by headline/number similarity
     * against every outlet headline remembered in source_links.
     *
     * @param array<int,array> $cluster items (title, summary, link, key)
     */
    private function findExisting(array $cluster, string $text = ''): ?Article
    {
        $this->recent ??= Article::whereNull('duplicate_of')->where('created_at', '>=', now()->subDays(21))->latest('id')->limit(400)
            ->get(['id', 'title', 'excerpt', 'source_title', 'source_links', 'story_key', 'created_at']);
        foreach ($this->recent as $a) {
            foreach ($cluster as $i) {
                if (Article::sameStory($i['key'] ?? null, $a->story_key) && $a->created_at->gte(now()->subDays(Article::storyWindowDays($a->story_key)))) return $a;
            }
            if ($a->created_at->lt(now()->subDays(7))) continue;
            $titles = array_filter([$a->title, $a->source_title, ...array_column((array) $a->source_links, 'title')]);
            foreach ($cluster as $i) {
                foreach ($titles as $t) {
                    if (TextTools::storySimilarity((string) $i['title'], (string) $t, ($i['summary'] ?? '').' '.$text, (string) $a->excerpt) >= 0.55) return $a;
                }
            }
        }
        return null;
    }

    /**
     * A late outlet reporting a story we already have is merged into that article (one strong page per story, better for SEO than
     * several near-identical ones): credited in source_links and, when it carries at least 3 facts the article lacks, the article is
     * rewritten from all the reports. Title and URL stay as they are.
     *
     * @param array|null $sources already-fetched texts for this cluster (title, text, outlet, url), if any
     */
    private function mergeInto(Article $article, array $cluster, ?array $sources = null): void
    {
        $links = (array) $article->source_links;
        $urls = array_column($links, 'url');
        $fresh = [];
        foreach ($cluster as $i) {
            if (in_array($i['link'], $urls, true)) continue;
            $fresh[] = $i;
            $links[] = ['outlet' => $i['source']->name, 'url' => $i['link'], 'title' => $i['title']];
        }
        $this->remember($cluster, 30);
        if (! $fresh) return;
        $article->forceFill(['source_links' => $links])->saveQuietly();
        if (self::mode() === 'scrape' || ! $article->is_ai_generated || ! AiClient::configured()) return;   // copied or hand-written articles are never rewritten

        try {
            $new = $sources ?? $this->fetchSources($fresh);
            $have = TextTools::numberFacts(TextTools::plain((string) Article::whereKey($article->id)->value('body')));
            $extra = $new ? count(array_diff_key(TextTools::numberFacts(implode(' ', array_column($new, 'text'))), $have)) : 0;
            if ($extra < 3 || count($links) > 5) return;

            $old = $this->fetchSources(array_map(fn ($l) => ['title' => $l['title'] ?? $article->source_title, 'link' => $l['url'], 'source' => (object) ['name' => $l['outlet']]], array_slice($links, 0, 2)));
            $res = Editorial::write(array_slice([...$new, ...$old], 0, 3));
            if (isset($res['error'])) return;
            $d = $res['data'];
            $article->forceFill([
                'body' => $d['body_html'],
                'excerpt' => Str::limit($d['excerpt'] ?? $article->excerpt, 300, '…'),
                'tldr' => array_slice(array_map('strval', (array) ($d['tldr'] ?? [])), 0, 3) ?: $article->tldr,
                'faq' => ($d['faq'] ?? null) ?: $article->faq,
                'meta_description' => Str::limit($d['meta_description'] ?? $article->meta_description, 160, ''),
                'similarity' => $res['similarity'],
            ])->save();      // bumps updated_at -> dateModified + sitemap lastmod
            $this->log[] = "updated #{$article->id} with facts from ".count($fresh).' more outlet(s)';
        } catch (\Throwable $e) {
            $this->log[] = 'merge refresh failed: '.Str::limit($e->getMessage(), 70);
        }
    }

    private function mergedMessage(Article $existing, array $item, ?array $sources = null): string
    {
        $this->mergeInto($existing, $item, $sources);
        return "Not imported: we already cover this story. It was merged into the existing article (#{$existing->id}) '".Str::limit($existing->title, 70)."' and credited as a source.";
    }

    /** @return array<int,array{title:string,text:string,outlet:string,url:string}> */
    private function fetchSources(array $items): array
    {
        $out = [];
        foreach (array_slice($items, 0, 2) as $i) {
            if (! Robots::allowed($i['link'])) continue;
            $page = PageFetcher::article($i['link']);
            if ($page && $page['words'] >= self::MIN_WORDS) $out[] = ['title' => $i['title'] ?: $page['title'], 'text' => $page['text'], 'outlet' => $i['source']->name, 'url' => $i['link']];
        }
        return $out;
    }

    /** @return array<int, array<int,array>> groups of items describing the same story (one item per outlet) */
    private function cluster(array $items): array
    {
        $clusters = [];
        foreach ($items as $item) {
            $item['title'] = $item['title'] ?: Str::headline(preg_replace('/[-_]\d+$|\.htm.*$/', '', basename((string) parse_url($item['link'], PHP_URL_PATH))));
            foreach ($clusters as &$c) {
                $sameOutlet = collect($c)->contains(fn ($x) => $x['source']->id === $item['source']->id);
                // compare with every member, not just the first, so A~B and B~C end up in one cluster
                $match = collect($c)->contains(fn ($x) => Article::sameStory($item['key'] ?? null, $x['key'] ?? null)
                    || TextTools::storySimilarity($item['title'], $x['title'], $item['summary'] ?? '', $x['summary'] ?? '') >= 0.5);
                if (! $sameOutlet && $match) { $c[] = $item; continue 2; }
            }
            unset($c);
            $clusters[] = [$item];
        }
        return $clusters;
    }

    private function fromRss(NewsSource $s): array
    {
        $res = Fetcher::get($s->feed_url, ['accept' => 'application/rss+xml,application/atom+xml,application/xml;q=0.9,text/xml;q=0.8,*/*;q=0.5']);
        if (! $res['ok']) throw new \RuntimeException($res['error'] ?? 'Fetch failed');

        libxml_use_internal_errors(true);
        $body = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $res['body']) ?? $res['body'];
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_PARSEHUGE);
        if (! $xml) {
            // The URL returned a web page instead of a feed: use the feed it advertises, or fall back to reading it as a page.
            $advertised = LinkFinder::feedLinks($res['body'], $res['url'])[0] ?? null;
            if ($advertised && $advertised !== $s->feed_url) {
                $feed = clone $s; $feed->feed_url = $advertised;
                return $this->fromRss($feed);
            }
            if (stripos($res['body'], '<html') !== false) return $this->itemsFromPageHtml($res['body'], $res['url'], $s);
            throw new \RuntimeException('Invalid feed XML');
        }

        $out = [];
        foreach ($xml->channel->item ?? $xml->entry ?? [] as $n) {
            $ns = $n->getNamespaces(true);
            $content = isset($ns['content']) ? (string) $n->children($ns['content'])->encoded : (string) ($n->content ?? '');
            $image = null;
            if (isset($ns['media'])) {
                $m = $n->children($ns['media']);
                $image = (string) ($m->content?->attributes()['url'] ?? $m->thumbnail?->attributes()['url'] ?? '') ?: null;
            }
            if (! $image && isset($n->enclosure)) $image = (string) $n->enclosure->attributes()['url'];
            $desc = (string) ($n->description ?? $n->summary);
            $dc = isset($ns['dc']) ? (string) $n->children($ns['dc'])->date : '';
            $date = Crawl\ArticleExtractor::date((string) ($n->pubDate ?? $n->published ?? $n->updated ?? '') ?: $dc);
            $link = (string) ($n->link['href'] ?? $n->link);
            if ($link === '' && isset($n->link)) foreach ($n->link as $l) { if ((string) $l['rel'] !== 'self' && (string) $l['href']) { $link = (string) $l['href']; break; } }
            $out[] = [
                'title' => trim(html_entity_decode((string) $n->title, ENT_QUOTES | ENT_HTML5)),
                'link' => trim($link),
                'summary' => Crawl\ArticleExtractor::normalize(strip_tags($desc)),
                'content' => trim(strip_tags(preg_replace('#</(p|div|li|h\d)>#i', "\n\n", $content))),
                'image' => $image ?: (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content ?: $desc, $m2) ? $m2[1] : null),
                'date' => $date,
            ];
        }
        return $out;
    }

    private function fromPage(NewsSource $s): array
    {
        if (! Robots::allowed($s->feed_url)) throw new \RuntimeException('Blocked by robots.txt');
        $page = Fetcher::get($s->feed_url);
        if (! $page['ok']) throw new \RuntimeException($page['error'] ?? 'Fetch failed');
        return $this->itemsFromPageHtml($page['body'], $page['url'], $s);
    }

    private function itemsFromPageHtml(string $html, string $base, NewsSource $s): array
    {
        return array_map(fn ($l) => ['title' => $l['title'], 'link' => $l['link'], 'summary' => '', 'content' => '', 'image' => null, 'date' => Crawl\ArticleExtractor::date(Crawl\ArticleExtractor::dateFromUrl($l['link']))],
            LinkFinder::articleLinks($html, $base, $s->link_pattern ?: null));
    }

    /* ----------------------------- writing ----------------------------- */

    /** @return Article|string  the article, or a short failure reason */
    private function import(array $cluster): Article|string
    {
        $primary = $cluster[0];
        if (self::mode() === 'scrape') return $this->importScraped($cluster);
        $maxAge = max(1, (int) Setting::get('news.max_age_days', 3));
        $sources = []; $images = []; $links = []; $alts = [];
        foreach (array_slice($cluster, 0, 3) as $i) {
            $text = trim($i['content'] ?: $i['summary']);
            $page = null;
            if (mb_strlen($text) < 2500 && Robots::allowed($i['link'])) {
                $page = PageFetcher::article($i['link']);
                if ($page) {
                    if ($page['published_at'] && $page['published_at']->lt(now()->subDays($maxAge))) { Cache::put('news.skip.'.sha1($i['link']), 1, now()->addDays(30)); continue; }
                    if ($page['words'] >= 120 && mb_strlen($page['text']) * 1.3 >= mb_strlen($text)) $text = $page['text'];
                    elseif (mb_strlen($page['text']) > mb_strlen($text)) $text = $page['text'];
                    if (! $i['title']) $i['title'] = $page['title'];
                }
            }
            if (TextTools::wordCount($text) < self::MIN_WORDS || ! $i['title']) continue;
            $sources[] = ['title' => $i['title'], 'text' => $text, 'outlet' => $i['source']->name, 'url' => $i['link']];
            $links[] = ['outlet' => $i['source']->name, 'url' => $i['link'], 'title' => $i['title']];
            foreach (array_filter([$i['image'] ?? null, ...($page['images'] ?? [])]) as $img) $images[] = $img;
            $alts += $page['image_alts'] ?? [];
        }
        if (! $sources) return 'Too little text: '.Str::limit($primary['link'], 60);

        // With the full text in hand (prices, specs, dates) a differently worded duplicate is easy to spot, before paying for the rewrite.
        $full = array_map(fn ($s, $l) => ['title' => $s['title'], 'summary' => mb_substr($s['text'], 0, 1500), 'link' => $s['url'], 'source' => $cluster[0]['source'], 'key' => $primary['key'] ?? null] + $l, $sources, $links);
        if ($existing = $this->findExisting($full)) { $this->mergeInto($existing, $cluster, $sources); $this->log[] = "merged into #{$existing->id}: ".Str::limit($existing->title, 45); return self::DUPLICATE; }

        $res = Editorial::write($sources);
        if (isset($res['error'])) return $res['error'].': '.Str::limit($sources[0]['title'], 50);

        // Safety net: the writer has now identified the story. If we already cover it, merge instead of publishing a twin.
        $story = (array) ($res['data']['story'] ?? []);
        if ($key = Article::storyKey($story['subject'] ?? null, $story['topic'] ?? null, $story['event'] ?? null)) {
            $twin = $this->findExisting([['title' => $sources[0]['title'], 'link' => $primary['link'], 'key' => $key]]);
            if ($twin) { $this->mergeInto($twin, $cluster); $this->log[] = "merged into #{$twin->id} (same story): ".Str::limit($twin->title, 40); return self::DUPLICATE; }
        }

        $article = $this->createArticle($res, $sources, $links, $images, $alts, $primary['source'], $primary['link']);
        $this->remember(array_slice($cluster, 1), 30);
        $this->recent?->prepend($article);
        return $article;
    }

    /**
     * Scrape-only mode for the scheduled desk: no AI rewrite. The first story of the cluster that can be scraped is saved as
     * published (text, tables and pictures are the source's own, the source is credited) and the other outlets' links are remembered.
     *
     * @return Article|string
     */
    private function importScraped(array $cluster): Article|string
    {
        $maxAge = max(1, (int) Setting::get('news.max_age_days', 3));
        $last = 'Too little text: '.Str::limit($cluster[0]['link'], 60);
        foreach (array_slice($cluster, 0, 3) as $i) {
            if (! Robots::allowed($i['link'])) { $last = 'Blocked by robots.txt: '.Str::limit($i['link'], 60); continue; }
            $res = $this->importExact($i['link'], null, Setting::bool('news.auto_publish', true), Setting::bool('news.scrape_ai_seo', false) && AiClient::configured(), $i['source']);
            if (is_string($res)) {
                $last = $res.': '.Str::limit($i['link'], 50);
                if (str_starts_with($res, 'Already imported')) return self::DUPLICATE;
                continue;
            }
            if ($res->published_at && $res->published_at->lt(now()->subDays($maxAge)) && $res->published_at->lt(now()->subMinute())) {
                $res->delete();
                Cache::put('news.skip.'.sha1($i['link']), 1, now()->addDays(30));
                $last = 'Old story skipped: '.Str::limit($i['link'], 50);
                continue;
            }
            $links = array_map(fn ($c) => ['outlet' => $c['source']->name, 'url' => $c['link'], 'title' => $c['title']], $cluster);
            $res->update(['source_links' => $links]);
            $this->remember(array_slice($cluster, 1), 30);
            $this->recent?->prepend($res);
            return $res;
        }
        return $last;
    }

    /**
     * Import ONE article from a link (admin "import from link", `crawl:url`). Same cleaning, rewrite and quality gates as the
     * scheduled desk; the page itself is the only source.
     *
     * @return Article|string  the article, or the reason it could not be imported
     */
    public function importUrl(string $url, ?int $categoryId = null, ?bool $publish = null, string $mode = 'ai', bool $aiSeo = false): Article|string
    {
        if ($mode === 'exact') return $this->importExact($url, $categoryId, $publish, $aiSeo);
        $url = LinkFinder::normalize(trim($url));
        if (! $url) return 'That is not a valid link.';
        if (! AiClient::configured()) return 'AI provider is not configured (Settings → AI). Use "Exact copy" to import without AI.';
        if ($a = Article::where('source_hash', sha1($url))->first()) return "Already imported: {$a->title}";

        $page = PageFetcher::article($url);
        if (! $page) return 'Could not open that page (blocked, offline or not HTML).';
        if ($page['canonical'] && $page['canonical'] !== $url && ($a = Article::where('source_hash', sha1(LinkFinder::normalize($page['canonical'])))->first())) return "Already imported: {$a->title}";
        if ($page['words'] < self::MIN_WORDS || ! $page['title']) return 'No readable article text found on that page ('.$page['words'].' words).';

        $outlet = $page['site'] ?: Str::headline(explode('.', LinkFinder::host($url))[0]);
        $sources = [['title' => $page['title'], 'text' => $page['text'], 'outlet' => $outlet, 'url' => $url]];

        // The same story from another outlet is merged into the article we already have, never added as a twin.
        $item = [['title' => $page['title'], 'summary' => mb_substr($page['text'], 0, 1500), 'link' => $url, 'source' => (object) ['id' => 0, 'name' => $outlet]]];
        $this->label($item);
        if ($existing = $this->findExisting($item)) return $this->mergedMessage($existing, $item, $sources);

        $res = Editorial::write($sources);
        if (isset($res['error'])) return $res['error'];

        $story = (array) ($res['data']['story'] ?? []);
        if (($key = Article::storyKey($story['subject'] ?? null, $story['topic'] ?? null, $story['event'] ?? null)) && ($twin = $this->findExisting([['title' => $page['title'], 'link' => $url, 'key' => $key]]))) {
            return $this->mergedMessage($twin, $item);
        }

        return $this->createArticle($res, $sources, [['outlet' => $outlet, 'url' => $url, 'title' => $page['title']]], $page['images'], $page['image_alts'], null, $url, $categoryId, $publish);
    }

    /**
     * "Exact copy" import: the page is scraped as published (Crawl\RichArticle) and saved without any AI rewriting, so the text,
     * numbers, tables and pictures are the source's own. The source is credited on the article. With $aiSeo the AI only fills the
     * EMPTY meta/FAQ/takeaway fields (the body is never touched).
     *
     * @return Article|string  the article, or the reason it could not be imported
     */
    public function importExact(string $url, ?int $categoryId = null, ?bool $publish = null, bool $aiSeo = false, ?NewsSource $source = null): Article|string
    {
        $url = LinkFinder::normalize(trim($url));
        if (! $url) return 'That is not a valid link.';
        if ($a = Article::where('source_hash', sha1($url))->first()) return "Already imported: {$a->title}";

        $page = \App\Services\Crawl\RichArticle::scrape($url);
        if (is_string($page)) return $page;
        if ($page['canonical'] && $page['canonical'] !== $url && ($a = Article::where('source_hash', sha1(LinkFinder::normalize($page['canonical'])))->first())) return "Already imported: {$a->title}";

        $outlet = $page['site'] ?: Str::headline(explode('.', LinkFinder::host($url))[0]);

        // Pictures: the cover first, then every picture of the body is copied to our storage (a failed one is dropped with its caption).
        $hashes = []; $cover = null;
        foreach (array_slice($page['images'], 0, 6) as $img) {
            $got = ImageStore::fetch($img, 'articles', $hashes);
            if ($got) { $cover = $got['path']; $hashes[] = $got['hash']; break; }
        }
        $body = preg_replace_callback('#<figure><img src="([^"]+)"[^>]*>(?:<figcaption>.*?</figcaption>)?</figure>#is', function ($m) use (&$hashes) {
            static $n = 0;
            if (++$n > 15) return '';
            $got = ImageStore::fetch(html_entity_decode($m[1]), 'articles', $hashes);
            if (! $got) return '';
            $hashes[] = $got['hash'];
            return str_replace($m[1], e($got['path']), $m[0]);
        }, $page['html']);
        $body = Editorial::cleanEditorHtml($body);

        $plain = TextTools::plain($body);
        $desc = trim($page['description']) !== '' ? $page['description'] : $plain;
        $tags = array_slice($page['tags'], 0, 6);
        $category = ($categoryId ? Category::find($categoryId) : null) ?? $source?->category ?? $this->guessCategory($page['section'], $tags, $page['title']) ?? Category::first();

        $article = Article::create([
            'category_id' => $category?->id,
            'news_source_id' => $source?->id,
            'title' => Str::limit($page['title'], 250, ''),
            'slug' => Article::uniqueSlug($page['title']),
            'excerpt' => Str::limit($desc, 300, '…'),
            'body' => $body,
            'tags' => $tags ?: null,
            'image_path' => $cover,
            'meta_title' => Str::limit($page['title'], 70, ''),
            'meta_description' => Str::limit($desc, 160, ''),
            'meta_keywords' => $tags ? Str::limit(implode(', ', $tags), 300, '') : null,
            'robots' => in_array($rb = Setting::get('seo.article_default_robots'), array_keys(\App\Support\SeoRules::ROBOTS), true) ? $rb : 'index,follow',
            'schema_type' => in_array($st = Setting::get('seo.article_default_schema'), array_keys(\App\Support\SeoRules::ARTICLE_SCHEMA_TYPES), true) ? $st : 'BlogPosting',
            'status' => ($publish ?? false) ? 'published' : 'draft',
            'published_at' => $page['published_at'] && $page['published_at']->lte(now()) ? $page['published_at'] : now(),
            'source_url' => $url,
            'source_title' => Str::limit($page['title'], 290, ''),
            'source_hash' => sha1($url),
            'source_links' => [['outlet' => $outlet, 'url' => $url, 'title' => $page['title']]],
            'is_ai_generated' => false,
        ]);

        if ($aiSeo && AiClient::configured()) { try { ArticleSeo::fillMissing($article->fresh()); } catch (\Throwable) {} }
        return $article->fresh();
    }

    /** Match the page's section / tags / title to one of our categories by name. */
    private function guessCategory(?string $section, array $tags, string $title): ?Category
    {
        $hay = mb_strtolower(trim(($section ?? '').' '.implode(' ', $tags).' '.$title));
        foreach (Category::where('is_active', true)->get() as $c) {
            $n = mb_strtolower(trim($c->name));
            if ($n !== '' && preg_match('/(^|[^\p{L}])'.preg_quote($n, '/').'s?($|[^\p{L}])/u', $hay)) return $c;
        }
        return null;
    }

    private function createArticle(array $res, array $sources, array $links, array $images, array $alts, ?NewsSource $source, string $primaryLink, ?int $categoryId = null, ?bool $publish = null): Article
    {
        $data = $res['data'];
        $category = ($categoryId ? Category::find($categoryId) : null)
            ?? Category::where('is_active', true)->where('name', $data['category'] ?? '')->first() ?? $source?->category ?? Category::first();
        $images = array_values(array_unique($images));
        // First image that downloads is the cover; up to 3 more different ones go into the article body.
        $cover = null; $figures = []; $hashes = [];
        foreach (array_slice($images, 0, 10) as $img) {
            if (count($figures) >= 3) break;
            $got = ImageStore::fetch($img, 'articles', $hashes);
            if (! $got) continue;
            $hashes[] = $got['hash'];
            if (! $cover) $cover = $got['path']; else $figures[] = [$got['path'], $alts[$img] ?? ''];
        }

        $faq = array_slice(array_values(array_filter((array) ($data['faq'] ?? []), fn ($f) => ! empty($f['q']) && ! empty($f['a']))), 0, 5);
        $article = Article::create([
            'category_id' => $category?->id,
            'news_source_id' => $source?->id,
            'title' => Str::limit($data['title'], 250, ''),
            'slug' => Article::uniqueSlug($data['title']),
            'excerpt' => Str::limit($data['excerpt'] ?? TextTools::plain($data['body_html']), 300, '…'),
            'body' => Editorial::withImages($data['body_html'], $figures),
            'tldr' => array_slice(array_map('strval', (array) ($data['tldr'] ?? [])), 0, 3) ?: null,
            'faq' => $faq ?: null,
            'tags' => array_slice(array_map(fn ($t) => Str::lower(trim((string) $t)), (array) ($data['tags'] ?? [])), 0, 6) ?: null,
            'image_path' => $cover,
            'meta_title' => Str::limit($data['meta_title'] ?? $data['title'], 70, ''),
            'meta_description' => Str::limit($data['meta_description'] ?? '', 160, ''),
            'meta_keywords' => Str::limit(implode(', ', array_slice(array_map(fn ($t) => Str::lower(trim((string) $t)), (array) ($data['tags'] ?? [])), 0, 6)), 300, '') ?: null,
            'robots' => in_array($rb = Setting::get('seo.article_default_robots'), array_keys(\App\Support\SeoRules::ROBOTS), true) ? $rb : 'index,follow',
            'schema_type' => in_array($st = Setting::get('seo.article_default_schema'), array_keys(\App\Support\SeoRules::ARTICLE_SCHEMA_TYPES), true) ? $st : 'BlogPosting',
            'status' => ($publish ?? Setting::bool('news.auto_publish', true)) ? 'published' : 'draft',
            'published_at' => now(),
            'source_url' => $sources[0]['url'],
            'source_title' => Str::limit($sources[0]['title'], 290, ''),
            'story_key' => ! empty($data['story']) && is_array($data['story']) ? Article::storyKey($data['story']['subject'] ?? null, $data['story']['topic'] ?? null, $data['story']['event'] ?? null) : null,
            'source_hash' => sha1($primaryLink),
            'source_links' => $links,
            'similarity' => $res['similarity'],
            'is_ai_generated' => true,
        ]);

        return $article;
    }
}
