<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', __('Home')) — {{ $siteSettings['site_name'] ?? 'ACADEXA' }}</title>
    <meta name="description" content="@yield('meta_description', __('ACADEXA — Empowering World Innovators and Leaders for Global Impact. Learn from top instructors at ZTF University Institute, Bertoua, Cameroon.'))">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <!-- Bootstrap 5 -->
    @include('partials.bootstrap-css')
    <!-- App CSS (includes Google Fonts) -->
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body data-user-id="{{ auth()->id() }}" @if(auth()->user()?->role === 'student') data-library="1" @endif>
    <!-- Navbar -->
    @include('partials.navbar')

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Page Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- App JS -->
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
