@extends('admin.layout')
@section('title', 'Contact requests')

@section('content')
<h4 class="mb-1">Contact requests</h4>
<p class="text-muted mb-4">Messages sent through the form on the Contacto page.</p>
<div class="card">
  <div class="card-header border-bottom">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4"><label class="form-label small mb-1">Search</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Name, company, email, message"></div>
      <div class="col-md-3"><label class="form-label small mb-1">Status</label>
        <select class="form-select" name="status"><option value="">Any status</option>@foreach (\App\Models\ContactRequest::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
      <div class="col-md-4"><label class="form-label small mb-1">Created between</label><input class="form-control flatpickr-range" name="range" value="{{ request('range') }}" placeholder="Pick a date range"></div>
      <div class="col-auto"><button class="btn btn-primary">Filter</button> <a href="{{ route('admin.contacts.index') }}" class="btn btn-label-secondary">Reset</a></div>
    </form>
  </div>
  <div class="table-responsive"><table class="table">
    <thead><tr><th>From</th><th>Company</th><th>Message</th><th>Status</th><th>Received</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $c)
      <tr>
        <td><a class="fw-medium" href="{{ route('admin.contacts.show', $c) }}">{{ $c->nombre }}</a><br><small class="text-muted">{{ $c->email }}</small></td>
        <td>{{ $c->empresa }}</td>
        <td class="small text-muted">{{ \Illuminate\Support\Str::limit($c->mejora, 80) }}</td>
        <td><span class="badge bg-label-{{ ['new' => 'danger', 'contacted' => 'warning', 'closed' => 'secondary'][$c->status] ?? 'secondary' }}">{{ \App\Models\ContactRequest::STATUSES[$c->status] ?? $c->status }}</span></td>
        <td class="text-muted small">{{ $c->created_at->format('d M Y H:i') }}</td>
        <td class="text-end"><a class="btn btn-sm btn-label-primary" href="{{ route('admin.contacts.show', $c) }}">Open</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="text-muted p-4">No requests yet.</td></tr>
    @endforelse
    </tbody></table></div>
  <div class="card-footer">{{ $items->links() }}</div>
</div>
@endsection
