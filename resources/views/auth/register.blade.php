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
                       value="{{ old('name') }}" required autofocus placeholder="{{ __('Your Full Name') }}">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required placeholder="{{ __('you@example.com') }}">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('Country') }}</label>
                <select name="country" class="form-select">
                    <option value="">{{ __('Select Country') }}</option>
                    @foreach(['Cameroon','Nigeria','Ghana','Senegal','Kenya','Ethiopia','South Africa','Egypt','Morocco','France','United Kingdom','United States','Canada','Brazil','Other'] as $c)
                    <option value="{{ $c }}" {{ old('country') == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.password') }}</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       required autocomplete="new-password" placeholder="{{ __('Min. 8 characters') }}">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold">{{ __('auth.confirm_password') }}</label>
                <input type="password" name="password_confirmation" class="form-control"
                       required autocomplete="new-password" placeholder="{{ __('Repeat password') }}">
            </div>

            <p class="text-muted" style="font-size:.8rem;">
                {!! __('By registering you get :trial. No payment required. By continuing, you agree to our :terms and :privacy.', [
                    'trial'   => '<strong>' . e(trans_choice(':count day of free trial|:count days of free trial', (int) ($siteSettings['trial_days'] ?? 30), ['count' => (int) ($siteSettings['trial_days'] ?? 30)])) . '</strong>',
                    'terms'   => '<a href="' . e(route('cms.page', 'terms')) . '" target="_blank">' . e(__('Terms of Service')) . '</a>',
                    'privacy' => '<a href="' . e(route('cms.page', 'privacy')) . '" target="_blank">' . e(__('Privacy Policy')) . '</a>',
                ]) !!}
            </p>

            <button type="submit" class="btn btn-primary w-100">{{ __('auth.register') }}</button>
        </form>

        @include('partials.google-button')

        <p class="text-center mt-3 mb-0 small">
            {{ __('auth.have_account') }}
            <a href="{{ route('login') }}" class="fw-bold">{{ __('auth.login') }}</a>
        </p>
    </div>
</div>
@endsection
