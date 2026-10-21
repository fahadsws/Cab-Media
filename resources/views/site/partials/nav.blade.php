@php
  use App\Models\Setting;
  $C = \App\Support\SiteContent::class;
  $menu = $C::menu('header');
  $logo = Setting::get('site.logo') ?: '/img/cabmedia-logo.jpg';
  $ctaText = Setting::get('site.cta_text', 'Get in touch');
  $ctaUrl = Setting::get('site.cta_url', '/#contact');
  $here = '/'.trim(request()->path(), '/');
  $active = fn ($u) => $u && ! str_contains($u, '#') && $u !== '/' && ($here === rtrim($u, '/') || str_starts_with($here, rtrim($u, '/').'/'));
@endphp
<a class="sr-only" href="#main">Skip to content</a>
<header class="nav" id="site-nav">
  <a href="/#home" class="brand" aria-label="{{ Setting::get('site.name', 'Cab Media') }}, home">
    <img src="{{ $logo }}" alt="{{ Setting::get('site.name', 'Cab Media') }}" height="48">
  </a>
  <nav class="nav-links" aria-label="Main">
    @foreach ($menu as $item)
      @if ($item->children->count())
        <div class="dd">
          <a href="{{ $item->url ?: '#' }}" aria-haspopup="true" @if ($item->open_new_tab) target="_blank" rel="noopener" @endif>{{ $item->title }} <i aria-hidden="true">▾</i></a>
          <div class="dd-panel">
            @foreach ($item->children as $c)<a href="{{ $c->url ?: '#' }}" @if ($c->open_new_tab) target="_blank" rel="noopener" @endif><b>{{ $c->title }}</b>@if ($c->description)<small>{{ $c->description }}</small>@endif</a>@endforeach
          </div>
        </div>
      @else
        <a href="{{ $item->url ?: '#' }}" @if ($active($item->url)) aria-current="page" @endif @if ($item->open_new_tab) target="_blank" rel="noopener" @endif>{{ $item->title }}</a>
      @endif
    @endforeach
  </nav>
  <a href="{{ $ctaUrl }}" class="btn nav-cta">{{ $ctaText }} <span class="arr">→</span></a>
  <button class="burger" type="button" aria-expanded="false" aria-controls="menu-movil" aria-label="Open menu"><span></span><span></span><span></span></button>
</header>
<div class="menu" id="menu-movil" aria-hidden="true">
  @php $i = 0; @endphp
  @foreach ($menu as $item)
    <a class="big" style="--i:{{ $i++ }}" href="{{ $item->url ?: '#' }}">{{ $item->title }}</a>
    @if ($item->children->count())<div class="menu-sub">@foreach ($item->children as $c)<a href="{{ $c->url ?: '#' }}">{{ $c->title }}</a>@endforeach</div>@endif
  @endforeach
  <div class="foot"><span>{{ $C::email() }}</span>@foreach ($C::phones() as [$l])<span>{{ $l }}</span>@endforeach</div>
</div>
