<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Page Not Found — ACADEXA') }}</title>
    @include('partials.bootstrap-css')
    <style> body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Poppins', sans-serif; } .error-code { font-size: 8rem; font-weight: 900; color: #0A2A5E; line-height: 1; } .accent { color: #C1440E; } </style>
</head>
<body>
<div class="text-center px-4">
    <div class="error-code">4<span class="accent">0</span>4</div>
    <h2 class="fw-bold mt-2" style="color:#0A2A5E;">{{ __('Page Not Found') }}</h2>
    <p class="text-muted mt-2 mb-4" style="max-width:400px;margin:0 auto;">
        {{ __('The page you\'re looking for doesn\'t exist or has been moved.') }}
    </p>
    <div class="d-flex gap-2 justify-content-center">
        <a href="{{ url('/') }}" class="btn btn-primary px-4">{{ __('Go Home') }}</a>
        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary px-4">{{ __('Browse Courses') }}</a>
    </div>
</div>
</body>
</html>
