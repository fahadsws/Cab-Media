{{-- Media browser markup. Behaviour lives in public/js/media-library.js. $pick = true renders the compact picker variant. --}}
<div class="ml-top d-flex flex-wrap align-items-center gap-2 mb-3">
  <div class="btn-group btn-group-sm">
    @foreach ($roots as $key => $cfg)
      <button type="button" class="btn btn-outline-primary {{ $loop->first ? 'active' : '' }}" data-root="{{ $key }}">{{ $cfg['label'] }}</button>
    @endforeach
  </div>
  <button type="button" class="btn btn-sm btn-label-secondary" data-ml-up title="Up one folder"><i class="ti ti-arrow-up"></i></button>
  <div class="ml-crumbs flex-grow-1" data-ml-crumbs></div>
  <input type="search" class="form-control form-control-sm" style="max-width:180px" placeholder="Search this folder" data-ml-search>
  <button type="button" class="btn btn-sm btn-label-primary" data-ml-newfolder><i class="ti ti-folder-plus me-1"></i>New folder</button>
  <label class="btn btn-sm btn-primary mb-0"><i class="ti ti-upload me-1"></i>Upload<input type="file" class="d-none" multiple accept="image/*,video/mp4,video/webm,application/pdf" data-ml-upload></label>
</div>
<div class="small text-muted mb-2" data-ml-status></div>
@unless ($pick ?? false)
  <div class="ml-bar d-none alert alert-primary d-flex align-items-center gap-2 py-2" data-ml-bar>
    <b data-ml-selcount></b>
    <button type="button" class="btn btn-sm btn-label-secondary ms-auto" data-ml-copy><i class="ti ti-copy me-1"></i>Copy URL</button>
    <button type="button" class="btn btn-sm btn-label-danger" data-ml-delete><i class="ti ti-trash me-1"></i>Delete</button>
    <button type="button" class="btn btn-sm btn-text-secondary" data-ml-clear>Clear</button>
  </div>
@endunless
<div class="ml-grid" data-ml-grid></div>
<div class="d-flex justify-content-center align-items-center mt-3" data-ml-pages></div>
