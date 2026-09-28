<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('security.2fa_title') }} — {{ $siteSettings['site_name'] ?? 'ACADEXA' }}</title>
    @include('partials.bootstrap-css')
    @vite(['resources/css/app.css'])
</head>
<body style="background:var(--primary);min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div style="width:100%;max-width:420px;padding:1rem;">
        <div class="card border-0 shadow-lg rounded-xl">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-2" style="color:var(--primary);"><x-icon name="phone" class="me-2" />{{ __('security.2fa_title') }}</h5>
                <p class="small text-muted">{{ __('security.2fa_challenge_help') }}</p>
                <form method="POST" action="{{ route('admin.2fa.verify') }}">
                    @csrf
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus
                           class="form-control form-control-lg text-center mb-3 @error('code') is-invalid @enderror" style="letter-spacing:.4em" placeholder="000000">
                    @error('code')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                    <button class="btn btn-primary w-100">{{ __('security.verify') }}</button>
                </form>
                <p class="text-center mt-3 mb-0"><a href="{{ route('admin.login') }}" class="small text-muted"><x-icon name="arrow-left" class="me-1" />{{ __('lms.back') }}</a></p>
            </div>
        </div>
    </div>
</body>
</html>
