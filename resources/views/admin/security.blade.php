@extends('layouts.admin')
@section('title', __('security.my_security'))
@section('page-title', __('security.my_security'))

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">
<div class="bg-white rounded-xl shadow-brand p-4">
    <h5 class="fw-bold"><x-icon name="shield-lock" class="me-2" />{{ __('security.2fa_title') }}</h5>

    @if($user->hasTwoFactorEnabled())
        <div class="alert alert-success"><x-icon name="check-circle" class="me-1" />{{ __('security.2fa_active_since', ['date' => $user->two_factor_confirmed_at->isoFormat('L')]) }}</div>
        <form method="POST" action="{{ route('admin.2fa.disable') }}" class="row g-2">
            @csrf
            <p class="small text-muted mb-1">{{ __('security.2fa_disable_help') }}</p>
            <div class="col-sm-5"><input type="password" name="password" class="form-control form-control-sm" required placeholder="{{ __('security.password') }}"></div>
            <div class="col-sm-4"><input type="text" name="code" inputmode="numeric" class="form-control form-control-sm @error('code') is-invalid @enderror" required placeholder="000000"></div>
            <div class="col-sm-3"><button class="btn btn-sm btn-outline-danger w-100">{{ __('security.disable') }}</button></div>
            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
        </form>
    @else
        <p class="text-muted">{{ __('security.2fa_intro') }}</p>
        <ol class="small">
            <li>{{ __('security.2fa_step1') }}</li>
            <li>{{ __('security.2fa_step2') }}</li>
            <li>{{ __('security.2fa_step3') }}</li>
        </ol>
        <div class="d-flex flex-wrap gap-4 align-items-center my-3">
            <div id="qrcode" class="p-2 bg-white border rounded" style="width:196px;height:196px" data-uri="{{ $pending['uri'] }}"></div>
            <div>
                <div class="small text-muted">{{ __('security.manual_key') }}</div>
                <code class="fs-6 user-select-all">{{ trim(chunk_split($pending['secret'], 4, ' ')) }}</code>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.2fa.enable') }}" class="d-flex gap-2" style="max-width:360px">
            @csrf
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror" required placeholder="000000">
            <button class="btn btn-primary text-nowrap">{{ __('security.enable') }}</button>
        </form>
        @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @endif
</div>
</div>
</div>
@endsection

@push('scripts')
@unless($user->hasTwoFactorEnabled())
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        var el = document.getElementById('qrcode');
        if (window.QRCode && el) new QRCode(el, { text: el.dataset.uri, width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M });
    })();
</script>
@endunless
@endpush
