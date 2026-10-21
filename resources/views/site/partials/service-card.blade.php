<a href="{{ route('service.show', $c['slug']) }}" class="tile svc-card" data-reveal style="--d:{{ ($i ?? 0) % 3 }}">
  <div class="ico">@include('site.partials.icon', ['n' => $c['icon']])</div>
  <h3 class="h3">{{ $c['title'] }}</h3>
  <p>{{ $c['short'] }}</p>
  <span class="go" aria-hidden="true">Learn more <i>→</i></span>
</a>
