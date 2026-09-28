@extends('layouts.auth')
@section('title', __('Confirm Password'))

@section('content')
<div class="card border-0 shadow-brand rounded-xl">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-2" style="font-family:'Poppins',sans-serif;color:var(--primary);">{{ __('Confirm Your Password') }}</h4>
        <p class="text-muted small mb-4">{{ __('Please confirm your password to continue.') }}</p>
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">{{ __('Password') }}</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">{{ __('Confirm') }}</button>
        </form>
    </div>
</div>
@endsection
