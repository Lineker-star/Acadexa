<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', __('Admin')) — {{ $siteSettings['site_name'] ?? 'ACADEXA' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @include('partials.bootstrap-css')
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body data-user-id="{{ auth()->id() }}">
    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebarOverlay" class="d-none d-lg-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1015;"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-brand">
            <span>ACADEXA<em>.</em></span>
            <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.2rem;">{{ __('Control Panel') }}</div>
        </div>
        <nav class="sidebar-nav mt-2">
            <div class="nav-section">{{ __('Main') }}</div>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <x-icon name="speedometer2" /> {{ __('Dashboard') }}
            </a>

            <div class="nav-section">{{ __('People') }}</div>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <x-icon name="people" /> {{ __('Users') }}
            </a>
            <a href="{{ route('admin.applications.index') }}" class="{{ request()->routeIs('admin.applications.*') ? 'active' : '' }}">
                <x-icon name="person-check" /> {{ __('Instructor Applications') }}
                @php $pendingApps = \App\Models\InstructorApplication::where('status','pending')->count(); @endphp
                @if($pendingApps) <span class="badge bg-danger ms-auto">{{ $pendingApps }}</span> @endif
            </a>

            <div class="nav-section">{{ __('Content') }}</div>
            <a href="{{ route('admin.courses.index') }}" class="{{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                <x-icon name="play-circle" /> {{ __('Courses') }}
            </a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <x-icon name="grid" /> {{ __('Categories') }}
            </a>
            <a href="{{ route('admin.cms-pages.index') }}" class="{{ request()->routeIs('admin.cms-pages.*') ? 'active' : '' }}">
                <x-icon name="file-text" /> {{ __('CMS Pages') }}
            </a>
            <a href="{{ route('admin.announcements.index') }}" class="{{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                <x-icon name="megaphone" /> {{ __('Announcements') }}
            </a>
            <a href="{{ route('admin.translations.index') }}" class="{{ request()->routeIs('admin.translations.*') ? 'active' : '' }}">
                <x-icon name="translate" /> {{ __('Translations') }}
            </a>

            <div class="nav-section">{{ __('Monitoring') }}</div>
            <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                <x-icon name="star" /> {{ __('Reviews') }}
            </a>
            <a href="{{ route('admin.certificates.index') }}" class="{{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}">
                <x-icon name="award" /> {{ __('Certificates') }}
            </a>
            <a href="{{ route('admin.contacts.index') }}" class="{{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                <x-icon name="envelope" /> {{ __('Contact Messages') }}
            </a>
            <a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                <x-icon name="journal-text" /> {{ __('Activity Logs') }}
            </a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <x-icon name="bar-chart-line" /> {{ __('security.reports') }}
            </a>

            <div class="nav-section">{{ __('System') }}</div>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <x-icon name="gear" /> {{ __('Settings') }}
            </a>
            <a href="{{ route('admin.certificate.template') }}" class="{{ request()->routeIs('admin.certificate.*') ? 'active' : '' }}">
                <x-icon name="card-text" /> {{ __('Certificate Template') }}
            </a>

            <div class="nav-section">{{ __('Account') }}</div>
            <a href="{{ route('admin.security') }}" class="{{ request()->routeIs('admin.security') ? 'active' : '' }}">
                <x-icon name="shield-lock" /> {{ __('security.my_security') }}
                @unless(auth()->user()->hasTwoFactorEnabled())<span class="badge bg-warning text-dark ms-auto">!</span>@endunless
            </a>
            <a href="{{ route('home') }}" target="_blank">
                <x-icon name="box-arrow-up-right" /> {{ __('View Site') }}
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" class="px-3 mt-1">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <x-icon name="box-arrow-left" /> {{ __('Logout') }}
                </button>
            </form>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle">
                    <x-icon name="list" class="fs-5" />
                </button>
                <nav aria-label="{{ __('Breadcrumb') }}" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="d-none d-sm-inline text-muted small">{{ auth()->user()->name }}</span>
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ __('avatar') }}" class="rounded-circle" width="36" height="36">
            </div>
        </div>

        <!-- Flash -->
        @include('partials.flash')

        <!-- Content -->
        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
