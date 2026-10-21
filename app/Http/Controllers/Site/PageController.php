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

    /** Service detail pages and the four category landing pages. */
    public function service(string $slug)
    {
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
        $urls = [$base.'/'];
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

        return response()->json(['ok' => true]);
    }
}
