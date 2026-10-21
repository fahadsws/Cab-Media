<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-red" data-assets-path="{{ asset('vuexy') }}/" data-template="vertical-menu-template" data-style="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex,nofollow">
  <title>@yield('title', 'Dashboard') · {{ config('app.name') }} Admin</title>
  <link rel="icon" href="{{ \App\Models\Setting::get('site.favicon') ?: asset('favicon-180.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/fonts/fontawesome.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/fonts/tabler-icons.full.css') }}?v={{ filemtime(public_path('vuexy/vendor/fonts/tabler-icons.full.css')) }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/css/rtl/core.css') }}" class="template-customizer-core-css">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/css/rtl/theme-blue.css') }}" class="template-customizer-theme-css">
  <link rel="stylesheet" href="{{ asset('vuexy/css/demo.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/libs/node-waves/node-waves.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/libs/toastr/toastr.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/libs/select2/select2.css') }}">
  <link rel="stylesheet" href="{{ asset('vuexy/vendor/libs/flatpickr/flatpickr.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
  @stack('styles')
  <script src="{{ asset('vuexy/vendor/js/helpers.js') }}"></script>
  <script src="{{ asset('vuexy/js/config.js') }}"></script>
</head>
<body>
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">

    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
      <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link" style="width: 100%;">
          <span class="app-brand-logo demo" style="width: 100%;height:100%;"><img src="{{ \App\Models\Setting::get('site.logo') ?: asset('img/cabmedia-logo.jpg') }}" alt="{{ config('app.name') }}" style="width:100%;height:100%;object-fit:contain;"></span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
          <i class="ti menu-toggle-icon d-none d-xl-block align-middle"></i>
          <i class="ti ti-x d-block d-xl-none ti-md align-middle"></i>
        </a>
      </div>
      <div class="menu-inner-shadow"></div>

      @php
        $on = fn (...$p) => request()->routeIs(...$p) ? 'active' : '';
        $newContacts = auth()->user()->can('contacts.manage') ? \App\Models\ContactRequest::where('status', 'new')->count() : 0;
      @endphp
      <ul class="menu-inner py-1">
        <li class="menu-item {{ $on('admin.dashboard') }}"><a href="{{ route('admin.dashboard') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-smart-home"></i><div>Dashboard</div></a></li>

        @canany(['articles.view', 'categories.manage', 'pages.manage', 'seo.manage', 'media.manage'])
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Content</span></li>
        @endcanany
        @can('articles.view')
        <li class="menu-item {{ $on('admin.articles.*') }}"><a href="{{ route('admin.articles.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-news"></i><div>Blogs</div></a></li>
        @endcan
        @can('categories.manage')
        <li class="menu-item {{ $on('admin.categories.*') }}"><a href="{{ route('admin.categories.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-category"></i><div>Blog categories</div></a></li>
        @endcan
        @can('pages.manage')
        <li class="menu-item {{ $on('admin.pages.*') }}"><a href="{{ route('admin.pages.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-file-text"></i><div>Pages</div></a></li>
        @endcan
        @can('media.manage')
        <li class="menu-item {{ $on('admin.media.*') }}"><a href="{{ route('admin.media.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-photo"></i><div>Media library</div></a></li>
        @endcan
        @can('seo.manage')
        <li class="menu-item {{ $on('admin.seo.*') }}"><a href="{{ route('admin.seo.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-seo"></i><div>SEO</div></a></li>
        @endcan

        @can('contacts.manage')
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Engagement</span></li>
        <li class="menu-item {{ $on('admin.contacts.*') }}"><a href="{{ route('admin.contacts.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-messages"></i><div>Contact requests</div>@if($newContacts)<div class="badge bg-danger rounded-pill ms-auto">{{ $newContacts }}</div>@endif</a></li>
        @endcan

        @canany(['automation.manage', 'sources.manage', 'menus.manage', 'settings.manage'])
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Automation &amp; Site</span></li>
        @endcanany
        @can('automation.manage')
        <li class="menu-item {{ $on('admin.automation') }}"><a href="{{ route('admin.automation') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-robot"></i><div>Blog automation</div></a></li>
        @endcan
        @can('sources.manage')
        <li class="menu-item {{ $on('admin.sources.*') }}"><a href="{{ route('admin.sources.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-rss"></i><div>Blog sources</div></a></li>
        @endcan
        @can('menus.manage')
        <li class="menu-item {{ $on('admin.menus.*') }}"><a href="{{ route('admin.menus.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-layout-navbar"></i><div>Header &amp; Footer</div></a></li>
        @endcan
        @can('settings.manage')
        <li class="menu-item {{ $on('admin.settings') }}"><a href="{{ route('admin.settings') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-settings"></i><div>Settings</div></a></li>
        @endcan

        @canany(['users.manage', 'roles.manage'])
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Team</span></li>
        @endcanany
        @can('users.manage')
        <li class="menu-item {{ $on('admin.users.*') }}"><a href="{{ route('admin.users.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-users"></i><div>Users</div></a></li>
        @endcan
        @can('roles.manage')
        <li class="menu-item {{ $on('admin.roles.*') }}"><a href="{{ route('admin.roles.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-shield-lock"></i><div>Roles &amp; permissions</div></a></li>
        @endcan

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Site</span></li>
        <li class="menu-item"><a href="{{ route('home') }}" target="_blank" class="menu-link"><i class="menu-icon tf-icons ti ti-external-link"></i><div>View website</div></a></li>
      </ul>
    </aside>

    <div class="layout-page">
      <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
          <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)"><i class="ti ti-menu-2 ti-md"></i></a>
        </div>
        <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
          <div class="navbar-nav align-items-center">
            <div class="nav-item mb-0 d-none d-md-block"><span class="text-muted"><i class="ti ti-calendar me-1"></i>{{ now()->format('D, d M Y') }}</span></div>
          </div>
          <ul class="navbar-nav flex-row align-items-center ms-auto">
            <li class="nav-item me-3"><a class="nav-link" href="{{ route('home') }}" target="_blank" title="View website"><i class="ti ti-world ti-md"></i></a></li>
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
              <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online"><img src="{{ auth()->user()->avatar_url }}" alt="" class="rounded-circle"></div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <a class="dropdown-item mt-0" href="{{ route('admin.profile') }}">
                    <div class="d-flex align-items-center">
                      <div class="flex-shrink-0 me-2"><div class="avatar avatar-online"><img src="{{ auth()->user()->avatar_url }}" alt="" class="rounded-circle"></div></div>
                      <div class="flex-grow-1"><h6 class="mb-0">{{ auth()->user()->name }}</h6><small class="text-muted">{{ auth()->user()->getRoleNames()->implode(', ') ?: 'No role' }}</small></div>
                    </div>
                  </a>
                </li>
                <li><div class="dropdown-divider my-1 mx-n2"></div></li>
                <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="ti ti-user me-3 ti-md"></i><span class="align-middle">My profile</span></a></li>
                <li>
                  <form method="POST" action="{{ route('admin.logout') }}" class="d-grid px-2 pt-2 pb-1">@csrf
                    <button class="btn btn-sm btn-danger d-flex justify-content-center" type="submit"><small class="align-middle">Logout</small><i class="ti ti-logout ms-2 ti-14px"></i></button>
                  </form>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>

      <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
          @include('admin.partials.flash')
          @yield('content')
        </div>
        <footer class="content-footer footer bg-footer-theme">
          <div class="container-xxl"><div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">© {{ date('Y') }} {{ config('app.name') }}</div>
            <div class="text-muted">{{ \App\Models\Setting::get('site.tagline', 'Content management') }}</div>
          </div></div>
        </footer>
        <div class="content-backdrop fade"></div>
      </div>
    </div>
  </div>
  <div class="layout-overlay layout-menu-toggle"></div>
  <div class="drag-target"></div>
</div>

@php
  $flashMessages = array_values(array_filter([
    session('success') ? ['type' => 'success', 'message' => session('success')] : null,
    session('error') ? ['type' => 'error', 'message' => session('error')] : null,
    (isset($errors) && $errors->any()) ? ['type' => 'error', 'message' => 'Please fix the highlighted problems below.'] : null,
  ]));
@endphp
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-body text-center p-5">
      <div class="cm-icon mb-3"></div>
      <h4 class="cm-title mb-2"></h4>
      <div class="cm-text text-muted"></div>
      <div class="cm-input mt-4 d-none text-start"><select id="cmSelect" data-plain class="form-select"></select></div>
      <div class="mt-4 d-flex justify-content-center gap-2">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary cm-ok">Confirm</button>
      </div>
    </div>
  </div></div>
</div>
<script src="{{ asset('vuexy/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('vuexy/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/node-waves/node-waves.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('vuexy/vendor/js/menu.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/toastr/toastr.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('vuexy/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('vuexy/js/main.js') }}"></script>
@can('media.manage')
<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true" style="z-index:1500">
  <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Choose from media library</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" data-ml-host>@include('admin.media.browser', ['pick' => true, 'roots' => [
      'uploads' => ['label' => 'Uploads'], 'editor' => ['label' => 'Editor images'],
    ]])</div>
    <div class="modal-footer"><button type="button" class="btn btn-primary d-none" data-ml-use></button></div>
  </div></div>
</div>
@endcan
<script>window.ADMIN = { flash: @json($flashMessages), csrf: @json(csrf_token()), uploadUrl: @json(route('admin.media.upload')), tinymce: @json(asset('vendor/tinymce')) @can('media.manage'), media: { list: @json(route('admin.media.list')), upload: @json(route('admin.media.files')), folder: @json(route('admin.media.folder')), destroy: @json(route('admin.media.destroy')), roots: { uploads: 'Uploads', editor: 'Editor images' } } @endcan };</script>
<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
@can('media.manage')<script src="{{ asset('js/media-library.js') }}?v={{ filemtime(public_path('js/media-library.js')) }}"></script>@endcan
@stack('scripts')
</body>
</html>
