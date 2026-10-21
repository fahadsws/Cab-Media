@extends('site.layout')
@php $base = rtrim(config('site.url'), '/'); @endphp
@section('title', ($page->meta_title ?: $page->title).' | Cab Media')
@section('description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $page->body))), 155, ''))
@section('path', '/'.$page->slug)
@if ($page->canonical_url)@section('canonical', $page->canonical_url)@endif
@if (str_starts_with((string) $page->robots, 'noindex'))@section('robots', $page->robots)@endif
@if ($page->image_url)@section('ogimage', $page->image_url)@endif
@section('jsonld')
@if (filled($page->schema_json) && json_decode($page->schema_json) !== null)<script type="application/ld+json">{!! $page->schema_json !!}</script>@endif
@if (count($page->faqItems()))<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['a'])]], $page->faqItems())], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>@endif
@endsection

@section('content')
<section class="phead phead--light">
  <div class="wrap">
    <nav class="crumbs crumbs--dark" aria-label="Breadcrumb"><a href="/">Home</a><span>/</span><b>{{ $page->title }}</b></nav>
    <h1 class="display split" style="color:var(--text);font-size:clamp(2.2rem,5.4vw,4.4rem)">{{ $page->title }}</h1>
    @if ($page->excerpt)<p class="lead" data-reveal>{{ $page->excerpt }}</p>@endif
  </div>
</section>
<section class="sec" style="padding-top:clamp(2rem,4vw,3rem)">
  <div class="wrap {{ $page->template === 'full' ? '' : 'narrow' }}">
    @if ($page->image_url)<figure class="cover" data-reveal="zoom"><img src="{{ $page->image_url }}" alt="{{ $page->title }}"></figure>@endif
    <div class="article">{!! $page->body !!}</div>
    @if (count($page->faqItems()))
      <section class="faq-block"><h2>Frequently asked questions</h2><div class="faq">@foreach ($page->faqItems() as $f)<details><summary><span>{{ $f['q'] }}</span><i aria-hidden="true">+</i></summary><div class="ans"><div><p>{{ $f['a'] }}</p></div></div></details>@endforeach</div></section>
    @endif
  </div>
</section>
@endsection
