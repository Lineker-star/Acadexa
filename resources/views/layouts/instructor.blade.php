<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', __('Instructor')) — {{ $siteSettings['site_name'] ?? 'ACADEXA' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @include('partials.bootstrap-css')
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body data-user-id="{{ auth()->id() }}">
    <div id="sidebarOverlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1015;"></div>

    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-brand">
            <span>ACADEXA<em>.</em></span>
            <div style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:.2rem;">{{ __('Instructor Portal') }}</div>
        </div>
        <nav class="sidebar-nav mt-2">
            <a href="{{ route('instructor.dashboard') }}" class="{{ request()->routeIs('instructor.dashboard') ? 'active' : '' }}">
                <x-icon name="speedometer2" /> {{ __('Dashboard') }}
            </a>
            <a href="{{ route('instructor.courses.index') }}" class="{{ request()->routeIs('instructor.courses.*') ? 'active' : '' }}">
                <x-icon name="play-circle" /> {{ __('My Courses') }}
            </a>
            <a href="{{ route('instructor.courses.create') }}">
                <x-icon name="plus-circle" /> {{ __('Create Course') }}
            </a>
            @php
                $pendingSubmissions = \App\Models\AssignmentSubmission::where('status', 'submitted')
                    ->whereHas('lesson.module.course', fn ($q) => $q->where('instructor_id', auth()->id()))->count();
            @endphp
            <a href="{{ route('instructor.submissions.index') }}" class="{{ request()->routeIs('instructor.submissions.*') ? 'active' : '' }}">
                <x-icon name="clipboard-check" /> {{ __('lms.assignments') }}
                @if($pendingSubmissions)<span class="badge bg-danger ms-auto">{{ $pendingSubmissions }}</span>@endif
            </a>
            <a href="{{ route('instructor.qa.index') }}" class="{{ request()->routeIs('instructor.qa.*') ? 'active' : '' }}">
                <x-icon name="chat-dots" /> {{ __('lms.qa') }}
            </a>
            <a href="{{ route('messages.index') }}" class="{{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <x-icon name="envelope" /> {{ __('lms.messages') }}
            </a>
            <a href="{{ route('instructor.earnings') }}" class="{{ request()->routeIs('instructor.earnings') ? 'active' : '' }}">
                <x-icon name="cash-stack" /> {{ __('Earnings') }}
            </a>
            <div class="nav-section" style="margin-top:1rem;"></div>
            <a href="{{ route('dashboard') }}">
                <x-icon name="person-circle" /> {{ __('Student View') }}
            </a>
            <a href="{{ route('home') }}" target="_blank">
                <x-icon name="box-arrow-up-right" /> {{ __('View Site') }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="px-3 mt-1">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <x-icon name="box-arrow-left" /> {{ __('Logout') }}
                </button>
            </form>
        </nav>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle"><x-icon name="list" class="fs-5" /></button>
            <h5 class="mb-0 fw-bold" style="font-family:'Poppins',sans-serif;color:var(--primary);">@yield('page-title', __('Instructor Dashboard'))</h5>
            <div class="d-flex align-items-center gap-3">
                <ul class="navbar-nav flex-row">@include('partials.notification-bell')</ul>
                <span class="d-none d-sm-inline text-muted small">{{ auth()->user()->name }}</span>
                <img src="{{ auth()->user()->avatarUrl() }}" class="rounded-circle" width="36" height="36" alt="{{ __('avatar') }}">
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
