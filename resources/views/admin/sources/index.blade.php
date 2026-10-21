@extends('admin.layout')
@section('title', 'Blog sources')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
  <h4 class="mb-0">Blog sources</h4>
  <a href="{{ route('admin.sources.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Add source</a>
</div>
<p class="text-muted mb-4">Sites and feeds the blog automation reads. Stories about the same topic from several outlets are merged into one original post and every outlet is credited.</p>
<div class="card dt-wrap">
  <div class="card-header border-bottom"><div class="row g-3 align-items-end">
    <div class="col-6 col-md-3 col-xl"><label class="form-label small mb-1">Type</label><select class="dt-filter" name="type" data-search="off"><option value="">All types</option><option value="rss">RSS / Atom</option><option value="page">Listing page</option></select></div>
    <div class="col-6 col-md-3 col-xl"><label class="form-label small mb-1">Status</label><select class="dt-filter" name="status" data-search="off"><option value="">Any status</option><option value="active">Active</option><option value="paused">Paused</option><option value="error">Last run failed</option></select></div>
    <div class="col-auto"><button type="button" class="btn btn-label-secondary dt-reset"><i class="ti ti-refresh me-1"></i>Reset</button></div>
  </div></div>
  @include('admin.partials.dt', ['url' => route('admin.sources.data'), 'order' => [[0, 'asc']], 'columns' => [
    ['title' => 'Source', 'orderable' => true], ['title' => 'Type', 'class' => 'd-none d-lg-table-cell'], ['title' => 'URL', 'class' => 'd-none d-xxl-table-cell'], ['title' => 'Status'], ['title' => 'Last run', 'orderable' => true], ['title' => '', 'class' => 'text-end'],
  ]])
</div>
@endsection
