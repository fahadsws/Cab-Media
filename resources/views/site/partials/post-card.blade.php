<a href="{{ $a->url }}" class="post tile" data-reveal style="--d:{{ ($i ?? 0) % 3 }}">
  <div class="post-img @unless ($a->has_image) post-img--ph @endunless">
    @if ($a->has_image)<img src="{{ $a->image_url }}" alt="" loading="lazy">@else<span aria-hidden="true">{{ strtoupper(substr($a->category?->name ?? 'C', 0, 1)) }}</span>@endif
    @if ($a->category)<em class="post-tag">{{ $a->category->name }}</em>@endif
  </div>
  <div class="post-body">
    <h3 class="h3">{{ $a->title }}</h3>
    @if ($a->excerpt)<p>{{ \Illuminate\Support\Str::limit($a->excerpt, 140) }}</p>@endif
    <div class="post-meta"><time datetime="{{ $a->published_at?->toDateString() }}">{{ $a->published_at?->format('d M Y') }}</time><span>·</span><span>{{ $a->read_minutes }} min read</span></div>
  </div>
</a>
