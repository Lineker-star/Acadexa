@extends('layouts.app')
@section('title', 'Edit Profile')

@section('content')
<div class="py-5 bg-light-gray">
    <div class="container" style="max-width:700px;">
        <h2 class="mb-4">My Profile</h2>
        <div class="bg-white rounded-xl shadow-brand p-4">
            <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="text-center mb-4">
                    <img src="{{ $user->avatarUrl() }}" class="rounded-circle mb-3" width="90" height="90" alt="avatar">
                    <div>
                        <label class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-camera me-1"></i>Change Photo
                            <input type="file" name="avatar" accept="image/*" class="d-none">
                        </label>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Country</label>
                        <select name="country" class="form-select">
                            <option value="">Select...</option>
                            @foreach(['Cameroon','Nigeria','Ghana','Senegal','Kenya','France','UK','USA','Canada','Other'] as $c)
                            <option value="{{ $c }}" {{ $user->country == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Bio</label>
                        <textarea name="bio" class="form-control" rows="3" maxlength="1000"
                                  placeholder="Tell us about yourself...">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Preferred Language</label>
                        <select name="preferred_language" class="form-select">
                            @foreach(config('app.supported_locales') as $locale)
                            <option value="{{ $locale }}" {{ $user->preferred_language == $locale ? 'selected' : '' }}>
                                {{ config('app.locale_flags.'.$locale) }} {{ config('app.locale_names.'.$locale) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Change Password (optional)</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Current Password</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Confirm New</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
