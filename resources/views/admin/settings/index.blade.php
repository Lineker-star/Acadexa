@extends('layouts.admin')
@section('title', __('Settings'))
@section('breadcrumb') <li class="breadcrumb-item active">{{ __('Settings') }}</li> @endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('Site Settings') }}</h5>
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                @csrf

                <h6 class="fw-bold text-muted mb-3">{{ __('General') }}</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Site Name') }}</label>
                        <input type="text" name="site_name" class="form-control"
                               value="{{ $settings['site_name'] ?? 'ACADEXA' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Trial Days (Default: 30)') }}</label>
                        <input type="number" name="trial_days" class="form-control" min="1" max="365"
                               value="{{ $settings['trial_days'] ?? 30 }}">
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">{{ __('learn.hero_image') }}</h6>
                <div class="row g-3 mb-4 align-items-center">
                    <div class="col-md-5">
                        <img src="{{ \App\Support\Branding::heroUrl() }}" alt="" class="img-fluid rounded border" style="aspect-ratio:16/9;object-fit:cover;width:100%">
                    </div>
                    <div class="col-md-7">
                        <input type="file" name="hero_image" class="form-control @error('hero_image') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                        @error('hero_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('learn.hero_image_help') }}</div>
                        @if(! empty($settings['hero_image']))
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_hero_image" value="1" id="removeHero">
                                <label class="form-check-label small" for="removeHero">{{ __('learn.hero_image_reset') }}</label>
                            </div>
                        @endif
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">{{ __('Contact Information') }}</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Contact Email') }}</label>
                        <input type="email" name="contact_email" class="form-control"
                               value="{{ $settings['contact_email'] ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Contact Phone') }}</label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="{{ $settings['contact_phone'] ?? '' }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">{{ __('Address') }}</label>
                        <textarea name="contact_address" class="form-control" rows="2">{{ $settings['contact_address'] ?? '' }}</textarea>
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">{{ __('Social Media') }}</h6>
                <div class="row g-3 mb-4">
                    @foreach(['facebook_url'=>'Facebook','twitter_url'=>'Twitter/X','youtube_url'=>'YouTube','linkedin_url'=>'LinkedIn','instagram_url'=>'Instagram'] as $key => $label)
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __(':network link', ['network' => $label]) }}</label>
                        <input type="url" name="{{ $key }}" class="form-control"
                               value="{{ $settings[$key] ?? '' }}" placeholder="{{ __('https://...') }}">
                    </div>
                    @endforeach
                </div>

                <h6 class="fw-bold text-muted mb-3">{{ __('Certificate Signature') }}</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Signature Name') }}</label>
                        <input type="text" name="cert_sig_name" class="form-control"
                               value="{{ $settings['cert_sig_name'] ?? 'Prof. Emmanuel ZANG' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">{{ __('Signature Title') }}</label>
                        <input type="text" name="cert_sig_title" class="form-control"
                               value="{{ $settings['cert_sig_title'] ?? 'Director, ZTF University Institute' }}">
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">{{ __('security.platform') }}</h6>
                <div class="mb-4">
                    @foreach([
                        'allow_registration'         => ['label' => __('security.allow_registration'), 'default' => '1'],
                        'registration_code'          => ['label' => __('learn.setting_registration_code'), 'default' => '1'],
                        'require_email_verification' => ['label' => __('security.require_email_verification'), 'default' => '0'],
                        'maintenance_mode'           => ['label' => __('security.maintenance_mode'), 'default' => '0'],
                    ] as $key => $opt)
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}" value="1" id="set-{{ $key }}"
                                   @checked(($settings[$key] ?? $opt['default']) === '1')>
                            <label class="form-check-label small" for="set-{{ $key }}">{{ $opt['label'] }}</label>
                        </div>
                    @endforeach
                    <div class="form-text">{{ __('security.email_verification_help') }}</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <x-icon name="save" class="me-2" />{{ __('Save Settings') }}
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">{{ __('Quick Info') }}</h6>
            <ul class="list-unstyled" style="font-size:.875rem;">
                <li class="mb-2"><x-icon name="info-circle" class="text-primary me-2" />{{ __('Trial days applies to new student registrations.') }}</li>
                <li class="mb-2"><x-icon name="shield-check" class="text-primary me-2" />{{ __('Admins and instructors are never affected by trial limits.') }}</li>
                <li class="mb-2"><x-icon name="envelope" class="text-primary me-2" />{{ __('Contact info appears in the site footer and contact page.') }}</li>
                <li><x-icon name="award" class="text-primary me-2" />{{ __('Certificate signature used on all generated PDFs.') }}</li>
            </ul>
        </div>
    </div>
</div>
@endsection
