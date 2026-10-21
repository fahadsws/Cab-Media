@php
  $seo = \App\Models\SeoEntry::forRoute(request()->route()?->getName());
  $pageTitle = $seo?->meta_title ?: html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES);
  $pageDesc = $seo?->meta_description ?: html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES);
  $path = trim($__env->yieldContent('path'));
  $canonical = $seo?->canonical_url ?: trim($__env->yieldContent('canonical')) ?: rtrim(config('site.url'), '/').($path === '/' ? '/' : $path);
  $ogImage = $seo?->og_image ?: trim($__env->yieldContent('ogimage')) ?: rtrim(config('site.url'), '/').config('site.og_image');
  $ogTitle = $seo?->og_title ?: html_entity_decode(trim($__env->yieldContent('ogtitle')), ENT_QUOTES) ?: $pageTitle;
  $ogDesc = $seo?->og_description ?: html_entity_decode(trim($__env->yieldContent('ogdesc')), ENT_QUOTES) ?: $pageDesc;
  $ogType = trim($__env->yieldContent('ogtype')) ?: 'website';
  $pageRobots = trim($__env->yieldContent('robots'));
  $robots = ! config('site.indexable') ? 'noindex, nofollow' : ($pageRobots ?: ($seo ? $seo->robots : 'index,follow'));
  $favicon = \App\Models\Setting::get('site.favicon') ?: \App\Models\Setting::get('site.logo') ?: '/favicon-180.png';
@endphp
<!doctype html>
<html lang="en" class="nojs">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#ffffff" />
    <meta name="keywords" content="Cab Media, Media, Marketing, Advertise, OOH" />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}" />
    <meta name="robots" content="{{ $robots }}" />
    <link rel="icon" href="{{ $favicon }}" />
    <link rel="apple-touch-icon" href="{{ $favicon }}" />
    <link rel="canonical" href="{{ $canonical }}" />
    <meta property="og:type" content="{{ $ogType }}" />
    <meta property="og:url" content="{{ $canonical }}" />
    <meta property="og:title" content="{{ $ogTitle }}" />
    <meta property="og:description" content="{{ $ogDesc }}" />
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $ogTitle }}" />
    <meta name="twitter:description" content="{{ $ogDesc }}" />
    <meta name="twitter:image" content="{{ $ogImage }}" />
    <link rel="preload" href="/fonts/plus-jakarta-sans-700-latin.woff2" as="font" type="font/woff2" crossorigin />
    <link rel="alternate" type="application/rss+xml" title="Cab Media Blog" href="{{ url('/blog/feed.xml') }}" />
    <link rel="stylesheet" href="{{ asset('css/cab.css') }}?v={{ filemtime(public_path('css/cab.css')) }}" />
    @yield('jsonld')
  </head>
  <body>
    <div class="progress" aria-hidden="true"></div>
    @include('site.partials.nav')
    <main id="main">@yield('content')</main>
    @include('site.partials.footer')
    <button class="totop" type="button" aria-label="Back to top"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
    <script>window.SITE = { contactUrl: @json(url('/api/contacto')), whatsapp: @json(config('site.whatsapp')) };</script>
    <script src="{{ asset('js/cab.js') }}?v={{ filemtime(public_path('js/cab.js')) }}" defer></script>
  </body>
</html>
