<!DOCTYPE html>
{{-- Offline app shell. Cached by the service worker: it must not contain any user-specific
     server data. Everything shown comes from the courses stored in IndexedDB. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ __('lms.offline_courses') }} — {{ $siteSettings['site_name'] ?? 'ACADEXXA' }}</title>
    @include('partials.pwa-head')
    <link rel="icon" type="image/png" href="{{ route('pwa.icon', 'icon-192.png') }}">
    @include('partials.bootstrap-css')
    @vite(['resources/css/app.css'])
</head>
<body style="background:var(--light)">
    <header class="bg-white border-bottom sticky-top" style="padding-top:env(safe-area-inset-top,0px)">
        <div class="container d-flex align-items-center gap-3 py-2">
            <a href="#" data-nav="home" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ route('pwa.icon', 'icon-192.png') }}" alt="" width="36" height="36" class="rounded">
                <strong class="text-dark d-none d-sm-inline">{{ __('lms.offline_courses') }}</strong>
            </a>
            <span id="connPill" class="offline-pill ms-auto"></span>
            <a href="{{ route('student.courses.index') }}" id="onlineLink" class="btn btn-sm btn-outline-primary" hidden><x-icon name="wifi" class="me-1" />{{ __('lms.back_online') }}</a>
        </div>
    </header>

    <main id="offlineApp" class="container py-4" aria-live="polite">
        <div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2"></span>{{ __('lms.loading') }}</div>
    </main>

    <noscript><div class="container py-5 text-center">{{ __('lms.offline_needs_js') }}</div></noscript>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js', 'resources/js/offline-app.js'])
</body>
</html>
