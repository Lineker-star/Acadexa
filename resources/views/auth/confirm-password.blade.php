@extends('layouts.auth')
@section('title', 'Confirm Password')

@section('content')
<div class="card border-0 shadow-brand rounded-xl">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-2" style="font-family:'Poppins',sans-serif;color:var(--primary);">Confirm Your Password</h4>
        <p class="text-muted small mb-4">Please confirm your password to continue.</p>
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">Confirm</button>
        </form>
    </div>
</div>
@endsection
