@extends('layouts.admin')
@section('title', 'Certificate Template')
@section('breadcrumb') <li class="breadcrumb-item active">Certificate Template</li> @endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">Certificate Template Settings</h5>
            <form method="POST" action="{{ route('admin.certificate.template.update') }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Institution Name</label>
                        <input type="text" name="cert_institution" class="form-control"
                               value="{{ $settings['cert_institution'] ?? 'ZTF University Institute' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Sub-heading</label>
                        <input type="text" name="cert_subheading" class="form-control"
                               value="{{ $settings['cert_subheading'] ?? 'ACADEXXA Learning Management System' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Signature Name</label>
                        <input type="text" name="cert_sig_name" class="form-control"
                               value="{{ $settings['cert_sig_name'] ?? 'Prof. Emmanuel ZANG' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Signature Title</label>
                        <input type="text" name="cert_sig_title" class="form-control"
                               value="{{ $settings['cert_sig_title'] ?? 'Director, ZTF University Institute' }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Certificate Description Text</label>
                        <textarea name="cert_description" class="form-control" rows="3">{{ $settings['cert_description'] ?? 'This is to certify that the above-named individual has successfully completed the course with distinction.' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Logo (top of certificate)</label>
                        <input type="file" name="cert_logo" class="form-control" accept="image/*">
                        @if(!empty($settings['cert_logo']))
                        <small class="text-muted">Current: {{ $settings['cert_logo'] }}</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Background Color</label>
                        <input type="color" name="cert_bg_color" class="form-control form-control-color"
                               value="{{ $settings['cert_bg_color'] ?? '#0A2A5E' }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Template
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">Preview Certificate</h6>
            <div class="text-center p-3 rounded border" style="background:{{ $settings['cert_bg_color'] ?? '#0A2A5E' }};color:white;font-size:.75rem;">
                <div style="font-size:.9rem;font-weight:bold;">{{ $settings['cert_institution'] ?? 'ZTF University Institute' }}</div>
                <div class="mt-1 mb-2" style="font-size:.7rem;opacity:.8;">{{ $settings['cert_subheading'] ?? 'ACADEXXA LMS' }}</div>
                <div style="font-size:.7rem;letter-spacing:2px;opacity:.6;">CERTIFICATE OF COMPLETION</div>
                <div class="my-2" style="font-size:1rem;font-weight:bold;">Student Name</div>
                <div style="font-size:.7rem;opacity:.8;">has completed</div>
                <div style="font-size:.85rem;font-style:italic;margin:.25rem 0;">Course Title Here</div>
                <div class="mt-2" style="font-size:.7rem;">
                    <div style="font-weight:bold;">{{ $settings['cert_sig_name'] ?? 'Director Name' }}</div>
                    <div style="opacity:.8;">{{ $settings['cert_sig_title'] ?? 'Director' }}</div>
                </div>
            </div>
            <p class="text-muted small mt-2 mb-0">This is a simplified preview. Download an actual certificate to see the full PDF design.</p>
        </div>
    </div>
</div>
@endsection
