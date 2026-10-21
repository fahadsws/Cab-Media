<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * The writing desk. Turns one or more source reports into a single original article and runs a quality gate
 * (source-overlap, length, boilerplate phrases) before anything is published.
 *
 * Honest by design: the prompt forbids invented quotes, invented specs and fake first-hand "we drove it" claims.
 * "Human feel" here means genuine editorial craft - concrete local context, varied rhythm, a point of view -
 * not tricks to fool AI detectors.
 */
class Editorial
{
    private const ANGLES = [
        'news_brief' => 'A tight, punchy news report (300-420 words). Lead with the single most important fact, then what changed, then what happens next.',
        'explainer' => 'An explainer (420-600 words): open with the news, then use 2-3 descriptive <h2> sections that answer the questions a curious reader would actually ask.',
        'reader_angle' => 'A reader-focused piece (400-550 words): who this matters to, what it means for them in practical terms, and what they could do next.',
        'analysis' => 'A short analysis (450-600 words): place the news in the wider industry - peers, trends, what it signals - and take a clear, reasoned position.',
    ];


    /**
     * @param  array<int,array{title:string,text:string,outlet:string,url:string}>  $sources
     * @param  string|null  $mode  'faithful' (same facts and meaning, new wording - default) or 'original' (free editorial angle). Setting: news.rewrite_mode
     * @return array{data:array,similarity:int}|array{error:string}
     */
    public static function write(array $sources, string $topicHint = '', ?string $mode = null): array
    {
        $mode = $mode ?: (string) Setting::get('news.rewrite_mode', 'faithful');
        $faithful = $mode !== 'original';
        $angleKey = array_rand(self::ANGLES);
        $cats = Category::where('is_active', true)->pluck('name')->implode(', ') ?: 'General';
        $brief = AiClient::brief();
        $srcText = collect($sources)->values()->map(fn ($s, $i) => '[Source '.($i + 1).' - '.$s['outlet']."]\nHeadline: {$s['title']}\n".Str::limit($s['text'], 6000, ''))->implode("\n\n");
        $plainSources = collect($sources)->pluck('text')->push(...collect($sources)->pluck('title'))->all();
        $srcWords = max(array_map(fn ($t) => TextTools::wordCount($t), $plainSources));
        $target = (int) max(350, min(1000, $srcWords * 1.0));
        $minWords = $faithful ? (int) max(100, min(400, 0.5 * min($srcWords, 800))) : 260;

        $system = $faithful ? self::faithfulPrompt($target, $cats, $brief) : self::systemPrompt($angleKey, $cats, $brief);
        $temperature = $faithful ? 0.35 : 0.85;
        $attempt = 0; $note = ''; $aiFails = 0; $check = null;
        do {
            $data = AiClient::json([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $srcText.($topicHint ? "\n\nEditor's note: $topicHint" : '').$note],
            ], ['temperature' => $temperature, 'max_tokens' => 4000, 'timeout' => 120]);

            if (! $data || empty($data['title']) || empty($data['body_html'])) {
                // Transient model failure (rate limit, cut-off JSON): try once more before giving up on this story.
                if (++$aiFails < 2) { sleep(2); continue; }
                return ['error' => 'AI returned no usable article ('.(AiClient::lastError() ?? 'empty reply').')'];
            }
            if (($data['relevant'] ?? true) === false) return ['error' => 'Not relevant to the blog\'s audience'];

            $body = self::cleanHtml($data['body_html']);
            $words = TextTools::wordCount($body);
            $overlap = TextTools::overlap($body, $plainSources);
            $cliches = TextTools::clicheCount(TextTools::plain($body));
            $maxOverlap = $faithful ? 30 : 18;

            $problems = [];
            if ($overlap > $maxOverlap) $problems[] = "about $overlap% of your phrasing matches the sources - re-express every sentence in clearly different words and sentence structure (names, model names, numbers and spec terms excepted)";
            if ($words < $minWords) $problems[] = "it is only $words words - cover every fact in the sources (at least $minWords words)";
            if (! $faithful && $cliches > 1) $problems[] = 'it contains stock phrases (e.g. "in conclusion", "game-changer", "when it comes to") - remove them';
            if ($faithful) {
                $outText = TextTools::plain($body).' '.($data['title'] ?? '').' '.implode(' ', (array) ($data['tldr'] ?? [])).' '.implode(' ', array_map(fn ($f) => ($f['q'] ?? '').' '.($f['a'] ?? ''), (array) ($data['faq'] ?? [])));
                $check = TextTools::factCheck($outText, implode("\n", $plainSources));
                if (count($check['invented']) > 1) $problems[] = 'it contains figures that are not in the sources ('.implode(', ', array_slice($check['invented'], 0, 6)).') - remove or correct them; never add facts';
                if ($check['coverage'] < 0.6) $problems[] = 'it dropped important figures from the sources ('.implode(', ', array_slice($check['missing'], 0, 8)).') - keep every price, date, spec and number';
            }

            if (! $problems) { $data['body_html'] = $body; return ['data' => $data, 'similarity' => $overlap]; }
            $note = "\n\nREVISION REQUIRED: your previous draft was rejected because ".implode('; ', $problems).'. Write a new, better draft now.';
        } while (++$attempt < 2);

        // After one revision accept only if it is distinct enough, long enough and (faithful mode) not inventing facts.
        $lenient = $faithful ? ($overlap <= 40 && $words >= $minWords * 0.8 && count($check['invented'] ?? []) <= 1 && ($check['coverage'] ?? 1) >= 0.5) : ($overlap <= 30 && $words >= 220);
        if ($lenient) { $data['body_html'] = $body; return ['data' => $data, 'similarity' => $overlap]; }
        return ['error' => "Quality gate failed (overlap $overlap%, $words words".($faithful ? ', '.count($check['invented'] ?? []).' invented figure(s), '.round(($check['coverage'] ?? 1) * 100).'% of figures kept' : '').')'];
    }

    private static function faithfulPrompt(int $target, string $cats, string $brief): string
    {
        return <<<TXT
You are a sub-editor. {$brief} You are given one or more reports on the same story. Produce ONE rewritten article that carries exactly the same information and meaning as the reports, in entirely new wording.

RULES
1. Same meaning, every fact: keep every material fact - what happened, who, when, where, amounts (keep the currency), percentages, rates, dates, figures, company, product and scheme names, and who said what. Copy numbers and units exactly as given; do not convert units or round. Do not leave out important details.
2. Nothing added: no facts, numbers, opinions, predictions, forecasts, advice or background that are not in the reports. Never give advice or recommendations of your own; if the reports contain an expert's recommendation, attribute it to them. If reports disagree, say "reports differ" and give both. If a detail is unclear, leave it out or say it is not confirmed.
3. New wording: rewrite every sentence with different vocabulary and sentence structure; do not copy phrases of five or more words (names, product names, financial terms and numbers are exempt). Turn direct quotes into reported speech ("the company said it will ...") and never invent a quote.
4. Structure: lead with the most important fact in the first sentence, then follow the order of the reports, merging overlapping details from several reports into one flow. Neutral, clear newsroom tone. No first-person claims (we spoke to, we attended), no marketing language, no stock phrases.
5. Length: about {$target} words - as long as it takes to cover all facts, no padding. Use only <p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>, <table>, <thead>, <tbody>, <tr>, <th>, <td>. Do not include the title as a heading.
   STRUCTURE (write a proper, complete article, not a summary): an opening paragraph with the key facts; then 3-5 sections, each with a specific <h2> and 2-3 full paragraphs; a <ul> for lists of features, variants or highlights; and a closing section on what it means for the reader, only if the reports support it. Whenever the reports give three or more comparable figures (prices, results, dates, specs, rates), present them in ONE <table> with a <thead> header row (e.g. Tool | Use | Price) instead of burying them in prose; never invent a row or a cell. Short paragraphs (2-4 sentences), no padding.
6. The title must state the same news as the source headline in different words (specific, under 90 characters, not clickbait). The FAQ must only use facts from the reports.


OUTPUT: ONE JSON object, no markdown fences, with keys:
"title", "excerpt" (max 200 chars), "tldr" (array of exactly 3 short takeaway strings), "body_html", "faq" (array of 3 objects {"q","a"} answered only from the reports), "meta_title" (max 60 chars), "meta_description" (max 155 chars, includes the key fact), "category" (exactly one of: {$cats}), "tags" (array of 4-6 lowercase strings), "relevant" (boolean - false if the story has no relevance to the audience described above),
"story": null or {"subject" (the company or institution the story is mainly about, e.g. "OpenAI"),"topic" (short topic, e.g. "gpt pricing"),"event" (update = a price, result, release or policy change; event = any other one-off announcement; other)}
TXT;
    }

    private static function systemPrompt(string $angleKey, string $cats, string $brief): string
    {
        $angle = self::ANGLES[$angleKey];
        $cliches = implode('", "', array_slice(TextTools::CLICHES, 0, 12));

        return <<<TXT
You are a staff writer. {$brief} You are given one or more reports on the same story. Write ONE original article for that audience.

FORMAT FOR THIS PIECE: {$angle}

RULES
1. Originality: use the sources only as fact material. Never reuse their sentences, distinctive phrasing or paragraph order. Combine what different sources say into one better-organised story.
2. Accuracy: every number, rate, amount, date and name must come from the sources. Never give advice or recommendations of your own; attribute any recommendation to the expert or outlet that made it. If sources disagree or something is unknown, say "not yet confirmed" - never guess. No invented quotes. No claims that we spoke to, tested or attended anything.
3. Voice: write like an experienced journalist talking to a smart friend. Vary sentence length; short sentences are fine. Use contractions. Add concrete practical context where the sources support it (what it means for a small business, how peers compare). A point of view is welcome, clearly framed as analysis ("Our read:", "The catch:"), never as fake first-hand experience.
4. Craft: no generic intro; don't open every sentence with the company's name; headings must be specific to this story, not templated. Avoid stock phrases such as "{$cliches}".
5. Answer-engine friendly: put the key facts (what, amount, when, who is affected) in the first 2 sentences and again in a short "Quick facts" <ul>. Write the FAQ as questions real people type into a search box or ask an AI assistant, each answered in 1-3 self-contained sentences.
6. Length and HTML: use only <p>, <h2>, <h3>, <ul>, <li>, <strong>, <em>, <table>, <thead>, <tbody>, <tr>, <th>, <td>. Do not include the title as a heading. Write a full article with 3-5 specific <h2> sections of 2-3 paragraphs each, and put three or more comparable figures (prices, results, specs) in one <table> with a header row. Never invent table data.


OUTPUT: ONE JSON object, no markdown fences, with keys:
"title" (specific, under 90 chars, not clickbait), "excerpt" (max 200 chars), "tldr" (array of exactly 3 short takeaway strings), "body_html", "faq" (array of 3-4 objects {"q","a"}), "meta_title" (max 60 chars), "meta_description" (max 155 chars, includes the key fact), "category" (exactly one of: {$cats}), "tags" (array of 4-6 lowercase strings), "relevant" (boolean - false if the story has no relevance to the audience described above),
"story": null or {"subject" (the company or institution the story is mainly about, e.g. "OpenAI"),"topic" (short topic, e.g. "gpt pricing"),"event" (update = a price, result, release or policy change; event = any other one-off announcement; other)}
TXT;
    }

    /**
     * Put stored images into the article body as <figure>s between paragraphs (after the 2nd paragraph, then every 3-4 paragraphs),
     * never inside lists or tables. $figures: [[url, alt], ...]
     */
    public static function withImages(string $html, array $figures): string
    {
        if (! $figures) return $html;
        $parts = preg_split('#(?<=</p>)#i', $html, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 3) return $html;
        $out = ''; $n = 0; $next = 2; $paras = 0;
        foreach ($parts as $part) {
            $out .= $part;
            if (! preg_match('#</p>\s*$#i', $part)) continue;
            $paras++;
            if ($paras >= $next && $figures) {
                [$url, $alt] = array_shift($figures);
                $alt = e(trim(strip_tags((string) $alt)));
                $out .= '<figure><img src="'.e($url).'" alt="'.$alt.'" loading="lazy">'.($alt !== '' ? '<figcaption>'.$alt.'</figcaption>' : '').'</figure>';
                $next = $paras + 3;
            }
        }
        return $out;
    }

    public static function cleanHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags($html, '<p><h2><h3><ul><ol><li><strong><em><a><br><table><thead><tbody><tr><th><td><blockquote>');
        $html = preg_replace('/<(table|thead|tbody|tr|th|td|p|h2|h3|ul|ol|li|strong|em|blockquote|br)\b[^>]*?(\/?)>/i', '<$1$2>', $html);   // drop style/class/on* attributes the model may add
        return trim(preg_replace('/\son\w+="[^"]*"/i', '', $html));
    }

    /**
     * Sanitise HTML written by an editor in the admin (TinyMCE). Allows images/tables/figures, and turns any
     * embedded base64 image into a real file so the database never stores image bytes.
     */
    public static function cleanEditorHtml(?string $html): string
    {
        $html = self::externalizeImages((string) $html);
        foreach (['script', 'style', 'iframe', 'object', 'embed'] as $tag) $html = preg_replace('#<'.$tag.'\b[^>]*>.*?</'.$tag.'>#is', '', $html);
        $html = strip_tags($html, '<p><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><em><b><i><u><s><a><br><blockquote><img><figure><figcaption><table><thead><tbody><tr><th><td><hr><video><source><span><div>');
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);
        return trim(preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*/i', '$1=$2#', $html));
    }

    /** Save <img src="data:image/...;base64,..."> payloads to disk and swap in the file URL. */
    public static function externalizeImages(string $html): string
    {
        if (! str_contains($html, 'data:image/')) return $html;
        return preg_replace_callback('#data:image/(png|jpe?g|gif|webp|avif);base64,([A-Za-z0-9+/=\s]+)#i', function ($m) {
            $bin = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
            if ($bin === false || strlen($bin) > 10 * 1024 * 1024) return '';
            $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
            $path = 'editor/'.date('Y/m').'/'.\Illuminate\Support\Str::random(24).'.'.$ext;
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $bin);
            return parse_url(asset('storage/'.$path), PHP_URL_PATH);
        }, $html);
    }
}
