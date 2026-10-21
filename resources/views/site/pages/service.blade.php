@extends('site.layout')
@php
  use App\Support\ServicesCatalog as Cat;
  $isCat = $s['category'] ?? false;
  [$groupName, $groupDesc] = Cat::GROUPS[$s['group']];
  $siblings = array_values(array_filter(Cat::all(), fn ($x) => $x['slug'] !== $s['slug']));
  [$prev, $next] = Cat::neighbours($s['slug']);
  $quote = route('contact').'?service='.$s['slug'];
@endphp
@section('title', $s['title'].' | Cab Media')
@section('description', $s['tagline'].' '.$s['short'])
@section('path', '/services/'.$s['slug'])
@section('jsonld')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org',
  '@type' => 'Service',
  'name' => $s['title'],
  'description' => $s['short'],
  'provider' => ['@type' => 'Organization', 'name' => 'Cab Media', 'url' => rtrim(config('site.url'), '/')],
  'areaServed' => ['India', 'USA'],
  'serviceType' => $groupName,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@if (! empty($s['faq']))
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $s['faq'])], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endsection

@section('content')
{{-- ============ HERO ============ --}}
<section class="phead">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" data-reveal>
      <a href="/">Home</a><span>/</span><a href="/#services">Services</a><span>/</span>
      <b>{{ $s['title'] }}</b>
    </nav>
    <div class="phead-grid">
      <div>
        <span class="eyebrow" data-reveal>{{ $groupName }}</span>
        <h1 class="display split">{{ $s['title'] }}</h1>
        <p class="lead" data-reveal>{{ $s['tagline'] }}</p>
        <div class="hero-actions" data-reveal style="--d:2"><a href="{{ $quote }}" class="btn" data-magnetic>Get a quote <span class="arr">→</span></a><a href="/#services" class="link">All services</a></div>
      </div>
      <div class="phead-ico" data-reveal="zoom" aria-hidden="true">@include('site.partials.icon', ['n' => $s['icon']])</div>
    </div>
  </div>
</section>

{{-- ============ INTRO ============ --}}
<section class="sec">
  <div class="wrap split2">
    <div class="prose">
      <h2 class="h2 split" style="margin-bottom:1.6rem">{{ $isCat ? 'What we do' : 'What it is, and how we do it' }}</h2>
      @foreach ($s['intro'] as $p)<p data-reveal>{{ $p }}</p>@endforeach
      @if (! $isCat)
        <p data-reveal><a href="{{ $quote }}" class="link">Tell us about your project</a></p>
      @endif
    </div>
    <aside class="side sticky" data-reveal style="--d:1">
      <div class="side-card">
        <h3 class="h3">All services</h3>
        <ul>
          @foreach ($siblings as $x)<li><a href="{{ route('service.show', $x['slug']) }}"><span class="ico">@include('site.partials.icon', ['n' => $x['icon']])</span>{{ $x['title'] }}</a></li>@endforeach
        </ul>
      </div>
      <div class="side-card side-card--cta">
        <h3 class="h3">Talk to Cab Media</h3>
        <p>Share a short brief and we will come back with a recommendation.</p>
        <a href="{{ $quote }}" class="btn">Start a brief <span class="arr">→</span></a>
        <small>{{ \App\Support\SiteContent::email() }} · {{ \App\Support\SiteContent::phones()[0][0] }}</small>
      </div>
    </aside>
  </div>
</section>

@if ($isCat)
  {{-- ============ CHILDREN ============ --}}
  <section class="sec sec--cream">
    <div class="wrap">
      <div class="sec-head"><span class="eyebrow" data-reveal>{{ $groupName }}</span><h2 class="h2 split">Choose a service.</h2></div>
      <div class="svc-cards">
        @foreach (Cat::children($s['slug']) as $i => $c)
          @include('site.partials.service-card', ['c' => $c, 'i' => $i])
        @endforeach
      </div>
    </div>
  </section>
@else
  {{-- ============ OFFERS ============ --}}
  <section class="sec sec--cream">
    <div class="wrap">
      <div class="sec-head"><span class="eyebrow" data-reveal>What is included</span><h2 class="h2 split">Everything under {{ $s['title'] }}.</h2></div>
      <div class="bento offers">
        @foreach ($s['offers'] as $i => [$t, $d])
          <article class="tile" data-reveal style="--d:{{ $i % 3 }}">
            <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
            <h3 class="h3" style="margin:.5rem 0 .6rem">{{ $t }}</h3>
            <p>{{ $d }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ============ PROCESS ============ --}}
  <section class="sec">
    <div class="wrap">
      <div class="sec-head"><span class="eyebrow" data-reveal>How we work</span><h2 class="h2 split">A clear path from brief to result.</h2></div>
      <div class="timeline" style="--n:{{ count($s['steps']) }}">
        @foreach ($s['steps'] as $i => [$t, $d])
          <div class="tl" data-reveal style="--d:{{ $i % 4 }}"><span class="dot">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><h3 class="h3">{{ $t }}</h3><p>{{ $d }}</p></div>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- ============ BENEFITS ============ --}}
<section class="sec sec--dark">
  <div class="wrap">
    <div class="sec-head"><span class="eyebrow" data-reveal>Why it matters</span><h2 class="h2 split">What you get.</h2></div>
    <ul class="checks">
      @foreach ($s['benefits'] as $i => $b)<li data-reveal style="--d:{{ $i % 2 }}"><i aria-hidden="true">✓</i>{{ $b }}</li>@endforeach
    </ul>
  </div>
</section>

@if (! empty($s['faq']))
  {{-- ============ FAQ ============ --}}
  <section class="sec sec--cream">
    <div class="wrap">
      <div class="sec-head"><span class="eyebrow" data-reveal>FAQ</span><h2 class="h2 split">Questions we are often asked.</h2></div>
      <div class="faq" data-reveal>
        @foreach ($s['faq'] as [$q, $a])
          <details><summary><span>{{ $q }}</span><i aria-hidden="true">+</i></summary><div class="ans"><div><p>{{ $a }}</p></div></div></details>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- ============ CTA + pager ============ --}}
<section class="sec cta">
  <div class="wrap cta-in">
    <div data-reveal>
      <h2 class="display split" style="font-size:clamp(2rem,5.4vw,4.4rem);max-width:16ch">Ready to talk about {{ $s['title'] }}?</h2>
      <p class="lead" style="margin-top:1.2rem">Send us a short brief and the Cab Media team will respond with a clear recommendation.</p>
    </div>
    <div data-reveal style="--d:2"><a href="{{ $quote }}" class="btn" data-magnetic>Get a quote <span class="arr">→</span></a></div>
  </div>
</section>
<section class="pager">
  <div class="wrap pager-in">
    <a href="{{ route('service.show', $prev['slug']) }}"><small>← Previous</small><b>{{ $prev['title'] }}</b></a>
    <a href="{{ route('service.show', $next['slug']) }}" class="r"><small>Next →</small><b>{{ $next['title'] }}</b></a>
  </div>
</section>
@endsection
