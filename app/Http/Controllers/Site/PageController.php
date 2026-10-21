<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\ContactRequestReceived;
use App\Models\ContactRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Public single-page site of cabmedia.in (view: resources/views/site/pages/home.blade.php). */
class PageController extends Controller
{
    public function home() { return view('site.pages.home'); }

    public function about() { return view('site.pages.about'); }

    public function contact() { return view('site.pages.contact'); }

    /** Service detail pages and the four category landing pages. */
    public function service(string $slug)
    {
        if ($to = \App\Support\ServicesCatalog::MOVED[$slug] ?? null) return redirect()->route('service.show', $to, 301);

        $s = \App\Support\ServicesCatalog::find($slug);
        abort_unless($s, 404);

        return view('site.pages.service', ['s' => $s]);
    }

    /** Pages created in Admin > Pages (catch-all route, kept last). */
    public function cms(string $slug)
    {
        $page = \App\Models\Page::published()->where('slug', $slug)->first();
        abort_unless($page, 404);

        return view('site.pages.cms', ['page' => $page]);
    }

    public function sitemap()
    {
        $base = rtrim(config('site.url'), '/');
        $urls = [$base.'/', $base.'/about-us', $base.'/contact-us'];
        foreach (array_keys(\App\Support\ServicesCatalog::all()) as $slug) $urls[] = $base.'/services/'.$slug;
        $urls[] = $base.'/blog';
        foreach (\App\Models\Article::published()->latest('published_at')->take(2000)->get(['slug']) as $a) $urls[] = $base.'/blog/'.$a->slug;
        foreach (\App\Models\Page::published()->get(['slug']) as $p) $urls[] = $base.'/'.$p->slug;
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', array_map(fn ($u) => '<url><loc>'.e($u).'</loc></url>', $urls)).'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /** POST /api/contacto (JSON). Same payload as the original site's form. */
    public function contactSubmit(Request $r)
    {
        $d = $r->validate([
            'nombre' => 'required|string|max:200',
            'empresa' => 'required|string|max:200',
            'email' => 'required|email|max:200',
            'telefono' => 'required|string|max:60',
            'mejora' => 'required|string|max:5000',
            'aceptaPrivacidad' => 'nullable|string|max:10',
            'fechaAceptacion' => 'nullable|string|max:40',
        ]);

        $req = ContactRequest::create([
            'nombre' => $d['nombre'], 'empresa' => $d['empresa'], 'email' => $d['email'], 'telefono' => $d['telefono'] ?? null,
            'mejora' => $d['mejora'], 'acepta_privacidad' => in_array($d['aceptaPrivacidad'] ?? '', ['sí', 'si', 'on', 'yes', '1'], true),
            'fecha_aceptacion' => ! empty($d['fechaAceptacion']) ? \Illuminate\Support\Carbon::parse($d['fechaAceptacion']) : now(),
            'ip' => $r->ip(), 'user_agent' => substr((string) $r->userAgent(), 0, 300),
        ]);

        $to = Setting::get('site.email') ?: config('site.contact_email');
        try { if ($to) Mail::to($to)->send(new ContactRequestReceived($req)); } catch (\Throwable $e) { report($e); }

        $this->sendLead($req, $r);

        return response()->json(['ok' => true]);
    }

    /** Lead transfer: forward the enquiry to the CRM webhook (URL and secret live in the settings table, not in the admin UI). */
    private function sendLead(ContactRequest $req, Request $r): void
    {
        $url = Setting::get('lead.webhook_url');
        if (! $url) return;
        $secret = (string) Setting::get('lead.webhook_secret', '');

        $body = json_encode([
            'event_id' => 'cabmedia-contact-'.$req->id,
            'profile_id' => 'cabmedia-website',
            'entry_method' => 'link',
            'networking_group' => '',
            'visitor' => ['name' => $req->nombre, 'email' => $req->email, 'phone' => (string) $req->telefono, 'company' => (string) $req->empresa, 'message' => $req->mejora],
            'card_owner' => ['name' => Setting::get('site.name', 'Cab Media'), 'email' => \App\Support\SiteContent::email()],
            'consent' => ['notifications' => true, 'voice' => false, 'photo' => false, 'notes' => false],
            'extras' => ['voice_url' => '', 'selfie_url' => '', 'notes' => ''],
            'utm' => ['source' => (string) $r->input('utm_source', ''), 'medium' => (string) $r->input('utm_medium', ''), 'campaign' => (string) $r->input('utm_campaign', '')],
            'summary' => $req->mejora,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = ['Accept' => 'application/json'];
        if ($secret !== '') $headers['X-Signature'] = 'sha256='.hash_hmac('sha256', $body, $secret);

        dispatch(function () use ($url, $body, $headers) {
            try {
                $res = \Illuminate\Support\Facades\Http::withHeaders($headers)->withBody($body, 'application/json')->timeout(10)->post($url);
                \Illuminate\Support\Facades\Log::log($res->successful() ? 'info' : 'warning', 'Lead webhook '.$res->status(), ['body' => substr($res->body(), 0, 300)]);
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }
}
