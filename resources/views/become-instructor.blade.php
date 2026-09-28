@extends('layouts.app')
@section('title', __('Become an Instructor'))

@section('content')
<!-- Hero -->
<section class="hero-section"
         style="background-image:url('https://images.unsplash.com/photo-1571260899304-425eee4c7efc?w=1400&q=80');"
         aria-label="{{ __('Instructors teaching') }}">
    <div class="container hero-content py-5">
        <div class="col-lg-7">
            <h1>{{ __('Share Your Knowledge.') }}<br>{{ __('Change Lives.') }}</h1>
            <p>{{ __('Join ZTF University Institute\'s growing network of online educators. Create courses in 6 languages and reach learners across Africa and the globe.') }}</p>
            <a href="#apply-form" class="btn btn-secondary btn-lg mt-2">{{ __('Apply to Teach') }}</a>
        </div>
    </div>
</section>

<!-- Benefits -->
<section class="py-5 bg-light-gray">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">{!! __('Why teach on :brand?', ['brand' => '<span>' . e($siteSettings['site_name'] ?? 'ACADEXA') . '</span>']) !!}</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4">
            @foreach([
                ['globe-americas', __('Global Reach'), __('Reach thousands of students across Africa, Europe, and beyond.')],
                ['translate', __('6 Languages'), __('Deliver your courses in English, French, Spanish, Portuguese, Chinese, or Arabic.')],
                ['tools', __('Easy Tools'), __('Use our intuitive course builder — no technical skills needed.')],
                ['bar-chart-line', __('Real Analytics'), __('Track enrollments, ratings, and student progress in real-time.')],
                ['bank', __('ZTF-UI Brand'), __('Be associated with ZTF University Institute, a respected academic institution.')],
                ['cash-coin', __('Earn Revenue'), __('Monetization is coming — get in early and build your audience now.')],
            ] as $b)
            <div class="col-md-4">
                <div class="bg-white rounded-xl shadow-brand p-4 text-center h-100">
                    <div style="font-size:2.5rem;margin-bottom:.75rem;color:var(--secondary);"><x-icon :name="$b[0]" /></div>
                    <h5>{{ $b[1] }}</h5>
                    <p class="text-muted small mb-0">{{ $b[2] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Application Form -->
<section id="apply-form" class="py-5">
    <div class="container" style="max-width:750px;">
        <div class="text-center mb-4">
            <h2 class="section-title">{{ __('Apply to Become an Instructor') }}</h2>
            <div class="section-divider"></div>
        </div>

        @if($alreadyApplied)
        <div class="alert alert-info text-center">
            <x-icon name="info-circle" class="me-2" /> {{ __('You have already applied. Our team will review your application and get back to you within 3-5 business days.') }}
        </div>
        @elseif(auth()->check() && auth()->user()->isInstructor())
        <div class="alert alert-success text-center">
            {{ __('You are already a confirmed instructor!') }} <a href="{{ route('instructor.dashboard') }}">{{ __('Go to Instructor Portal') }}</a>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-brand p-4">
            @guest
            <div class="alert alert-warning mb-4">
                <x-icon name="lock" class="me-2" /> {!! __('Please :login or :register before applying.', ['login' => '<a href="' . e(route('login')) . '">' . e(__('log in')) . '</a>', 'register' => '<a href="' . e(route('register')) . '">' . e(__('register')) . '</a>']) !!}
            </div>
            @endguest

            <form method="POST" action="{{ route('instructor.apply.store') }}"
                  {{ !auth()->check() ? 'onsubmit=return false;' : '' }} novalidate>
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Professional Bio *') }}</label>
                    <textarea name="bio" class="form-control @error('bio') is-invalid @enderror"
                              rows="4" minlength="100"
                              placeholder="{{ __('Tell us about your background, experience, and teaching style (minimum 100 characters)') }}">{{ old('bio') }}</textarea>
                    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Credentials & Qualifications *') }}</label>
                    <textarea name="credentials" class="form-control @error('credentials') is-invalid @enderror"
                              rows="3" minlength="50"
                              placeholder="{{ __('Degrees, certifications, years of experience, institutions...') }}">{{ old('credentials') }}</textarea>
                    @error('credentials') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Area of Expertise *') }}</label>
                    <input type="text" name="expertise" class="form-control @error('expertise') is-invalid @enderror"
                           placeholder="{{ __('e.g., Data Science, Law, Medicine, Engineering...') }}"
                           value="{{ old('expertise') }}">
                    @error('expertise') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('Sample Content / Course Ideas') }}</label>
                    <textarea name="sample_content" class="form-control" rows="3"
                              placeholder="{{ __('Describe the courses you\'d like to create on ACADEXA...') }}">{{ old('sample_content') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100"
                        {{ !auth()->check() ? 'disabled' : '' }}>
                    <x-icon name="send" class="me-2" />{{ __('Submit Application') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</section>
@endsection
