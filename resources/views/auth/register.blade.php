@extends('layouts.auth')
@section('title', __('auth.register'))

@section('content')
<div class="card border-0 shadow-brand rounded-xl">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;color:var(--primary);">{{ __('auth.create_account') }}</h4>
        <p class="text-muted small mb-4">{{ __('auth.join_acadexa') }}</p>

        <form method="POST" action="{{ route('register') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.full_name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required autofocus placeholder="Your Full Name">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required placeholder="you@example.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Country</label>
                <select name="country" class="form-select">
                    <option value="">Select Country</option>
                    @foreach(['Cameroon','Nigeria','Ghana','Senegal','Kenya','Ethiopia','South Africa','Egypt','Morocco','France','United Kingdom','United States','Canada','Brazil','Other'] as $c)
                    <option value="{{ $c }}" {{ old('country') == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.password') }}</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required autocomplete="new-password" placeholder="Min. 8 characters">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold">{{ __('auth.confirm_password') }}</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required autocomplete="new-password" placeholder="Repeat password">
            </div>

            <p class="text-muted" style="font-size:.8rem;">
                By registering you get <strong>30 days free trial</strong>. No payment required. By continuing, you agree to our
                <a href="{{ route('cms.page', 'terms') }}" target="_blank">Terms</a> &amp;
                <a href="{{ route('cms.page', 'privacy') }}" target="_blank">Privacy Policy</a>.
            </p>

            <button type="submit" class="btn btn-primary w-100">{{ __('auth.register') }}</button>
        </form>

        <p class="text-center mt-3 mb-0 small">
            {{ __('auth.have_account') }}
            <a href="{{ route('login') }}" class="fw-bold">{{ __('auth.login') }}</a>
        </p>
    </div>
</div>
@endsection
