@extends('site.layout')
@section('title', 'Contact Us | Cab Media')
@section('description', 'Get in touch with Cab Media. Share your brief and we will come back with a recommendation.')
@section('path', '/contact-us')

@section('content')
@php $C = \App\Support\SiteContent::class; @endphp
<section class="phead">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb" data-reveal><a href="/">Home</a><span>/</span><b>Contact us</b></nav>
    <span class="eyebrow" data-reveal>Contact us</span>
    <h1 class="display split">Let's talk about your brand.</h1>
    <p class="lead" data-reveal>Share a short brief and the Cab Media team will respond.</p>
  </div>
</section>

{{-- ============ CONTACT ============ --}}
<section class="sec sec--dark" id="contact" style="overflow:hidden">
  <div class="wrap">
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
