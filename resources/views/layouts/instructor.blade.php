<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Instructor') — ACADEXXA</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body>
    <div id="sidebarOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1015;"></div>

    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-brand">
            <span>ACADEXXA<em>.</em></span>
            <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.2rem;">Instructor Portal</div>
        </div>
        <nav class="sidebar-nav mt-2">
            <a href="{{ route('instructor.dashboard') }}" class="{{ request()->routeIs('instructor.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="{{ route('instructor.courses.index') }}" class="{{ request()->routeIs('instructor.courses.*') ? 'active' : '' }}">
                <i class="bi bi-play-circle"></i> My Courses
            </a>
            <a href="{{ route('instructor.courses.create') }}">
                <i class="bi bi-plus-circle"></i> Create Course
            </a>
            <a href="{{ route('instructor.earnings') }}" class="{{ request()->routeIs('instructor.earnings') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Earnings
            </a>
            <div class="nav-section" style="margin-top:1rem;"></div>
            <a href="{{ route('dashboard') }}">
                <i class="bi bi-person-circle"></i> Student View
            </a>
            <a href="{{ route('home') }}" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> View Site
            </a>
            <form method="POST" action="{{ route('logout') }}" class="px-3 mt-1">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </nav>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
            <h5 class="mb-0 fw-bold" style="font-family:'Poppins',sans-serif;color:var(--primary);">@yield('page-title', 'Instructor Dashboard')</h5>
            <div class="d-flex align-items-center gap-3">
                <span class="d-none d-sm-inline text-muted small">{{ auth()->user()->name }}</span>
                <img src="{{ auth()->user()->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="avatar">
            </div>
        </div>
        @include('partials.flash')
        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
