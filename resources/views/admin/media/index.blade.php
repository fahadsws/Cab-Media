@extends('admin.layout')
@section('title', 'Media library')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <h4 class="mb-0">Media library</h4>
  <span class="text-muted small">Drag &amp; drop files anywhere below to upload. Click a file to select it (Ctrl-click for several), then copy its URL or delete it.</span>
</div>
<div class="card"><div class="card-body" data-ml-page>
  @include('admin.media.browser', ['pick' => false])
</div></div>
@endsection
