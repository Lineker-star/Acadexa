@extends('layouts.app')
@section('title', __('Edit Profile'))
@section('robots', 'noindex, nofollow')

@section('content')
<div class="py-5 bg-light-gray">
    <div class="container" style="max-width:820px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h2 class="mb-0">{{ __('My Profile') }}</h2>
            <a href="{{ $user->isAdmin() ? route('admin.dashboard') : ($user->isInstructor() ? route('instructor.dashboard') : route('dashboard')) }}" class="btn btn-sm btn-outline-secondary">
                <x-icon name="speedometer2" class="me-1" />{{ __('navigation.dashboard') }}</a>
        </div>

        {{-- ─── Personal information ─── --}}
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
            <h5 class="fw-bold mb-3"><x-icon name="person" class="me-1" />{{ __('learn.profile_information') }}</h5>
            <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <img src="{{ $user->avatarUrl() }}" class="rounded-circle" width="90" height="90" alt="{{ __('avatar') }}" style="object-fit:cover">
                    <div>
                        <label class="btn btn-outline-primary btn-sm mb-1">
                            <x-icon name="camera" class="me-1" />{{ __('Change Photo') }}
                            <input type="file" name="avatar" accept="image/*" class="d-none">
                        </label>
                        @if($user->avatar)
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="removeAvatar">
                                <label class="form-check-label" for="removeAvatar">{{ __('learn.remove_photo') }}</label>
                            </div>
                        @endif
                        @error('avatar')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Full Name') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required maxlength="255">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('auth.email') }}</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                        <div class="form-text">{{ __('learn.email_change_help') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Country') }}</label>
                        <select name="country" class="form-select">
                            <option value="">{{ __('Select...') }}</option>
                            @foreach(['Cameroon','Nigeria','Ghana','Senegal','Kenya','France','UK','USA','Canada','Other'] as $c)
                            <option value="{{ $c }}" {{ old('country', $user->country) == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Preferred Language') }}</label>
                        <select name="preferred_language" class="form-select">
                            @foreach(config('app.supported_locales') as $locale)
                            <option value="{{ $locale }}" {{ $user->preferred_language == $locale ? 'selected' : '' }}>
                                {{ config('app.locale_names.'.$locale) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">{{ __('Bio') }}</label>
                        <textarea name="bio" class="form-control" rows="3" maxlength="1000"
                                  placeholder="{{ __('Tell us about yourself...') }}">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications" value="1" id="emailNotif" @checked($user->email_notifications)>
                            <label class="form-check-label small" for="emailNotif">{{ __('learn.email_notifications') }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_news" value="1" id="emailNews" @checked($user->email_news)>
                            <label class="form-check-label small" for="emailNews">{{ __('learn.email_news') }}</label>
                        </div>
                        <div class="form-text">{{ __('learn.email_security_note') }}</div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>

        {{-- ─── Security ─── --}}
        <div class="bg-white rounded-xl shadow-brand p-4 mb-4" id="security">
            <h5 class="fw-bold mb-3"><x-icon name="shield-lock" class="me-1" />{{ __('learn.security') }}</h5>

            {{-- Password --}}
            <h6 class="fw-bold">{{ $user->has_password ? __('Change Password (optional)') : __('learn.choose_password') }}</h6>
            @unless($user->has_password)
                <p class="small text-muted">{{ __('learn.choose_password_help') }}</p>
            @endunless
            <form method="POST" action="{{ route('profile.password') }}" class="row g-3 mb-4" novalidate>
                @csrf
                @if($user->has_password)
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">{{ __('Current Password') }}</label>
                        <input type="password" name="current_password" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('New Password') }}</label>
                    <input type="password" name="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('Confirm New') }}</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
                </div>
                <div class="col-12"><button class="btn btn-outline-primary btn-sm">{{ __('learn.save_password') }}</button></div>
            </form>

            <hr>

            {{-- Two-factor authentication --}}
            <h6 class="fw-bold">{{ __('security.2fa_title') }}</h6>
            @if($user->isAdmin())
                <p class="small text-muted">{{ __('learn.two_factor_admin_help') }}</p>
                <a href="{{ route('admin.security') }}" class="btn btn-sm btn-outline-primary">{{ __('security.my_security') }}</a>
            @elseif($user->hasTwoFactorEnabled())
                <div class="alert alert-success small">
                    <x-icon name="check-circle" class="me-1" />
                    {{ $user->usesAuthenticatorApp() ? __('learn.two_factor_on_app') : __('learn.two_factor_on_email') }}
                </div>
                <form method="POST" action="{{ route('profile.2fa.disable') }}" class="row g-2 align-items-end" novalidate>
                    @csrf
                    <div class="col-sm-5">
                        <label class="form-label small">{{ $user->usesAuthenticatorApp() ? __('learn.code_from_app') : __('learn.code_from_email') }}</label>
                        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="form-control form-control-sm @error('disable_code') is-invalid @enderror" placeholder="000000">
                        @error('disable_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4"><button class="btn btn-sm btn-outline-danger w-100">{{ __('security.disable') }}</button></div>
                </form>
                @unless($user->usesAuthenticatorApp())
                    <form method="POST" action="{{ route('profile.2fa.email-code') }}" class="mt-2">
                        @csrf
                        <button class="btn btn-link btn-sm p-0">{{ __('learn.send_me_a_code') }}</button>
                    </form>
                @endunless
            @else
                <p class="small text-muted">{{ __('learn.two_factor_intro') }}</p>
                <div class="row g-4">
                    {{-- Authenticator app --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-1"><x-icon name="phone" class="me-1" />{{ __('learn.two_factor_app') }}</div>
                            <p class="small text-muted">{{ __('learn.two_factor_app_intro') }}</p>
                            <div id="qrcode" class="p-2 bg-white border rounded mx-auto mb-2" style="width:176px;height:176px" data-uri="{{ $pending['uri'] }}"></div>
                            <div class="small text-muted text-center mb-2">{{ __('security.manual_key') }}<br><code class="user-select-all">{{ trim(chunk_split($pending['secret'], 4, ' ')) }}</code></div>
                            <form method="POST" action="{{ route('profile.2fa.app') }}" class="d-flex gap-2" novalidate>
                                @csrf
                                <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="form-control form-control-sm @error('app_code') is-invalid @enderror" placeholder="000000" aria-label="{{ __('learn.code_from_app') }}">
                                <button class="btn btn-sm btn-primary text-nowrap">{{ __('security.enable') }}</button>
                            </form>
                            @error('app_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    {{-- E-mail code --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-1"><x-icon name="envelope" class="me-1" />{{ __('learn.two_factor_email') }}</div>
                            <p class="small text-muted">{{ __('learn.two_factor_email_intro', ['email' => $user->email]) }}</p>
                            @if(session('email_code_sent') || $errors->has('email_code'))
                                <form method="POST" action="{{ route('profile.2fa.email') }}" class="d-flex gap-2" novalidate>
                                    @csrf
                                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="form-control form-control-sm @error('email_code') is-invalid @enderror" placeholder="000000" aria-label="{{ __('learn.code_from_email') }}">
                                    <button class="btn btn-sm btn-primary text-nowrap">{{ __('security.enable') }}</button>
                                </form>
                                @error('email_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @else
                                <form method="POST" action="{{ route('profile.2fa.email-code') }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">{{ __('learn.send_me_a_code') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Google account --}}
            @if($googleEnabled || $user->google_id)
                <hr>
                <h6 class="fw-bold">{{ __('learn.google_account') }}</h6>
                @if($user->google_id)
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small"><x-icon name="check-circle-fill" class="text-success me-1" />{{ __('learn.google_linked') }}</span>
                        <form method="POST" action="{{ route('profile.google.unlink') }}" data-confirm="{{ __('learn.google_unlink_confirm') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0" @disabled(! $user->has_password)>{{ __('learn.google_unlink') }}</button>
                        </form>
                    </div>
                    @unless($user->has_password)<div class="small text-muted">{{ __('learn.google_unlink_needs_password') }}</div>@endunless
                    @error('google')<div class="text-danger small">{{ $message }}</div>@enderror
                @else
                    <p class="small text-muted mb-0">{{ __('learn.google_not_linked') }}</p>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($pending)
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        var el = document.getElementById('qrcode');
        if (window.QRCode && el) new QRCode(el, { text: el.dataset.uri, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
    })();
</script>
@endif
@endpush
