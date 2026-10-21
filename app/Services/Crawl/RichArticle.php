<?php

namespace App\Services\Crawl;

use App\Services\PageFetcher;

/**
 * "Exact copy" scraper: turns a news page into the article exactly as published - headings, paragraphs, lists, tables,
 * quotes, links and pictures in their original order - with the page chrome (menus, share bars, related stories, ads,
 * comments) removed. No AI involved, so every fact is the source's own.
 *
 * Several strategies are tried until the story is complete:
 *   1. the page's own markup (readability-style scorer, see ArticleExtractor::contentRoot)
 *   2. the AMP version of the page (<link rel="amphtml">) when the normal page is JavaScript-heavy or thin
 *   3. JSON-LD "articleBody"
 *   4. JSON embedded in the page (Next.js / Nuxt / app state) that holds the story body
 *   5. multi-page stories ("Next page", rel="next", ?page=2 ...) are followed and joined, up to MAX_PAGES
 */
class RichArticle
{
    public const MAX_PAGES = 6;
    private const THIN_WORDS = 120;
    private const BLOCKS = ['p', 'div', 'section', 'article', 'main', 'ul', 'ol', 'li', 'table', 'figure', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'picture', 'header'];
    private const SKIP_IMG = '/logo|icon|avatar|sprite|pixel|placeholder|blank|spacer|\.svg|\.gif|gravatar|emoji|badge|banner-ad|\/ads?\/|doubleclick|tracking|1x1/i';

    /**
     * @return array{title:string,description:string,author:?string,published_at:?\Carbon\Carbon,html:string,text:string,words:int,images:array,image_alts:array,canonical:?string,site:?string,tags:array,section:?string,url:string,pages:int,strategy:string}|string  the article, or the reason it failed
     */
    public static function scrape(string $url): array|string
    {
        $r = Fetcher::get($url);
        if (! $r['ok']) return $r['error'] ? 'Could not open the page: '.$r['error'] : 'Could not open that page (blocked, offline or not HTML).';

        $page = self::parse($r['body'], $r['url']);
        $page['strategy'] = 'page';

        // 2. AMP twin: usually plain server-rendered markup, even when the normal page builds the story with JavaScript.
        if ($page['words'] < self::THIN_WORDS && ($amp = self::linkRel($r['body'], 'amphtml', $r['url']))) {
            $a = Fetcher::get($amp);
            if ($a['ok']) {
                $alt = self::parse($a['body'], $a['url']);
                if ($alt['words'] > $page['words']) { $page = ['title' => $page['title'] ?: $alt['title'], 'images' => $page['images'] ?: $alt['images']] + $alt; $page['strategy'] = 'amp'; }
            }
        }

        // 3 + 4. Structured data: JSON-LD articleBody, then JSON embedded in the page.
        if ($page['words'] < self::THIN_WORDS) {
            $emb = self::embedded($r['body'], $r['url']);
            if ($emb && self::words($emb['text']) > $page['words']) { $page['html'] = $emb['html']; $page['text'] = $emb['text']; $page['words'] = self::words($emb['text']); $page['strategy'] = $emb['via']; }
        }

        // 5. Multi-page stories.
        $seen = [$r['url'] => true, $url => true]; $cur = $r['url']; $body = $r['body']; $pages = 1;
        while ($pages < self::MAX_PAGES && ($next = self::nextPage($body, $cur, $url, $pages + 1)) && empty($seen[$next])) {
            $seen[$next] = true;
            $n = Fetcher::get($next);
            if (! $n['ok']) break;
            $more = self::parse($n['body'], $n['url']);
            if ($more['words'] < 40) break;
            $page['html'] = self::joinHtml($page['html'], $more['html']);
            $page['text'] .= "\n\n".$more['text'];
            $page['words'] = self::words($page['text']);
            $page['images'] = array_values(array_unique(array_merge($page['images'], $more['images'])));
            $page['image_alts'] += $more['image_alts'];
            $cur = $n['url']; $body = $n['body']; $pages++;
        }

        $page['pages'] = $pages;
        $page['url'] = $r['url'];
        if (! $page['title']) return 'No title found on that page.';
        if ($page['words'] < 30 || trim(strip_tags($page['html'])) === '') return 'No readable article text found on that page ('.$page['words'].' words). It may need JavaScript or a login.';
        return $page;
    }

    /* ------------------------------ one page ------------------------------ */

    /** @return array<string,mixed> */
    public static function parse(string $html, string $url): array
    {
        $base = ArticleExtractor::extract($html, $url);

        $dom = PageFetcher::dom($html);
        $meta = PageFetcher::meta($dom);
        $tags = []; $section = null;
        foreach ($dom->getElementsByTagName('meta') as $m) {
            if (strtolower($m->getAttribute('property')) === 'article:tag' && $m->getAttribute('content')) $tags[] = $m->getAttribute('content');
        }
        foreach (PageFetcher::jsonLd($dom) as $n) {
            if (! array_intersect((array) ($n['@type'] ?? []), ['NewsArticle', 'Article', 'BlogPosting', 'ReportageNewsArticle', 'AnalysisNewsArticle'])) continue;
            foreach ((array) ($n['keywords'] ?? []) as $k) foreach (is_string($k) ? explode(',', $k) : [] as $kw) $tags[] = $kw;
            $s = $n['articleSection'] ?? null; $section = is_array($s) ? ($s[0] ?? null) : $s;
            break;
        }
        foreach (explode(',', (string) ($meta['keywords'] ?? $meta['news_keywords'] ?? '')) as $kw) $tags[] = $kw;
        $section = $section ?: ($meta['article:section'] ?? null);

        ArticleExtractor::strip($dom);
        $root = ArticleExtractor::contentRoot($dom);
        $st = ['seen' => [], 'url' => $url, 'title' => mb_strtolower($base['title']), 'imgs' => []];
        $out = $root ? self::container($root, $st) : '';
        $out = self::tidy($out);

        // The scorer found the story in text but the markup walk lost most of it (e.g. text sits in bare <div>s): fall back to the clean paragraphs.
        $htmlWords = self::words(strip_tags($out));
        if ($htmlWords < $base['words'] * 0.6) {
            $out = implode('', array_map(fn ($p) => str_starts_with($p, '## ') ? '<h2>'.e(substr($p, 3)).'</h2>' : '<p>'.e(preg_replace('/^•\s*/u', '', $p)).'</p>', preg_split('/\n{2,}/', $base['text'], -1, PREG_SPLIT_NO_EMPTY)));
            $htmlWords = $base['words'];
        }

        return [
            'title' => $base['title'], 'description' => $base['description'], 'author' => $base['author'], 'published_at' => $base['published_at'],
            'html' => $out, 'text' => $base['words'] >= $htmlWords ? $base['text'] : trim(strip_tags(str_replace(['</p>', '</h2>', '</h3>', '</li>'], "\n\n", $out))),
            'words' => max($base['words'], $htmlWords),
            'images' => $base['images'], 'image_alts' => $base['image_alts'], 'canonical' => $base['canonical'], 'site' => $base['site'],
            'tags' => self::cleanTags($tags), 'section' => $section ? trim((string) $section) : null,
        ];
    }

    private static function cleanTags(array $tags): array
    {
        $out = [];
        foreach ($tags as $t) {
            $t = mb_strtolower(trim(strip_tags((string) $t)));
            if (mb_strlen($t) >= 2 && mb_strlen($t) <= 40 && ! in_array($t, $out, true)) $out[] = $t;
        }
        return array_slice($out, 0, 8);
    }

    /* ------------------------------ DOM -> HTML ------------------------------ */

    /** Children of a generic container: blocks are converted, loose text / inline runs become paragraphs. */
    private static function container(\DOMNode $node, array &$st): string
    {
        $out = ''; $run = '';
        $flush = function () use (&$out, &$run, &$st) {
            $t = ArticleExtractor::normalize(strip_tags($run));
            if ($t !== '' && mb_strlen($t) >= 15 && ! preg_match(ArticleExtractor::BOILERPLATE_LINE, $t) && ! self::metaLine($t) && ! self::dupe($t, $st)) $out .= '<p>'.trim($run).'</p>';
            $run = '';
        };
        foreach ($node->childNodes as $c) {
            if ($c instanceof \DOMText) { $run .= e(preg_replace('/[\s\x{00A0}]+/u', ' ', $c->textContent)); continue; }
            if (! $c instanceof \DOMElement) continue;
            $tag = strtolower($c->nodeName);
            if (in_array($tag, ['script', 'style', 'noscript', 'nav', 'aside', 'footer', 'form', 'iframe', 'svg', 'button', 'select', 'template', 'dialog', 'head', 'hr'], true)) continue;
            if (in_array($tag, ['strong', 'b', 'em', 'i', 'a', 'span', 'br', 'u', 'small', 'mark', 'sup', 'sub', 'font', 'abbr', 'time', 'cite', 'code'], true) && ! self::hasBlock($c)) { $run .= self::inline($c, $st['url'], true); continue; }
            $flush();
            $out .= self::block($c, $st);
        }
        $flush();
        return $out;
    }

    private static function block(\DOMElement $n, array &$st): string
    {
        $tag = strtolower($n->nodeName);
        $url = $st['url'];
        switch ($tag) {
            case 'p':
                $t = ArticleExtractor::normalize($n->textContent);
                $imgs = self::imagesIn($n, $st);
                if ($t === '') return $imgs;
                if (preg_match(ArticleExtractor::BOILERPLATE_LINE, $t) || self::metaLine($t) || self::dupe($t, $st)) return $imgs;
                return '<p>'.trim(self::inline($n, $url)).'</p>'.$imgs;

            case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
                $t = ArticleExtractor::normalize($n->textContent);
                if ($tag === 'h1' || mb_strlen($t) < 3 || mb_strtolower($t) === $st['title'] || preg_match(ArticleExtractor::BOILERPLATE_LINE, $t) || preg_match('/^(related|trending|more stories|you may also like|recommended|latest news|popular)\b/i', $t)) return '';
                $h = $tag === 'h2' ? 'h2' : ($tag === 'h3' ? 'h3' : 'h4');
                return "<$h>".e($t)."</$h>";

            case 'ul': case 'ol':
                $items = []; $linkLen = 0; $all = 0;
                foreach ($n->childNodes as $li) {
                    if (! $li instanceof \DOMElement || strtolower($li->nodeName) !== 'li') continue;
                    $t = ArticleExtractor::normalize($li->textContent);
                    if ($t === '' || self::metaLine($t)) continue;
                    foreach ($li->getElementsByTagName('a') as $a) $linkLen += mb_strlen($a->textContent);
                    $all += mb_strlen($t);
                    $items[] = self::hasBlock($li) && ! self::onlyInlineBlocks($li) ? self::container($li, $st) : trim(self::inline($li, $url));
                }
                if (! $items || ($all > 0 && count($items) >= 3 && $linkLen / $all > 0.7)) return '';   // a list of links = related stories / menu
                return "<$tag>".implode('', array_map(fn ($i) => '<li>'.$i.'</li>', $items))."</$tag>";

            case 'blockquote':
                $t = ArticleExtractor::normalize($n->textContent);
                if ($t === '' || self::dupe($t, $st)) return '';
                return '<blockquote>'.(self::hasBlock($n) ? self::container($n, $st) : '<p>'.trim(self::inline($n, $url)).'</p>').'</blockquote>';

            case 'table':
                return self::table($n, $st);

            case 'figure': case 'picture':
                return self::imagesIn($n, $st, true);

            case 'img':
                return self::imageTag($n, $st);

            case 'pre':
                return '';

            default:    // div, section, article, main, header, span-with-blocks, li outside lists ...
                return self::container($n, $st);
        }
    }

    private static function inline(\DOMNode $n, string $base, bool $self = false): string
    {
        $o = '';
        $kids = $self ? [$n] : iterator_to_array($n->childNodes);
        foreach ($kids as $c) {
            if ($c instanceof \DOMText) { $o .= e(preg_replace('/[\s\x{00A0}]+/u', ' ', $c->textContent)); continue; }
            if (! $c instanceof \DOMElement) continue;
            $tag = strtolower($c->nodeName);
            switch ($tag) {
                case 'strong': case 'b': $i = self::inline($c, $base); $o .= trim(strip_tags($i)) === '' ? $i : '<strong>'.$i.'</strong>'; break;
                case 'em': case 'i': $i = self::inline($c, $base); $o .= trim(strip_tags($i)) === '' ? $i : '<em>'.$i.'</em>'; break;
                case 'br': $o .= '<br>'; break;
                case 'a':
                    $i = self::inline($c, $base);
                    $href = PageFetcher::absolute((string) $c->getAttribute('href'), $base);
                    $o .= ($href !== '' && preg_match('#^https?://#i', $href) && trim(strip_tags($i)) !== '') ? '<a href="'.e($href).'" target="_blank" rel="noopener nofollow">'.$i.'</a>' : $i;
                    break;
                case 'script': case 'style': case 'noscript': case 'svg': case 'iframe': case 'button': case 'img': case 'picture': case 'figure': break;
                default: $o .= self::inline($c, $base);
            }
        }
        return $o;
    }

    private static function table(\DOMElement $t, array &$st): string
    {
        $rows = [];
        foreach ($t->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $td) {
                if (! $td instanceof \DOMElement || ! in_array(strtolower($td->nodeName), ['td', 'th'], true)) continue;
                $cells[] = [strtolower($td->nodeName), trim(self::inline($td, $st['url']))];
            }
            if ($cells) $rows[] = $cells;
        }
        if (count($rows) < 1 || (count($rows) < 2 && count($rows[0]) < 2)) return '';
        $h = '<table><tbody>';
        foreach ($rows as $cells) $h .= '<tr>'.implode('', array_map(fn ($c) => "<{$c[0]}>{$c[1]}</{$c[0]}>", $cells)).'</tr>';
        return $h.'</tbody></table>';
    }

    /** Every usable picture inside an element, as <figure>s (with caption when the element is a <figure>). */
    private static function imagesIn(\DOMElement $n, array &$st, bool $isFigure = false): string
    {
        $caption = '';
        if ($isFigure) foreach ($n->getElementsByTagName('figcaption') as $fc) { $caption = ArticleExtractor::normalize($fc->textContent); break; }
        $out = '';
        foreach ($n->getElementsByTagName('img') as $img) {
            $fig = self::imageTag($img, $st, $caption);
            if ($fig !== '') { $out .= $fig; $caption = ''; }
        }
        // <picture><source srcset> with no usable <img>
        if ($out === '' ) foreach ($n->getElementsByTagName('source') as $s) {
            $set = $s->getAttribute('srcset') ?: $s->getAttribute('data-srcset');
            if ($set) { $out .= self::figure(trim(preg_split('/\s+/', trim(explode(',', $set)[0]))[0] ?? ''), '', $caption, $st); break; }
        }
        return $out;
    }

    private static function imageTag(\DOMElement $img, array &$st, string $caption = ''): string
    {
        $w = (int) $img->getAttribute('width'); $h = (int) $img->getAttribute('height');
        if (($w && $w < 200) || ($h && $h < 100)) return '';
        if (preg_match('/(avatar|author|emoji|icon|logo|ad-|sponsor)/i', $img->getAttribute('class'))) return '';
        return self::figure((string) ArticleExtractor::bestSrc($img), trim($img->getAttribute('alt')), $caption, $st);
    }

    private static function figure(string $src, string $alt, string $caption, array &$st): string
    {
        $abs = PageFetcher::absolute($src, $st['url']);
        if ($abs === '' || ! preg_match('#^https?://#i', $abs) || preg_match(self::SKIP_IMG, $abs) || isset($st['imgs'][$abs])) return '';
        $st['imgs'][$abs] = true;
        $alt = ArticleExtractor::normalize($alt);
        $cap = $caption !== '' ? $caption : '';
        return '<figure><img src="'.e($abs).'" alt="'.e($alt).'" loading="lazy">'.($cap !== '' ? '<figcaption>'.e($cap).'</figcaption>' : '').'</figure>';
    }

    private static function hasBlock(\DOMElement $n): bool
    {
        foreach (self::BLOCKS as $b) if ($n->getElementsByTagName($b)->length) return true;
        return false;
    }

    /** A <li> whose only block child is a single <p> is still just a text item. */
    private static function onlyInlineBlocks(\DOMElement $li): bool
    {
        return $li->getElementsByTagName('p')->length <= 1 && ! $li->getElementsByTagName('ul')->length && ! $li->getElementsByTagName('ol')->length && ! $li->getElementsByTagName('table')->length;
    }

    /** Byline / date / "write a comment" lines that sit inside the story container but are not part of the story. */
    private static function metaLine(string $t): bool
    {
        if (mb_strlen($t) > 100) return false;
        return (bool) preg_match('/^((last\s+)?(updated|published|posted|modified)(\s+on)?\b|written\s+by\b|by\s+[A-Z][\w.\- ]{2,40}$|share(\s+this)?\b|write\s+a\s+comment|\d+\s+comments?$|\d+\s*min(ute)?s?\s+read)/i', $t)
            || (bool) preg_match('/^(\w+\s+\d{1,2},?\s+\d{4}|\d{1,2}\s+\w+\s+\d{4})(,?\s+\d{1,2}:\d{2}.*)?$/u', $t);
    }

    private static function dupe(string $t, array &$st): bool
    {
        if (mb_strlen($t) < 30) return false;          // short lines ("Yes", "Price") may legitimately repeat
        $k = md5(mb_strtolower($t));
        if (isset($st['seen'][$k])) return true;
        return ! ($st['seen'][$k] = true);
    }

    /** Remove empty tags, stray leading/trailing breaks and collapse whitespace. */
    private static function tidy(string $h): string
    {
        $h = preg_replace('#<(p|li|h2|h3|h4|blockquote|figure)>\s*(<br>\s*)*</\1>#i', '', $h);
        $h = preg_replace('#<p>\s*(<br>\s*)+#i', '<p>', $h);
        $h = preg_replace('#(\s*<br>)+\s*</p>#i', '</p>', $h);
        $h = preg_replace('#(<br>\s*){3,}#i', '<br><br>', $h);
        return trim(preg_replace('/>\s+</', '><', $h));
    }

    private static function words(string $t): int
    {
        return count(preg_split('/\s+/u', trim($t), -1, PREG_SPLIT_NO_EMPTY));
    }

    /** Append the next page's blocks, skipping any paragraph already present (pages often repeat the headline/intro). */
    private static function joinHtml(string $a, string $b): string
    {
        preg_match_all('#<(p|li)>(.*?)</\1>#is', $a, $m);
        $have = array_flip(array_map(fn ($x) => md5(mb_strtolower(trim(strip_tags($x)))), $m[2]));
        $b = preg_replace_callback('#<p>(.*?)</p>#is', fn ($x) => isset($have[md5(mb_strtolower(trim(strip_tags($x[1]))))]) && mb_strlen(strip_tags($x[1])) > 30 ? '' : $x[0], $b);
        return $a.$b;
    }

    /* ------------------------------ other sources of the story ------------------------------ */

    private static function linkRel(string $html, string $rel, string $base): ?string
    {
        foreach (PageFetcher::dom($html)->getElementsByTagName('link') as $l) {
            if (strtolower($l->getAttribute('rel')) === $rel && $l->getAttribute('href')) return PageFetcher::absolute($l->getAttribute('href'), $base) ?: null;
        }
        return null;
    }

    /**
     * Story body from JSON-LD articleBody or from JSON blobs in the page (Next.js __NEXT_DATA__, Nuxt, application/json).
     *
     * @return array{html:string,text:string,via:string}|null
     */
    private static function embedded(string $html, string $url): ?array
    {
        $dom = PageFetcher::dom($html);
        $best = ''; $via = '';
        foreach (PageFetcher::jsonLd($dom) as $n) {
            if (isset($n['articleBody']) && is_string($n['articleBody']) && mb_strlen($n['articleBody']) > mb_strlen($best)) { $best = $n['articleBody']; $via = 'json-ld'; }
        }
        foreach ($dom->getElementsByTagName('script') as $s) {
            $type = strtolower($s->getAttribute('type'));
            if ($type !== 'application/json' && $s->getAttribute('id') !== '__NEXT_DATA__' && ! str_contains($type, 'json') || str_contains($type, 'ld+json')) continue;
            $j = json_decode(trim($s->textContent), true);
            if (! is_array($j)) continue;
            $found = self::longestBody($j);
            if (mb_strlen($found) > mb_strlen($best)) { $best = $found; $via = 'embedded-json'; }
        }
        $best = trim($best);
        if (mb_strlen($best) < 400) return null;

        if (preg_match('/<(p|div|br|h2|ul)\b/i', $best)) {
            $d = PageFetcher::dom('<html><body><div id="rich-root">'.$best.'</div></body></html>');
            $root = $d->getElementById('rich-root') ?? $d->getElementsByTagName('div')->item(0);
            $st = ['seen' => [], 'url' => $url, 'title' => '', 'imgs' => []];
            $h = self::tidy($root ? self::container($root, $st) : '');
        } else {
            $h = implode('', array_map(fn ($p) => '<p>'.e(trim($p)).'</p>', preg_split('/\R{1,}/u', $best, -1, PREG_SPLIT_NO_EMPTY)));
        }
        $text = trim(preg_replace("/\n{3,}/", "\n\n", strip_tags(str_replace(['</p>', '</h2>', '</h3>', '</li>', '<br>'], "\n\n", $h))));
        return $h !== '' ? ['html' => $h, 'text' => $text, 'via' => $via] : null;
    }

    private static function longestBody(array $node, int $depth = 0): string
    {
        if ($depth > 12) return '';
        $best = '';
        foreach ($node as $k => $v) {
            if (is_string($v) && is_string($k) && preg_match('/^(articleBody|article_body|body|bodyHtml|body_html|content|contentHtml|content_html|storyContent|story|text|html)$/i', $k) && mb_strlen($v) > mb_strlen($best) && mb_strlen($v) > 400) $best = $v;
            elseif (is_array($v)) { $f = self::longestBody($v, $depth + 1); if (mb_strlen($f) > mb_strlen($best)) $best = $f; }
        }
        return $best;
    }

    /**
     * The next page of the SAME story, or null. rel="next" is only trusted when it stays on the same article path
     * (many blogs use rel="next" for the next *post*, which must never be glued on).
     */
    private static function nextPage(string $html, string $current, string $first, int $wanted): ?string
    {
        $dom = PageFetcher::dom($html);
        $baseOf = fn (string $u) => preg_replace('#(/(page/)?\d+)?/?$#', '', strtolower((string) parse_url(preg_replace('/[?&](page|paged|pg|p)=\d+/', '', $u), PHP_URL_PATH)));
        $firstBase = $baseOf($first); $host = strtolower((string) parse_url($first, PHP_URL_HOST));
        $same = function (?string $u) use ($baseOf, $firstBase, $host, $first) {
            if (! $u || strtolower((string) parse_url($u, PHP_URL_HOST)) !== $host) return false;
            $b = $baseOf($u);
            return $firstBase !== '' && $firstBase !== '/' && ($b === $firstBase || str_starts_with(strtolower((string) parse_url($u, PHP_URL_PATH)), rtrim(strtolower((string) parse_url($first, PHP_URL_PATH)), '/').'/')) && $u !== $first;
        };

        $cands = [];
        foreach ($dom->getElementsByTagName('link') as $l) if (strtolower($l->getAttribute('rel')) === 'next') $cands[] = PageFetcher::absolute($l->getAttribute('href'), $current);
        $xp = new \DOMXPath($dom);
        foreach ($xp->query('//a[@href][contains(concat(" ", normalize-space(@rel), " "), " next ")]') as $a) $cands[] = PageFetcher::absolute($a->getAttribute('href'), $current);
        foreach ($xp->query('//*[contains(@class,"pagination") or contains(@class,"page-links") or contains(@class,"page-numbers") or contains(@class,"pager") or contains(@class,"post-pages") or contains(@class,"paging")]//a[@href]') as $a) {
            $t = mb_strtolower(trim($a->textContent));
            if ($t === (string) $wanted || in_array($t, ['next', 'next page', 'next »', '›', '»', '→', '>'], true)) $cands[] = PageFetcher::absolute($a->getAttribute('href'), $current);
        }
        foreach (array_unique(array_filter($cands)) as $c) if ($same($c)) return $c;
        return null;
    }
}
