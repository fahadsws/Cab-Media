{{-- Dashboard: most-read articles. Expects $topToday, $topWeek, $topAll (collections of ['article' => Article, 'views' => int]). --}}
<div class="card"><div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
  <div><h5 class="card-title mb-0"><i class="ti ti-flame text-danger me-1"></i>Top blog posts by views</h5><small class="text-muted">Trending: the most read posts</small></div>
  <ul class="nav nav-pills" role="tablist">
    <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#tt-today" type="button">Today</button></li>
    <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#tt-week" type="button">7 days</button></li>
    <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#tt-all" type="button">All time</button></li>
  </ul></div>
  <div class="tab-content p-0 shadow-none bg-transparent">
    @foreach (['today' => $topToday, 'week' => $topWeek, 'all' => $topAll] as $k => $rows)
    <div class="tab-pane fade {{ $k === 'today' ? 'show active' : '' }}" id="tt-{{ $k }}">
      <div class="table-responsive"><table class="table mb-0"><tbody>
        @forelse ($rows as $i => $row)
          <tr><td style="width:44px" class="fw-bold text-muted">#{{ $i + 1 }}</td>
            <td><a href="{{ route('admin.articles.edit', $row['article']) }}" class="fw-medium">{{ \Illuminate\Support\Str::limit($row['article']->title, 90) }}</a><br><small class="text-muted">{{ $row['article']->category?->name }}</small></td>
            <td class="text-end text-nowrap"><span class="badge bg-label-danger"><i class="ti ti-eye me-1"></i>{{ number_format($row['views']) }}</span></td></tr>
        @empty <tr><td class="text-muted p-4">No views recorded {{ $k === 'today' ? 'today yet' : 'yet' }}.</td></tr>
        @endforelse
      </tbody></table></div>
    </div>
    @endforeach
  </div></div>
