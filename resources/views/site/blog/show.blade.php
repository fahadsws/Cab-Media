@extends('site.layout')
@php
  $a = $article;
  $desc = $a->meta_description ?: ($a->excerpt ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $a->body))), 155, ''));
  $base = rtrim(config('site.url'), '/');
  $url = $base.'/blog/'.$a->slug;
  $img = $a->has_image ? (str_starts_with($a->image_url, 'http') ? $a->image_url : $base.parse_url($a->image_url, PHP_URL_PATH)) : null;
@endphp
@section('title', ($a->meta_title ?: $a->title).' | Cab Media')
@section('description', $desc)
@section('path', '/blog/'.$a->slug)
@section('canonical', $a->canonical_url ?: $url)
@section('ogtype', 'article')
@if ($img)@section('ogimage', $img)@endif
@if ($a->og_title)@section('ogtitle', $a->og_title)@endif
@if ($a->og_description)@section('ogdesc', $a->og_description)@endif
@if (str_starts_with((string) $a->robots, 'noindex'))@section('robots', $a->robots)@endif
@section('jsonld')
@if (filled($a->schema_json) && json_decode($a->schema_json) !== null)
<script type="application/ld+json">{!! $a->schema_json !!}</script>
@elseif ($a->schema_type !== 'None')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org', '@type' => $a->schema_type ?: 'BlogPosting', 'headline' => $a->title, 'description' => $desc,
  'image' => $img, 'datePublished' => $a->published_at?->toIso8601String(), 'dateModified' => $a->updated_at?->toIso8601String(),
  'author' => ['@type' => 'Organization', 'name' => 'Cab Media'],
  'publisher' => ['@type' => 'Organization', 'name' => 'Cab Media', 'logo' => ['@type' => 'ImageObject', 'url' => $base.'/img/cabmedia-logo.jpg']],
  'mainEntityOfPage' => $url, 'articleSection' => $a->category?->name, 'keywords' => $a->tags ? implode(', ', $a->tags) : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@php $faq = collect($a->faq ?? [])->filter(fn ($f) => filled($f['q'] ?? null) && filled($f['a'] ?? null))->values(); @endphp
@if ($faq->count())
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faq->map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['a'])]])->all()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endsection

@section('content')
<section class="phead phead--post">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" data-reveal><a href="/">Home</a><span>/</span><a href="{{ route('blog.index') }}">Blog</a>@if ($a->category)<span>/</span><a href="{{ route('blog.category', $a->category) }}">{{ $a->category->name }}</a>@endif</nav>
    <h1 class="display split" style="max-width:22ch">{{ $a->title }}</h1>
    <div class="post-meta post-meta--hero" data-reveal style="--d:2">
      @if ($a->category)<a class="post-tag" href="{{ route('blog.category', $a->category) }}">{{ $a->category->name }}</a>@endif
      <time datetime="{{ $a->published_at->toDateString() }}">{{ $a->published_at->format('d F Y') }}</time><span>·</span><span>{{ $a->read_minutes }} min read</span>
    </div>
  </div>
</section>

<section class="sec" style="padding-top:clamp(2rem,4vw,3rem)">
  <div class="wrap article-wrap">
    <article class="article-col">
      @if ($a->has_image)<figure class="cover" data-reveal="zoom"><img src="{{ $a->image_url }}" alt="{{ $a->title }}"></figure>@endif

      @if (! empty($a->tldr))
        <aside class="tldr" data-reveal><b>In short</b><ul>@foreach ($a->tldr as $t)<li>{{ $t }}</li>@endforeach</ul></aside>
      @endif

      <div class="article" id="article-body">{!! $a->body !!}</div>

      @if ($faq->count())
        <section class="faq-block"><h2>Frequently asked questions</h2>
          <div class="faq">@foreach ($faq as $f)<details><summary><span>{{ $f['q'] }}</span><i aria-hidden="true">+</i></summary><div class="ans"><div><p>{{ $f['a'] }}</p></div></div></details>@endforeach</div>
        </section>
      @endif

      @if ($a->source_url || ! empty($a->source_links))
        <p class="source">Source{{ ! empty($a->source_links) && count($a->source_links) > 1 ? 's' : '' }}:
          @if (! empty($a->source_links))@foreach ($a->source_links as $l)<a href="{{ $l['url'] ?? '#' }}" target="_blank" rel="noopener nofollow">{{ $l['outlet'] ?? parse_url($l['url'] ?? '', PHP_URL_HOST) }}</a>@if (! $loop->last), @endif @endforeach
          @else<a href="{{ $a->source_url }}" target="_blank" rel="noopener nofollow">{{ parse_url($a->source_url, PHP_URL_HOST) }}</a>@endif
        </p>
      @endif

      @if (! empty($a->tags))<div class="tags">@foreach ($a->tags as $t)<span>#{{ $t }}</span>@endforeach</div>@endif

      <div class="share" data-share="{{ $url }}" data-title="{{ $a->title }}">
        <b>Share</b>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($url) }}" target="_blank" rel="noopener">LinkedIn</a>
        <a href="https://twitter.com/intent/tweet?url={{ urlencode($url) }}&text={{ urlencode($a->title) }}" target="_blank" rel="noopener">X</a>
        <a href="https://wa.me/?text={{ urlencode($a->title.' '.$url) }}" target="_blank" rel="noopener">WhatsApp</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}" target="_blank" rel="noopener">Facebook</a>
        <button type="button" data-copy>Copy link</button>
      </div>
    </article>

    <aside class="article-side">
      <div class="side-card toc-card" id="toc-card" hidden><h3 class="h3">On this page</h3><nav id="toc" aria-label="Table of contents"></nav></div>
      <div class="side-card side-card--cta">
        <h3 class="h3">Need help with this?</h3>
        <p>Share a short brief and the Cab Media team will respond with a clear recommendation.</p>
        <a href="{{ route('contact') }}" class="btn">Start a brief <span class="arr">→</span></a>
      </div>
    </aside>
  </div>
</section>

@if ($related->count())
<section class="sec sec--cream">
  <div class="wrap">
    <div class="sec-head"><span class="eyebrow" data-reveal>Keep reading</span><h2 class="h2 split">More from the blog.</h2></div>
    <div class="posts">@foreach ($related as $i => $r)@include('site.partials.post-card', ['a' => $r, 'i' => $i])@endforeach</div>
  </div>
</section>
@endif
@endsection
