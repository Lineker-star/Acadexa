@extends('layouts.admin')
@section('title', 'Settings')
@section('breadcrumb') <li class="breadcrumb-item active">Settings</li> @endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">Site Settings</h5>
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf

                <h6 class="fw-bold text-muted mb-3">General</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Site Name</label>
                        <input type="text" name="site_name" class="form-control"
                               value="{{ $settings['site_name'] ?? 'ACADEXA' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Trial Days (Default: 30)</label>
                        <input type="number" name="trial_days" class="form-control" min="1" max="365"
                               value="{{ $settings['trial_days'] ?? 30 }}">
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">Contact Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control"
                               value="{{ $settings['contact_email'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="{{ $settings['contact_phone'] ?? '' }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Address</label>
                        <textarea name="contact_address" class="form-control" rows="2">{{ $settings['contact_address'] ?? '' }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">Social Media</h6>
                <div class="row g-3 mb-4">
                    @foreach(['facebook_url'=>'Facebook','twitter_url'=>'Twitter/X','youtube_url'=>'YouTube','linkedin_url'=>'LinkedIn','instagram_url'=>'Instagram'] as $key => $label)
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ $label }} URL</label>
                        <input type="url" name="{{ $key }}" class="form-control"
                               value="{{ $settings[$key] ?? '' }}" placeholder="https://...">
                    </div>
                    @endforeach
                </div>

                <h6 class="fw-bold text-muted mb-3">Certificate Signature</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Signature Name</label>
                        <input type="text" name="cert_sig_name" class="form-control"
                               value="{{ $settings['cert_sig_name'] ?? 'Prof. Emmanuel ZANG' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Signature Title</label>
                        <input type="text" name="cert_sig_title" class="form-control"
                               value="{{ $settings['cert_sig_title'] ?? 'Director, ZTF University Institute' }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Save Settings
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">Quick Info</h6>
            <ul class="list-unstyled" style="font-size:.875rem;">
                <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Trial days applies to new student registrations.</li>
                <li class="mb-2"><i class="bi bi-shield-check text-primary me-2"></i>Admins and instructors are never affected by trial limits.</li>
                <li class="mb-2"><i class="bi bi-envelope text-primary me-2"></i>Contact info appears in the site footer and contact page.</li>
                <li><i class="bi bi-award text-primary me-2"></i>Certificate signature used on all generated PDFs.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
