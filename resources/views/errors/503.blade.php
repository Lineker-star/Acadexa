<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Maintenance Mode — ACADEXA') }}</title>
    @include('partials.bootstrap-css')
    <style> body { background: #0A2A5E; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Poppins', sans-serif; color: white; } .logo { font-size: 2rem; font-weight: 900; letter-spacing: -1px; } .accent { color: #C1440E; } </style>
</head>
<body>
<div class="text-center px-4">
    <div class="logo mb-3">ACADE<span class="accent">XA</span></div>
    <div style="font-size:3rem;"><x-icon name="wrench" /></div>
    <h2 class="fw-bold mt-3">{{ __('We\'ll be right back') }}</h2>
    <p class="mt-2 mb-4 opacity-75" style="max-width:400px;margin:0 auto;">
        {{ __('ACADEXA is currently undergoing scheduled maintenance. We apologize for the inconvenience — check back soon!') }}
    </p>
    <p class="opacity-50 small">{{ __('ZTF University Institute — www.ztfuniversity.com') }}</p>
</div>
</body>
</html>
