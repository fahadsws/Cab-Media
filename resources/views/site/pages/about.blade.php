@extends('site.layout')
@section('title', 'About Us | Cab Media')
@section('description', 'Cab Media is a team of experienced professionals in Communications, Advertising & Branding with 20+ years of experience.')
@section('path', '/about-us')

@section('content')
@php $C = \App\Support\SiteContent::class; @endphp
<section class="phead">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" data-reveal><a href="/">Home</a><span>/</span><b>About us</b></nav>
    <span class="eyebrow" data-reveal>About us</span>
    <h1 class="display split">Tailor made media &amp; marketing, for 20+ years.</h1>
    <p class="lead" data-reveal>A team of experienced professionals in Communications, Advertising &amp; Branding.</p>
  </div>
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

@endsection
