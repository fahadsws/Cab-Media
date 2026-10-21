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
        <a href="#contact" class="btn" data-magnetic>Start a brief <span class="arr">→</span></a>
        <a href="#services" class="link">Our services</a>
      </div>
    </div>

    <div class="flow" id="flow" data-state="chaos" data-reveal="zoom" style="--d:3" role="group" aria-label="Illustration: separate agencies versus one orchestrated partner">
      <div class="flow-bar">
                <div class="flow-tabs"><button type="button" data-state="chaos" aria-pressed="true">Without one partner</button><button type="button" data-state="order" aria-pressed="false">With Cab Media</button></div>
      </div>
      <div class="flow-stage"><svg aria-hidden="true"></svg></div>
      <div class="flow-cap" aria-live="polite"><span class="c-chaos"><b>Today:</b> separate agencies, the same brief repeated, mismatched messages and nobody who owns the whole picture.</span><span class="c-order"><b>With Cab Media:</b> one brief, one plan, one team across creative, media and digital. Tailor made for your brand.</span></div>
      <div class="flow-badge">One team</div>
    </div>
  </div>
  <a href="#about" class="scrollcue" aria-label="Scroll down"></a>
</section>

{{-- ============ MARQUEE ============ --}}
<section class="sec sec--dark big-marq" style="padding-block:clamp(2rem,4vw,3.2rem);overflow:hidden" aria-hidden="true">
  <div class="marq"><div class="marq-track">@foreach (['Creative', 'Advertising', 'Branding', 'Media Planning', 'PR', 'Digital'] as $w)<span>{{ $w }}</span>@endforeach</div></div>
</section>

{{-- ============ ABOUT ============ --}}
<section class="sec sec--cream" id="about">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow" data-reveal>About us</span>
      <h2 class="h2 split">How are we different?</h2>
    </div>
    <div class="split2">
      <div class="sticky">
        <p class="quote" data-reveal>We are a team of experienced professionals in the field of Communications, Advertising &amp; Branding.</p>
        <div class="chips" style="margin-top:1.6rem" data-reveal><span class="chip">Above the Line (ATL)</span><span class="chip">Below the Line (BTL)</span><span class="chip">National &amp; International</span></div>
      </div>
      <div class="prose">
        <p data-reveal>This professional experience spans for <strong>20 plus years</strong>, having associated with the top advertising / media / digital agencies in the country, the team is well equipped to handle any kind of “Media / Marketing Hurdles” faced by a brand under the sun, by providing an “appropriate” and “Cost effective” solution helping reach out to the right Target Audience, in the National &amp; International Markets.</p>
        <p data-reveal>In the current Media / Marketing scenario, where most agencies/brands adopt the “One size fits all” policy, we strongly believe that not everyone fits the same mold, and hence provide <strong>“Tailor Made”</strong> solutions for brands that not only would help it reach the right audience but also propel it to grow as expected.</p>
        <p data-reveal><strong>Services offered:</strong> (National &amp; International Markets) Above the Line (ATL) and Below the Line (BTL).</p>
      </div>
    </div>
    <div class="stats" style="margin-top:clamp(2.5rem,6vw,4.5rem)" data-reveal>
      <div class="stat"><b><span data-count="20">0</span>+</b><span>years of professional experience in communications, advertising &amp; branding.</span></div>
      <div class="stat"><b><span data-count="7">0</span></b><span>service lines under one roof: from creative to digital marketing.</span></div>
      <div class="stat"><b><span data-count="7">0</span></b><span>office locations across India and the USA.</span></div>
      <div class="stat"><b>ATL<small style="font-size:.4em;letter-spacing:0"> + </small>BTL</b><span>national and international campaigns, tailor made for each brand.</span></div>
    </div>
  </div>
</section>

{{-- ============ SERVICES ============ --}}
<section class="sec" id="services">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow" data-reveal>Services</span>
      <h2 class="h2 split">Everything your brand needs to be seen, grow and run, under one roof.</h2>
      <p class="lead" data-reveal>From a single poster to a full digital platform: pick an area, then open any service for the details.</p>
    </div>

    @php $Cat = \App\Support\ServicesCatalog::class; $catSlug = ['digital' => 'digital-marketing', 'development' => 'development', 'it' => 'it-services', 'ai' => 'ai-services']; @endphp
    <div class="svc-tabs" role="tablist" aria-label="Service areas" data-reveal>
      @foreach ($Cat::GROUPS as $g => [$gname])
        <button type="button" role="tab" data-group="{{ $g }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"><span>{{ $gname }}</span><em>{{ count($Cat::inGroup($g, true)) }}</em></button>
      @endforeach
    </div>

    @foreach ($Cat::GROUPS as $g => [$gname, $gdesc])
      <div class="svc-pane" data-group="{{ $g }}" @if (! $loop->first) hidden @endif role="tabpanel">
        <div class="svc-head">
          <div><h3 class="h3" style="font-size:1.5rem">{{ $gname }}</h3><p class="muted">{{ $gdesc }}</p></div>
          @if (isset($catSlug[$g]))<a href="{{ route('service.show', $catSlug[$g]) }}" class="link">Explore {{ $gname }}</a>@endif
        </div>
        <div class="svc-cards">
          @foreach ($Cat::inGroup($g, true) as $c)
            @include('site.partials.service-card', ['c' => $c, 'i' => $loop->index])
          @endforeach
        </div>
      </div>
    @endforeach

    <div class="svc-cta" data-reveal>
      <div><h3 class="h3" style="font-size:1.4rem">Not sure what you need?</h3><p>Tell us the brief. We will shape the right mix for your brand and your audience.</p></div>
      <a href="#contact" class="btn" data-magnetic>Start a brief <span class="arr">→</span></a>
    </div>
  </div>
</section>

{{-- ============ PRINCIPLE ============ --}}
<section class="sec sec--dark" style="overflow:hidden">
  <div class="wrap">
    <h2 class="fill" style="max-width:22ch">Not everyone fits the same mold. So we build tailor made, cost effective solutions that reach the right audience and help your brand grow.</h2>
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

{{-- ============ CONTACT ============ --}}
<section class="sec sec--dark" id="contact" style="overflow:hidden">
  <div class="wrap">
    <div class="sec-head">
      <span class="eyebrow" data-reveal>Contact us</span>
      <h2 class="h2 split">Let's talk about your brand.</h2>
    </div>
    <div class="contact-grid">
      <div data-reveal>
        <h3 class="h3" style="margin-bottom:1.2rem">Enquiry</h3>
        <form id="contact-form" class="form form--dark">
          <div class="hp" aria-hidden="true"><label for="web">Leave this field empty</label><input id="web" tabindex="-1" autocomplete="off" type="text" value="" name="web"></div>
          <div class="row2">
            <div class="field"><input id="f-nombre" placeholder=" " required type="text" name="nombre" autocomplete="name"><label for="f-nombre">Name</label></div>
            <div class="field"><input id="f-tel" placeholder=" " required type="tel" name="telefono" autocomplete="tel"><label for="f-tel">Phone number</label></div>
          </div>
          <div class="row2">
            <div class="field"><input id="f-email" placeholder=" " required type="email" name="email" autocomplete="email"><label for="f-email">Email</label></div>
            <div class="field"><input id="f-empresa" placeholder=" " required type="text" name="empresa" autocomplete="organization"><label for="f-empresa">Company name</label></div>
          </div>
          <div class="field"><textarea id="f-mejora" placeholder=" " name="mejora" rows="5" required></textarea><label for="f-mejora">Additional message</label></div>
          <div class="form-err" role="alert" hidden></div>
          <button type="submit" class="btn" data-magnetic><span>Submit</span> <span class="arr">→</span></button>
        </form>
      </div>
      <aside data-reveal style="--d:2">
        <h3 class="h3" style="margin-bottom:1.2rem">Our offices</h3>
        <ul class="offices">@foreach ($C::offices() as $o)<li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $o }}</li>@endforeach</ul>
        <div class="direct direct--dark" style="margin-top:1.6rem">
          <a href="mailto:{{ $C::email() }}"><span class="ico">@include('site.partials.icon', ['n' => 'mail'])</span><span><small>Email</small><b>{{ $C::email() }}</b></span></a>
          @foreach ($C::phones() as [$label, $tel])<a href="tel:{{ $tel }}"><span class="ico">@include('site.partials.icon', ['n' => 'phone'])</span><span><small>Phone</small><b>{{ $label }}</b></span></a>@endforeach
        </div>
      </aside>
    </div>
  </div>
</section>
@endsection
