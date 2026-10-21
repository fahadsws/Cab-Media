@extends('admin.layout')
@section('title', 'Contact request')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Request · {{ $c->nombre }}</h4>
  <a href="{{ route('admin.contacts.index') }}" class="btn btn-label-secondary">Back</a>
</div>
<div class="row g-4">
  <div class="col-lg-8"><div class="card"><div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $c->nombre }}</dd>
      <dt class="col-sm-3">Company</dt><dd class="col-sm-9">{{ $c->empresa }}</dd>
      <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><a href="mailto:{{ $c->email }}">{{ $c->email }}</a></dd>
      <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">{{ $c->telefono ?: '—' }}</dd>
      <dt class="col-sm-3">What to improve</dt><dd class="col-sm-9" style="white-space:pre-wrap">{{ $c->mejora }}</dd>
      <dt class="col-sm-3">Privacy accepted</dt><dd class="col-sm-9">{{ $c->acepta_privacidad ? 'Yes' : 'No' }} · {{ $c->fecha_aceptacion?->format('d M Y H:i') }}</dd>
      <dt class="col-sm-3">Received</dt><dd class="col-sm-9">{{ $c->created_at->format('d M Y H:i') }} · {{ $c->ip }}</dd>
    </dl>
  </div></div></div>
  <div class="col-lg-4"><div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.contacts.update', $c) }}">@csrf @method('PUT')
      <label class="form-label">Status</label>
      <select class="form-select mb-3" name="status">@foreach (\App\Models\ContactRequest::STATUSES as $k => $v)<option value="{{ $k }}" @selected($c->status === $k)>{{ $v }}</option>@endforeach</select>
      <button class="btn btn-primary">Save</button>
    </form>
    <hr><form method="POST" action="{{ route('admin.contacts.destroy', $c) }}" data-confirm="Delete this request permanently?">@csrf @method('DELETE')<button class="btn btn-label-danger">Delete</button></form>
  </div></div></div>
</div>
@endsection
