@php
  use App\Models\Setting;
  $C = \App\Support\SiteContent::class;
  $tree = $C::menu('footer');
  $cols = $tree->filter(fn ($i) => $i->children->count());
  $loose = $tree->filter(fn ($i) => ! $i->children->count());
  $wm = Setting::get('site.logo') ?: '/img/cabmedia-logo.jpg';
  $note = str_replace('{year}', date('Y'), Setting::get('site.footer_note', '© {year} Cab Media. All rights reserved.'));
  $fctaText = Setting::get('site.footer_cta_text', 'Start a brief');
  $fctaUrl = \App\Support\SiteContent::fixUrl(Setting::get('site.footer_cta_url', '/contact-us'));
  $name = Setting::get('site.name', 'Cab Media');
@endphp
<footer class="foot">
  <div class="wrap">
    <div class="foot-main">
      <div class="foot-brand">
        <a href="/#home" class="brand" aria-label="{{ $name }}, home"><img class="wm" src="{{ $wm }}" alt="{{ $name }}" height="64"></a>
        <p class="tagline">{{ $C::tagline() }}</p>
        <a href="{{ $fctaUrl }}" class="btn">{{ $fctaText }} <span class="arr">→</span></a>
      </div>
      @if ($loose->count())
        <div><h4>Explore</h4><ul>@foreach ($loose as $l)<li><a href="{{ $l->url ?: '#' }}" @if ($l->open_new_tab) target="_blank" rel="noopener" @endif>{{ $l->title }}</a></li>@endforeach</ul></div>
      @endif
      @foreach ($cols as $col)
        <div><h4>{{ $col->title }}</h4><ul>@foreach ($col->children as $l)<li><a href="{{ $l->url ?: '#' }}" @if ($l->open_new_tab) target="_blank" rel="noopener" @endif>{{ $l->title }}</a></li>@endforeach</ul></div>
      @endforeach
      <div class="foot-contact">
        <h4>Contact</h4>
        <ul>
          <li><a href="mailto:{{ $C::email() }}">{{ $C::email() }}</a></li>
          @foreach ($C::phones() as [$label, $tel])<li><a href="tel:{{ $tel }}">{{ $label }}</a></li>@endforeach
          <li><span>{{ implode(' · ', $C::offices()) }}</span></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="foot-word" aria-hidden="true">{{ $name }}</div>
  <div class="wrap">
    <div class="foot-bar">
      <span>{{ $note }}</span>
      <nav aria-label="Back to top"><a href="/#home">Back to top ↑</a></nav>
    </div>
  </div>
</footer>
