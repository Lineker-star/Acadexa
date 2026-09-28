@extends('layouts.app')
@section('title', __('navigation.contact'))

@section('content')
<div class="breadcrumb-bar"><div class="container"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li><li class="breadcrumb-item active">{{ __('Contact') }}</li></ol></div></div>

<div class="py-5 bg-light-gray">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <h2 class="section-title mb-2">{!! __('Get in :touch', ['touch' => '<span>' . e(__('touch')) . '</span>']) !!}</h2>
                <div class="section-divider" style="margin:0 0 1.5rem;"></div>
                <p class="text-muted">{{ __('Have a question about our courses, admissions, or partnerships? We\'d love to hear from you.') }}</p>
                <ul class="list-unstyled mt-4">
                    <li class="d-flex gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><x-icon name="geo-alt" class="text-primary" /></div>
                        <div><strong>{{ __('Address') }}</strong><br><span class="text-muted small">{{ __('ZTF University Institute, Koumé – Bertoua, East Region, Cameroon') }}</span></div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><x-icon name="envelope" class="text-primary" /></div>
                        <div><strong>{{ __('Email') }}</strong><br><a href="mailto:{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}" class="text-muted small">{{ $siteSettings['contact_email'] ?? 'info@acadexxa.com' }}</a></div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><x-icon name="telephone" class="text-primary" /></div>
                        <div><strong>{{ __('Phone') }}</strong><br><span class="text-muted small">{{ $siteSettings['contact_phone'] ?? '+237 000 000 000' }}</span></div>
                    </li>
                    <li class="d-flex gap-3">
                        <div style="width:40px;height:40px;background:#EEF3FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><x-icon name="globe" class="text-primary" /></div>
                        <div><strong>{{ __('Website') }}</strong><br><a href="https://www.ztfuniversity.com" target="_blank" class="text-muted small">www.ztfuniversity.com</a></div>
                    </li>
                </ul>
            </div>
            <div class="col-lg-8">
                <div class="bg-white rounded-xl shadow-brand p-4">
                    <h4 class="mb-4">{{ __('Send Us a Message') }}</h4>
                    <form method="POST" action="{{ route('contact.store') }}" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">{{ __('Name *') }}</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="{{ __('Your full name') }}">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">{{ __('Email *') }}</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" required placeholder="{{ __('you@example.com') }}">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">{{ __('Subject *') }}</label>
                                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject') }}" required>
                                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">{{ __('Message *') }}</label>
                                <textarea name="message" class="form-control @error('message') is-invalid @enderror"
                                          rows="5" required minlength="10">{{ old('message') }}</textarea>
                                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <x-icon name="send" class="me-2" />{{ __('Send Message') }}
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
