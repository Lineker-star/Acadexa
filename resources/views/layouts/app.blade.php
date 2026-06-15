<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ACADEXXA') — {{ $siteSettings['site_name'] ?? 'ACADEXXA' }}</title>
    <meta name="description" content="@yield('meta_description', 'ACADEXXA — Empowering World Innovators and Leaders for Global Impact. Online learning platform by ZTF University Institute, Bertoua, Cameroon.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- App CSS (includes Google Fonts) -->
    @vite(['resources/css/app.css'])
    @if(app()->getLocale() === 'ar')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    @endif
    @stack('styles')
</head>
<body>
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
