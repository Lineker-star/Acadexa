<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — ACADEXXA Control Panel</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebarOverlay" class="d-none d-lg-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1015;"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-brand">
            <span>ACADEXXA<em>.</em></span>
            <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.2rem;">Control Panel</div>
        </div>
        <nav class="sidebar-nav mt-2">
            <div class="nav-section">Main</div>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="nav-section">People</div>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="{{ route('admin.applications.index') }}" class="{{ request()->routeIs('admin.applications.*') ? 'active' : '' }}">
                <i class="bi bi-person-check"></i> Instructor Applications
                @php $pendingApps = \App\Models\InstructorApplication::where('status','pending')->count(); @endphp
                @if($pendingApps) <span class="badge bg-danger ms-auto">{{ $pendingApps }}</span> @endif
            </a>

            <div class="nav-section">Content</div>
            <a href="{{ route('admin.courses.index') }}" class="{{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                <i class="bi bi-play-circle"></i> Courses
            </a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="bi bi-grid"></i> Categories
            </a>
            <a href="{{ route('admin.cms-pages.index') }}" class="{{ request()->routeIs('admin.cms-pages.*') ? 'active' : '' }}">
                <i class="bi bi-file-text"></i> CMS Pages
            </a>
            <a href="{{ route('admin.announcements.index') }}" class="{{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Announcements
            </a>
            <a href="{{ route('admin.translations.index') }}" class="{{ request()->routeIs('admin.translations.*') ? 'active' : '' }}">
                <i class="bi bi-translate"></i> Translations
            </a>

            <div class="nav-section">Monitoring</div>
            <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                <i class="bi bi-star"></i> Reviews
            </a>
            <a href="{{ route('admin.certificates.index') }}" class="{{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}">
                <i class="bi bi-award"></i> Certificates
            </a>
            <a href="{{ route('admin.contacts.index') }}" class="{{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                <i class="bi bi-envelope"></i> Contact Messages
            </a>
            <a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Activity Logs
            </a>

            <div class="nav-section">System</div>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Settings
            </a>
            <a href="{{ route('admin.certificate.template') }}" class="{{ request()->routeIs('admin.certificate.*') ? 'active' : '' }}">
                <i class="bi bi-card-text"></i> Certificate Template
            </a>

            <div class="nav-section">Account</div>
            <a href="{{ route('home') }}" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> View Site
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" class="px-3 mt-1">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-box-arrow-left"></i> Logout
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
                    <i class="bi bi-list fs-5"></i>
                </button>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="d-none d-sm-inline text-muted small">{{ auth()->user()->name }}</span>
                <img src="{{ auth()->user()->avatarUrl() }}" alt="avatar" class="rounded-circle" width="36" height="36">
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
