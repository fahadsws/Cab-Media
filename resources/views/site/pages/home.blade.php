@extends('site.layout')
@section('title', 'Cab Media | One Stop Solution for All Your Media & Marketing Needs')
@section('description', 'Cab Media - One Stop Solution for All Your Media & Marketing Needs. Communications, Advertising & Branding for national and international markets.')
@section('path', '/')
@section('jsonld')
@include('site.jsonld.home')
@endsection

@section('content')
@php $C = \App\Support\SiteContent::class; @endphp

{{-- ============ HOME / HERO ============ --}}
<section class="hero" id="home">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow" data-reveal>Communications · Advertising · Branding</span>
      <h1 class="display" data-reveal style="--d:1">One stop solution for all your <span class="accent">media</span> &amp; <span class="accent">marketing</span> needs.</h1>
      <p class="lead" data-reveal style="--d:2">We are a team of experienced professionals in the field of Communications, Advertising &amp; Branding.</p>
      <p class="rot" data-reveal style="--d:3"><span data-words="{{ implode('|', $C::SLIDES) }}">{{ $C::SLIDES[0] }}</span></p>
      <div class="hero-actions" data-reveal style="--d:4">
        <a href="{{ route('contact') }}" class="btn" data-magnetic>Start a brief <span class="arr">→</span></a>
        <a href="#services" class="link">Our services</a>
      </div>
    </div>

    <div class="flow" id="flow" data-state="order" data-reveal="zoom" style="--d:3" role="group" aria-label="Illustration: separate agencies versus one orchestrated partner">
      <div class="flow-bar">
                <div class="flow-tabs"><button type="button" data-state="chaos" aria-pressed="false">Without partner</button><button type="button" data-state="order" aria-pressed="true">With Cab Media</button></div>
      </div>
      <div class="flow-stage"><svg aria-hidden="true"></svg></div>
      <div class="flow-cap" aria-live="polite"><span class="c-chaos"><b>Today:</b> separate agencies, the same brief repeated, mismatched messages and nobody who owns the whole picture.</span><span class="c-order"><b>With Cab Media:</b> one brief, one plan, one team across creative, media and digital. Tailor made for your brand.</span></div>
      <div class="flow-badge">One team</div>
    </div>
  </div>
  <a href="#services" class="scrollcue" aria-label="Scroll down"></a>
</section>

{{-- ============ MARQUEE ============ --}}
<section class="sec sec--dark big-marq" style="padding-block:clamp(2rem,4vw,3.2rem);overflow:hidden" aria-hidden="true">
  <div class="marq"><div class="marq-track">@foreach (['Creative', 'Advertising', 'Branding', 'Media Planning', 'PR', 'Digital'] as $w)<span>{{ $w }}</span>@endforeach</div></div>
</section>

{{-- ============ SERVICES ============ --}}
<section class="sec" id="services">
  <div class="wrap">
    <div class="sec-head" style="max-width:none">
      <span class="eyebrow" data-reveal>Services</span>
      <h2 class="h2 split" style="max-width:none">Everything your brand needs to be seen, grow and run, under one roof.</h2>
      <p class="lead" data-reveal>From a single poster to a full digital platform: open any service for the details.</p>
    </div>

    @php $Cat = \App\Support\ServicesCatalog::class; @endphp
    <div class="svc-grid">
      @foreach ($Cat::CARDS as $slug => [$img, $items])
        @php $svc = $Cat::find($slug); @endphp
        <a href="{{ route('service.show', $slug) }}" class="svc-tile" data-reveal style="--d:{{ $loop->index % 3 }}">
          <span class="svc-img"><img src="https://images.unsplash.com/photo-{{ $img }}?auto=format&fit=crop&w=700&q=70" alt="" loading="lazy" width="700" height="440"></span>
          <h3>{{ $svc['title'] }}</h3>
          <ul>@foreach ($items as $it)<li>{{ $it }}</li>@endforeach</ul>
          <span class="go" aria-hidden="true">Learn more <i>→</i></span>
        </a>
      @endforeach
    </div>

    <div class="svc-cta" data-reveal>
      <div><h3 class="h3" style="font-size:1.4rem">Not sure what you need?</h3><p>Tell us the brief. We will shape the right mix for your brand and your audience.</p></div>
      <a href="{{ route('contact') }}" class="btn" data-magnetic>Start a brief <span class="arr">→</span></a>
    </div>
  </div>
</section>

{{-- ============ PRINCIPLE ============ --}}
<section class="sec sec--dark" style="overflow:hidden">
  <div class="wrap">
    <h2 class="fill" style="max-width:22ch">Not everyone fits the same mold. So we build tailor made, cost effective solutions that reach the right audience and help your brand grow.</h2>
  </div>
</section>

{{-- ============ OFFICES ============ --}}
<section class="sec" id="offices">
  <div class="wrap">
    <div class="sec-head" style="max-width:none">
      <span class="eyebrow" data-reveal>Our offices</span>
      <h2 class="h2 split">Seven cities, one team, wherever your audience is.</h2>
    </div>
    @php $offs = $C::offices(); @endphp
    <div class="ogal" data-reveal>
      @foreach ($offs as $o)
        @php $ph = $C::OFFICE_PHOTOS[strtolower($o)] ?? null; @endphp
        <article class="ogi @if ($loop->first) on @endif" tabindex="0" aria-label="{{ $o }} office">
          @if ($ph)<img src="https://images.unsplash.com/photo-{{ $ph[1] }}?auto=format&fit=crop&w=900&q=70" alt="{{ $ph[0] }}, {{ $o }}" loading="lazy" width="900" height="1100">@endif
          <span class="ogi-v" aria-hidden="true">{{ $o }}</span>
          <div class="ogi-in">
            <span class="ogi-no">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <h3>{{ $o }}</h3>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ TESTIMONIALS ============ --}}
<section class="sec sec--cream tst-sec" id="testimonials">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow" data-reveal>Testimonials</span>
      <h2 class="h2 split">Brands that grew with Cab Media.</h2>
    </div>
    <div class="tst" data-reveal>
      <div class="tst-list" role="tablist" aria-label="Clients">
        @foreach ($C::TESTIMONIALS as $i => [$q, $name, $co])
          <button type="button" role="tab" class="tst-tab @if ($i === 0) on @endif" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" data-i="{{ $i }}">
            <span class="av">{{ strtoupper(substr($co ?: $name, 0, 1)) }}</span>
            <span class="who"><b>{{ $name }}</b>@if ($co)<small>{{ $co }}</small>@endif</span>
            <span class="bar" aria-hidden="true"></span>
          </button>
        @endforeach
      </div>
      <div class="tst-stage">
        <span class="orb" aria-hidden="true"></span>
        @foreach ($C::TESTIMONIALS as $i => [$q, $name, $co])
          <figure class="tst-card @if ($i === 0) on @endif" role="tabpanel">
            <span class="qmark" aria-hidden="true">“</span>
            <blockquote class="{{ strlen($q) > 220 ? 'long' : '' }}">{{ $q }}</blockquote>
            <figcaption><b>{{ $name }}</b>@if ($co)<small>{{ $co }}</small>@endif</figcaption>
          </figure>
        @endforeach
        <div class="tst-ctl">
          <span class="count"><b id="tst-n">1</b> / {{ count($C::TESTIMONIALS) }}</span>
          <button type="button" class="tsl-btn" data-dir="-1" aria-label="Previous testimonial"><svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></button>
          <button type="button" class="tsl-btn" data-dir="1" aria-label="Next testimonial"><svg viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></button>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============ BLOG ============ --}}
@php $latest = \App\Models\Article::published()->with('category')->latest('published_at')->take(3)->get(); @endphp
@if ($latest->count())
<section class="sec" id="blog">
  <div class="wrap">
    <div class="sec-head" style="max-width:none;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:1.5rem">
      <div><span class="eyebrow" data-reveal>From the blog</span><h2 class="h2 split" style="margin-top:1rem">Ideas, news and practical guides.</h2></div>
      <a href="{{ route('blog.index') }}" class="link" data-reveal>View all articles</a>
    </div>
    <div class="posts">@foreach ($latest as $i => $a)@include('site.partials.post-card', ['a' => $a, 'i' => $i])@endforeach</div>
  </div>
</section>
@endif

@endsection
