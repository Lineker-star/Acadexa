@extends('layouts.app')
@section('title', 'Verify Certificate')

@section('content')
<div class="py-5 bg-light-gray" style="min-height:60vh;">
    <div class="container" style="max-width:640px;">
        <div class="text-center mb-4">
            <h2 class="section-title">Certificate <span>Verification</span></h2>
            <div class="section-divider"></div>
        </div>

        @if($certificate)
        <div class="bg-white rounded-xl shadow-brand p-5 text-center">
            <div style="font-size:4rem;">✅</div>
            <h3 class="mt-3 text-success">Certificate Verified!</h3>
            <p class="text-muted">This is a valid ACADEXXA certificate.</p>

            <hr class="my-4">
            <div class="row text-start">
                <div class="col-6 mb-3">
                    <label class="text-muted small fw-bold">RECIPIENT</label>
                    <div class="fw-bold">{{ $certificate->user->name }}</div>
                </div>
                <div class="col-6 mb-3">
                    <label class="text-muted small fw-bold">ISSUED</label>
                    <div class="fw-bold">{{ $certificate->issued_at->format('F d, Y') }}</div>
                </div>
                <div class="col-12 mb-3">
                    <label class="text-muted small fw-bold">COURSE</label>
                    <div class="fw-bold">{{ $certificate->course->title() }}</div>
                </div>
                <div class="col-12">
                    <label class="text-muted small fw-bold">CERTIFICATE CODE</label>
                    <div style="font-family:monospace;font-size:1.2rem;color:var(--primary);">{{ $certificate->certificate_code }}</div>
                </div>
            </div>
            <hr class="my-4">
            <p class="text-muted small">Issued by <strong>ACADEXXA</strong> — ZTF University Institute, Bertoua, Cameroon</p>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-brand p-5 text-center">
            <div style="font-size:4rem;">❌</div>
            <h3 class="mt-3 text-danger">Certificate Not Found</h3>
            <p class="text-muted">The certificate code <code>{{ $code }}</code> was not found in our system.</p>
            <p class="text-muted small">If you believe this is an error, please contact us at {{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}</p>
        </div>
        @endif

        <!-- Verification Search -->
        <div class="bg-white rounded-xl shadow-brand p-4 mt-4">
            <h6 class="mb-3">Verify Another Certificate</h6>
            <form action="" method="GET" class="d-flex gap-2">
                <input type="text" name="code" class="form-control" placeholder="Enter certificate code (e.g., ACADEXXA-ABCD-EFGH-2025)">
                <button class="btn btn-primary" onclick="this.form.action='/verify-certificate/'+this.form.code.value;return true;">
                    Verify
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
