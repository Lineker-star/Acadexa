@extends('layouts.auth')
@section('title', __('auth.forgot_password'))

@section('content')
<div class="card border-0 shadow-brand rounded-xl">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;color:var(--primary);">Forgot Password?</h4>
        <p class="text-muted small mb-4">Enter your email and we'll send you a reset link.</p>

        @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('auth.email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autofocus placeholder="you@example.com">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
    </div>
</div>
@endsection
