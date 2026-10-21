@extends('site.layout')
@section('title', ($category ? $category->name.' | ' : '').'Blog | Cab Media')
@section('description', $category ? 'Articles on '.$category->name.' from the Cab Media blog.' : 'News, ideas and practical guides on advertising, branding, media and digital marketing from the Cab Media team.')
@section('path', $category ? '/blog/category/'.$category->slug : '/blog')

@section('content')
<section class="phead">
  <div class="wrap">
    <span class="eyebrow" data-reveal>The Cab Media blog</span>
    <h1 class="display split">{{ $category ? $category->name : 'Ideas for brands that want to grow.' }}</h1>
    <p class="lead" data-reveal>News, ideas and practical guides on advertising, branding, media and digital marketing.</p>
    <form class="blog-search" action="{{ route('blog.index') }}" method="get" role="search" data-reveal style="--d:2">
      <input type="search" name="q" value="{{ $q }}" placeholder="Search articles" aria-label="Search articles">
      <button type="submit" class="btn">Search <span class="arr">→</span></button>
    </form>
  </div>
</section>

<section class="sec sec--cream" style="padding-top:clamp(2.5rem,5vw,4rem)">
  <div class="wrap">
    @if ($categories->count())
      <div class="chipbar" data-reveal>
        <a href="{{ route('blog.index') }}" class="{{ ! $category ? 'on' : '' }}">All</a>
        @foreach ($categories as $c)<a href="{{ route('blog.category', $c) }}" class="{{ $category && $category->id === $c->id ? 'on' : '' }}">{{ $c->name }} <em>{{ $c->published_count }}</em></a>@endforeach
      </div>
    @endif

    @if ($featured)
      <a href="{{ $featured->url }}" class="post-feature" data-reveal="zoom">
        <div class="pf-img @unless ($featured->has_image) post-img--ph @endunless">
          @if ($featured->has_image)<img src="{{ $featured->image_url }}" alt="">@else<span aria-hidden="true">{{ strtoupper(substr($featured->category?->name ?? 'C', 0, 1)) }}</span>@endif
        </div>
        <div class="pf-body">
          <span class="eyebrow">Featured{{ $featured->category ? ' · '.$featured->category->name : '' }}</span>
          <h2 class="h2">{{ $featured->title }}</h2>
          @if ($featured->excerpt)<p class="lead">{{ \Illuminate\Support\Str::limit($featured->excerpt, 220) }}</p>@endif
          <div class="post-meta"><time datetime="{{ $featured->published_at?->toDateString() }}">{{ $featured->published_at?->format('d M Y') }}</time><span>·</span><span>{{ $featured->read_minutes }} min read</span></div>
          <span class="link">Read the article</span>
        </div>
      </a>
    @endif

    @if ($posts->count())
      <div class="posts">@foreach ($posts as $i => $a)@include('site.partials.post-card', ['a' => $a, 'i' => $i])@endforeach</div>
      @if ($posts->hasPages())
        <nav class="pagination" aria-label="Pages">
          @if ($posts->onFirstPage())<span class="dis">← Newer</span>@else<a href="{{ $posts->previousPageUrl() }}" rel="prev">← Newer</a>@endif
          <span class="cur">Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>
          @if ($posts->hasMorePages())<a href="{{ $posts->nextPageUrl() }}" rel="next">Older →</a>@else<span class="dis">Older →</span>@endif
        </nav>
      @endif
    @elseif (! $featured)
      <div class="empty" data-reveal>
        <div class="ico">@include('site.partials.icon', ['n' => 'news'])</div>
        <h2 class="h3">{{ $q !== '' ? 'No articles match “'.$q.'”.' : 'Articles are on their way.' }}</h2>
        <p class="muted">{{ $q !== '' ? 'Try a different word, or browse all articles.' : 'We are preparing our first posts. In the meantime, tell us about your brief.' }}</p>
        <a href="{{ $q !== '' ? route('blog.index') : route('contact') }}" class="btn">{{ $q !== '' ? 'All articles' : 'Contact us' }} <span class="arr">→</span></a>
      </div>
    @endif
  </div>
</section>
@endsection
