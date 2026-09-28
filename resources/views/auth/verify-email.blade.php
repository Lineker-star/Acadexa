@extends('layouts.auth')
@section('title', __('Verify Email'))

@section('content')
<div class="card border-0 shadow-brand rounded-xl text-center">
    <div class="card-body p-4">
        <div style="font-size:3rem;"><x-icon name="envelope" /></div>
        <h4 class="fw-bold mt-2" style="font-family:'Poppins',sans-serif;color:var(--primary);">{{ __('Verify Your Email') }}</h4>
        <p class="text-muted">{{ __('We sent a verification link to your email. Please click it to continue.') }}</p>
        @if(session('status') == 'verification-link-sent')
        <div class="alert alert-success">{{ __('A new link has been sent!') }}</div>
        @endif
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">{{ __('Resend Verification Email') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button class="btn btn-link text-muted small">{{ __('Log Out') }}</button>
        </form>
    </div>
</div>
@endsection
