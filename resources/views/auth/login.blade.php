@extends('layouts.auth')
@section('title', __('auth.login'))

@section('content')
<div class="card border-0 shadow-brand rounded-xl">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;color:var(--primary);">{{ __('auth.welcome_back') }}</h4>
        <p class="text-muted small mb-4">{{ __('auth.sign_in_to_continue') }}</p>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="you@example.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <label class="form-label small fw-bold">{{ __('auth.password') }}</label>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-muted">{{ __('auth.forgot_password') }}</a>
                    @endif
                </div>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required autocomplete="current-password" placeholder="••••••••">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                <label class="form-check-label small" for="remember">{{ __('auth.remember_me') }}</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">{{ __('auth.login') }}</button>
        </form>

        <p class="text-center mt-3 mb-0 small">
            {{ __('auth.no_account') }}
            <a href="{{ route('register') }}" class="fw-bold">{{ __('auth.register_here') }}</a>
        </p>
    </div>
</div>
@endsection
