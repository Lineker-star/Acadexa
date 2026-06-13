@extends('layouts.app')
@section('title', __('navigation.contact'))

@section('content')
<div class="breadcrumb-bar"><div class="container"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active">Contact</li></ol></div></div>

<div class="py-5 bg-light-gray">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h2 class="section-title mb-2">Get In <span>Touch</span></h2>
                <div class="section-divider" style="margin:0 0 1.5rem;"></div>
                <p class="text-muted">Have a question about our courses, admissions, or partnerships? We'd love to hear from you.</p>
                <ul class="list-unstyled mt-4">
                    <li class="d-flex gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-geo-alt text-primary"></i></div>
                        <div><strong>Address</strong><br><span class="text-muted small">ZTF University Institute, Koumé – Bertoua, East Region, Cameroon</span></div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-envelope text-primary"></i></div>
                        <div><strong>Email</strong><br><a href="mailto:{{ $siteSettings['contact_email'] ?? 'info@acadexa.com' }}" class="text-muted small">{{ $siteSettings['contact_email'] ?? 'info@acadexa.com' }}</a></div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-telephone text-primary"></i></div>
                        <div><strong>Phone</strong><br><span class="text-muted small">{{ $siteSettings['contact_phone'] ?? '+237 000 000 000' }}</span></div>
                    </li>
                    <li class="d-flex gap-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-globe text-primary"></i></div>
                        <div><strong>Website</strong><br><a href="https://www.ztfuniversity.com" target="_blank" class="text-muted small">www.ztfuniversity.com</a></div>
                    </li>
                </ul>
            </div>
            <div class="col-lg-8">
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <h4 class="mb-4">Send Us a Message</h4>
                    <form method="POST" action="{{ route('contact.store') }}" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Name *</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="Your full name">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email *</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required placeholder="you@example.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Subject *</label>
                                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject') }}" required>
                                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Message *</label>
                                <textarea name="message" class="form-control @error('message') is-invalid @enderror"
                                          rows="5" required minlength="10">{{ old('message') }}</textarea>
                                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
