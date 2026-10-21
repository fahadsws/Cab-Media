@extends('admin.layout')
@section('title', 'Preview')

@section('content')
<div class="alert alert-info d-flex justify-content-between align-items-center"><span><i class="ti ti-eye me-1"></i>{{ $meta ?? 'Preview' }} - unstyled admin preview; the public design is not built yet.</span><a href="javascript:history.back()" class="btn btn-sm btn-label-secondary">Back</a></div>
<div class="card"><div class="card-body" style="max-width:860px">
  <h2 class="mb-3">{{ $title }}</h2>
  @if (! empty($image))<img src="{{ $image }}" alt="" class="img-fluid rounded mb-4">@endif
  <div class="preview-body">{!! $body !!}</div>
</div></div>
@endsection
