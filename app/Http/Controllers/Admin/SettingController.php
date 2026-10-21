<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AiClient;
use App\Services\Automation;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** group => [icon, fields]; field = [key, label, type(text|number|textarea|bool|secret), help] */
    public static function groups(): array
    {
        $cron = [];
        foreach (Automation::TASKS as $task => [$label, $default]) {
            $cron[] = ["cron.enabled.$task", "$label - enabled", 'bool', ''];
            $cron[] = ["cron.every.$task", "$label - run every (minutes)", 'number', "Default $default"];
        }

        return [
            'General' => ['ti-building-store', [
                ['site.name', 'Site name', 'text', ''],
                ['site.tagline', 'Tagline', 'text', ''],
                ['site.logo', 'Site logo', 'image', 'PNG, JPG, WEBP or SVG. Used in the admin and on the public site.'],
                ['site.favicon', 'Favicon', 'image', 'PNG, ICO or SVG. Used in browser tabs.'],
                ['site.about', 'About the business', 'textarea', ''],
                ['site.email', 'Contact email', 'text', ''],
                ['site.phone', 'Contact phone', 'text', ''],
                ['site.address', 'Address', 'text', ''],
                ['site.offices', 'Office locations (footer & contact)', 'textarea', 'One per line or comma separated, e.g. Delhi, Chennai, USA.'],
                ['site.cta_text', 'Header button text', 'text', 'Default: Get in touch'],
                ['site.cta_url', 'Header button link', 'text', 'Default: /contact-us'],
                ['site.footer_logo', 'Footer logo (wordmark)', 'image', 'Shown in the footer on the dark background. A transparent PNG works best. Leave empty for the built-in wordmark.'],
                ['site.footer_cta_text', 'Footer button text', 'text', 'Default: Start a brief'],
                ['site.footer_cta_url', 'Footer button link', 'text', 'Default: /contact-us'],
                ['site.footer_note', 'Footer copyright line', 'text', 'Use {year} for the current year.'],
            ]],
            'Social profiles' => ['ti-share', [
                ['social.linkedin', 'LinkedIn URL', 'text', ''],
                ['social.youtube', 'YouTube URL', 'text', ''],
                ['social.instagram', 'Instagram URL', 'text', ''],
                ['social.x', 'X URL', 'text', ''],
                ['social.facebook', 'Facebook URL', 'text', ''],
            ]],
            'SEO & Tracking' => ['ti-seo', [
                ['seo.default_description', 'Default meta description', 'textarea', 'Used on pages that have no description of their own.'],
                ['seo.default_og_image', 'Default social share image URL', 'text', 'Used when a page has no image. 1200×630 recommended.'],
                ['seo.twitter_handle', 'X / Twitter handle', 'text', 'e.g. @cabmedia'],
                ['seo.org_name', 'Organization name (schema)', 'text', 'Falls back to the site name.'],
                ['seo.org_logo', 'Organization logo URL (schema)', 'text', ''],
                ['seo.social_profiles', 'Social profile URLs (one per line)', 'textarea', 'Added as Organization "sameAs".'],
                ['seo.google_verification', 'Google Search Console verification code', 'text', 'The content value of the google-site-verification meta tag.'],
                ['seo.bing_verification', 'Bing Webmaster verification code', 'text', ''],
                ['seo.ga4_id', 'Google Analytics 4 ID', 'text', 'e.g. G-XXXXXXXXXX'],
                ['seo.gtm_id', 'Google Tag Manager ID', 'text', 'e.g. GTM-XXXXXXX'],
                ['seo.article_default_robots', 'New automated/imported blogs: robots', 'text', 'index,follow (default), noindex,follow or noindex,nofollow. Use noindex,follow to keep imports out of Google until reviewed.'],
                ['seo.article_default_schema', 'New automated/imported blogs: schema type', 'text', 'BlogPosting (default), Article, NewsArticle, Review or None.'],
                ['seo.robots_extra', 'Extra robots.txt lines', 'textarea', 'Appended to the generated robots.txt.'],
            ]],
            'AI provider' => ['ti-sparkles', [
                ['ai.base_url', 'API base URL', 'text', 'Any OpenAI-compatible endpoint. OpenRouter: https://openrouter.ai/api/v1 · Groq: https://api.groq.com/openai/v1 · Gemini: https://generativelanguage.googleapis.com/v1beta/openai'],
                ['ai.api_key', 'API key', 'secret', 'Stored encrypted. Leave blank to keep the current key.'],
                ['ai.model', 'Primary model', 'text', 'Use a ":free" model on OpenRouter to keep costs at zero.'],
                ['ai.fallback_model', 'Fallback model', 'text', 'Used automatically if the primary model fails or is rate-limited.'],
            ]],
            'Blog automation' => ['ti-robot', [
                ['news.mode', 'How should fetched posts be published?', 'select:ai=AI rewrite (own words; same facts)|scrape=Scrape only (copy as published; no AI)', 'AI rewrite needs the AI provider. Scrape only copies the page as published and credits the source: no AI cost, but check each source\'s terms before republishing.'],
                ['news.language', 'Language of AI-written posts', 'select:English=English|Spanish=Spanish', 'The AI writes the post, title, FAQ and SEO fields in this language.'],
                ['news.audience', 'Audience / topic focus', 'textarea', 'One or two sentences telling the AI who the blog is for and what it covers. Example: "Business owners in India looking for practical advice on advertising and digital marketing."'],
                ['news.rewrite_mode', 'AI rewrite style', 'select:faithful=Faithful (same facts; new wording)|original=Original (fresh editorial piece)', 'Only used when "AI rewrite" is selected above.'],
                ['news.scrape_ai_seo', 'Scrape only: let AI fill empty meta description, FAQ and takeaways', 'bool', 'Never touches the post text. Needs the AI provider; ignored if none is set.'],
                ['news.per_run', 'Posts to publish per run', 'number', 'Total across all sources each time the blog task runs.'],
                ['news.auto_publish', 'Publish automatically (otherwise save as draft for review)', 'bool', ''],
                ['news.max_age_days', 'Ignore stories older than (days)', 'number', 'Default 3.'],
            ]],
            'Task schedule' => ['ti-clock', array_merge($cron, [
                ['cleanup.automation_days', 'Clean-up: keep automation run logs (days)', 'number', 'Default 30.'],
            ])],
        ];
    }

    public function edit()
    {
        $values = [];
        foreach (self::groups() as [, $fields]) {
            foreach ($fields as [$key, , $type]) {
                $values[$key] = $type === 'secret' ? (Setting::get($key) ? '••••••••' : '') : Setting::get($key);
            }
        }
        return view('admin.settings', ['groups' => self::groups(), 'values' => $values]);
    }

    /** "Run setup check": verifies the database, AI provider, mail and basic business details. */
    public function check()
    {
        $rows = [];
        $add = function (string $label, string $status, string $detail) use (&$rows) { $rows[] = ['label' => $label, 'status' => $status, 'detail' => $detail, 'audio' => null]; };

        try { \Illuminate\Support\Facades\DB::connection()->getPdo(); $add('Database', 'ok', 'Connected.'); }
        catch (\Throwable $e) { $add('Database', 'fail', $e->getMessage()); }

        if (AiClient::configured()) {
            $t = AiClient::test();
            $add('AI provider', $t['ok'] ? 'ok' : 'fail', $t['ok'] ? 'Replied "'.trim((string) $t['reply']).'" in '.$t['ms'].' ms via '.$t['endpoint'] : ($t['error'] ?: 'No reply').' ('.$t['endpoint'].')');
        } else {
            $add('AI provider', 'fail', 'No AI API key saved (Settings -> AI provider).');
        }

        $mail = (string) config('mail.default');
        $add('Email', in_array($mail, ['log', 'array'], true) ? 'warn' : 'ok',
            in_array($mail, ['log', 'array'], true) ? "MAIL_MAILER is \"$mail\" - mail is only written to the log. Set real MAIL_* values in .env." : "Mail driver: $mail.");
        $add('Cron token', Setting::get('cron.token') ? 'ok' : 'warn', Setting::get('cron.token') ? 'Set. See Blog automation for the cron URLs.' : 'Not generated yet. Open Blog automation and regenerate it.');
        foreach (['site.phone' => 'phone', 'site.email' => 'email', 'site.address' => 'address'] as $k => $label) {
            if (! Setting::get($k)) $add('Business '.$label, 'warn', "Settings -> General has no $label.");
        }

        return response()->json(['rows' => $rows]);
    }

    public function update(Request $r)
    {
        foreach (self::groups() as [, $fields]) {
            foreach ($fields as [$key, , $type]) {
                $input = $r->input(str_replace('.', '__', $key));
                if ($type === 'image') {
                    $file = $r->file(str_replace('.', '__', $key));
                    if ($file) {
                        $r->validate([str_replace('.', '__', $key) => 'file|mimes:jpg,jpeg,png,webp,svg,ico|max:4096']);
                        $name = match ($key) { 'site.logo' => 'logo', 'site.footer_logo' => 'footer-logo', default => 'favicon' };
                        $folder = public_path('uploads/site');
                        if (! is_dir($folder)) mkdir($folder, 0755, true);
                        $filename = $name.'.'.$file->getClientOriginalExtension();
                        $file->move($folder, $filename);
                        Setting::put($key, asset('uploads/site/'.$filename));
                    }
                    elseif ($lib = \App\Support\MediaRef::clean($r->input(str_replace('.', '__', $key).'_library'))) Setting::put($key, $lib);
                    continue;
                }
                if ($type === 'bool') { Setting::put($key, $r->boolean(str_replace('.', '__', $key)) ? '1' : '0'); continue; }
                if ($type === 'secret') { if ($input !== null && $input !== '' && ! str_starts_with($input, '••')) Setting::put($key, trim($input)); continue; }
                        Setting::put($key, $input === null ? null : trim($input));
            }
        }
        return back()->with('success', 'Settings saved.');
    }
}
