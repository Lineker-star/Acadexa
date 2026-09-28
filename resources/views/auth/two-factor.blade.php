@extends('layouts.auth')
@section('title', $purpose === 'register' ? __('learn.confirm_email_title') : __('learn.two_factor_title'))

@section('content')
<div class="card border-0 shadow-brand rounded-xl">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <x-icon :name="$purpose === 'register' ? 'envelope-check' : 'shield-lock'" style="font-size:2.5rem;color:var(--primary)" />
        </div>
        <h4 class="fw-bold mb-1 text-center" style="color:var(--primary);">
            {{ $purpose === 'register' ? __('learn.confirm_email_title') : __('learn.two_factor_title') }}
        </h4>
        <p class="text-muted small text-center mb-4">
            @if($method === 'app')
                {{ __('learn.two_factor_app_help') }}
            @elseif($purpose === 'register')
                {{ __('learn.confirm_email_help', ['email' => $maskedEmail]) }}
            @else
                {{ __('learn.two_factor_email_help', ['email' => $maskedEmail]) }}
            @endif
        </p>

        <form method="POST" action="{{ route('two-factor.verify') }}" novalidate>
            @csrf
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus required
                   class="form-control form-control-lg text-center fw-bold @error('code') is-invalid @enderror"
                   style="letter-spacing:.5em;font-size:1.6rem" placeholder="000000" aria-label="{{ __('learn.code_label') }}">
            @error('code')<div class="invalid-feedback text-center">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary w-100 mt-3">{{ __('learn.verify') }}</button>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-3 small">
            @if($method === 'email')
                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button class="btn btn-link btn-sm p-0" @disabled($wait > 0)>
                        {{ $wait > 0 ? __('learn.code_wait', ['seconds' => $wait]) : __('learn.resend_code') }}
                    </button>
                </form>
            @else
                <span class="text-muted">{{ __('learn.lost_device_help') }}</span>
            @endif
            <form method="POST" action="{{ route('two-factor.cancel') }}">
                @csrf
                <button class="btn btn-link btn-sm p-0 text-muted">{{ __('lms.cancel') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
