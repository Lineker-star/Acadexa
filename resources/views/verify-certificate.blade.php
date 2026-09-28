@extends('layouts.app')
@section('title', __('Verify Certificate'))

@section('content')
<div class="py-5 bg-light-gray" style="min-height:60vh;">
    <div class="container" style="max-width:640px;">
        <div class="text-center mb-4">
            <h2 class="section-title">{{ __('Certificate') }} <span>{{ __('Verification') }}</span></h2>
            <div class="section-divider"></div>
        </div>

        @if($certificate)
        <div class="bg-white rounded-xl shadow-brand p-5 text-center">
            <div style="font-size:4rem;"><x-icon name="check-circle-fill" /></div>
            <h3 class="mt-3 text-success">{{ __('Certificate Verified!') }}</h3>
            <p class="text-muted">{{ __('This is a valid ACADEXA certificate.') }}</p>

            <hr class="my-4">
            <div class="row text-start">
                <div class="col-6 mb-3">
                    <label class="text-muted small fw-bold">{{ __('RECIPIENT') }}</label>
                    <div class="fw-bold">{{ $certificate->user->name }}</div>
                </div>
                <div class="col-6 mb-3">
                    <label class="text-muted small fw-bold">{{ __('ISSUED') }}</label>
                    <div class="fw-bold">{{ $certificate->issued_at->isoFormat('LL') }}</div>
                </div>
                <div class="col-12 mb-3">
                    <label class="text-muted small fw-bold">{{ __('COURSE') }}</label>
                    <div class="fw-bold">{{ $certificate->course->title() }}</div>
                </div>
                <div class="col-12">
                    <label class="text-muted small fw-bold">{{ __('CERTIFICATE CODE') }}</label>
                    <div style="font-family:monospace;font-size:1.2rem;color:var(--primary);">{{ $certificate->certificate_code }}</div>
                </div>
            </div>
            <hr class="my-4">
            <p class="text-muted small">{!! __('Issued by :site — ZTF University Institute, Bertoua, Cameroon', ['site' => '<strong>' . e($siteSettings['site_name'] ?? 'ACADEXA') . '</strong>']) !!}</p>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-brand p-5 text-center">
            <div style="font-size:4rem;"><x-icon name="x-circle-fill" /></div>
            <h3 class="mt-3 text-danger">{{ __('Certificate Not Found') }}</h3>
            <p class="text-muted">{!! __('The certificate code :code was not found in our system.', ['code' => '<code>' . e($code) . '</code>']) !!}</p>
            <p class="text-muted small">{{ __('If you believe this is an error, please contact us at :email', ['email' => $siteSettings['contact_email'] ?? 'info@ztfuniversity.com']) }}</p>
        </div>
        @endif

        <!-- Verification Search -->
        <div class="bg-white rounded-xl shadow-brand p-4 mt-4">
            <h6 class="mb-3">{{ __('Verify Another Certificate') }}</h6>
            <form action="" method="GET" class="d-flex gap-2">
                <input type="text" name="code" class="form-control" placeholder="{{ __('Enter certificate code (e.g., ACADEXA-ABCD-EFGH-2025)') }}">
                <button class="btn btn-primary" onclick="this.form.action='/verify-certificate/'+this.form.code.value;return true;">
                    {{ __('Verify') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
