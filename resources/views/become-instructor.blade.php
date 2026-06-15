@extends('layouts.app')
@section('title', 'Become an Instructor')

@section('content')
<!-- Hero -->
<section class="hero-section"
         style="background-image:url('https://images.unsplash.com/photo-1571260899304-425eee4c7efc?w=1400&q=80');"
         aria-label="Instructors teaching">
    <div class="container hero-content py-5">
        <div class="col-lg-7">
            <h1>Share Your Knowledge.<br>Change Lives.</h1>
            <p>Join ZTF University Institute's growing network of online educators. Create courses in 6 languages and reach learners across Africa and the globe.</p>
            <a href="#apply-form" class="btn btn-secondary btn-lg mt-2">Apply to Teach</a>
        </div>
    </div>
</section>

<!-- Benefits -->
<section class="py-5 bg-light-gray">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why Teach on <span>ACADEXXA</span>?</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4">
            @foreach([
                ['🌍','Global Reach','Reach thousands of students across Africa, Europe, and beyond.'],
                ['🌐','6 Languages','Deliver your courses in English, French, Spanish, Portuguese, Chinese, or Arabic.'],
                ['🛠️','Easy Tools','Use our intuitive course builder — no technical skills needed.'],
                ['📊','Real Analytics','Track enrollments, ratings, and student progress in real-time.'],
                ['🏛️','ZTF-UI Brand','Be associated with ZTF University Institute, a respected academic institution.'],
                ['💰','Earn Revenue','Monetization is coming — get in early and build your audience now.'],
            ] as $b)
            <div class="col-md-4">
                <div class="bg-white rounded-xl shadow-brand p-4 text-center h-100">
                    <div style="font-size:2.5rem;margin-bottom:.75rem;">{{ $b[0] }}</div>
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
            <h2 class="section-title">Apply to Become an Instructor</h2>
            <div class="section-divider"></div>
        </div>

        @if($alreadyApplied)
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i>
            You have already applied. Our team will review your application and get back to you within 3-5 business days.
        </div>
        @elseif(auth()->check() && auth()->user()->isInstructor())
        <div class="alert alert-success text-center">
            You are already a confirmed instructor! <a href="{{ route('instructor.dashboard') }}">Go to Instructor Portal</a>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-brand p-4">
            @guest
            <div class="alert alert-warning mb-4">
                <i class="bi bi-lock me-2"></i>
                Please <a href="{{ route('login') }}">log in</a> or <a href="{{ route('register') }}">register</a> before applying.
            </div>
            @endguest

            <form method="POST" action="{{ route('instructor.apply.store') }}"
                  {{ !auth()->check() ? 'onsubmit=return false;' : '' }} novalidate>
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-bold">Professional Bio *</label>
                    <textarea name="bio" class="form-control @error('bio') is-invalid @enderror"
                              rows="4" minlength="100"
                              placeholder="Tell us about your background, experience, and teaching style (minimum 100 characters)">{{ old('bio') }}</textarea>
                    @error('bio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Credentials & Qualifications *</label>
                    <textarea name="credentials" class="form-control @error('credentials') is-invalid @enderror"
                              rows="3" minlength="50"
                              placeholder="Degrees, certifications, years of experience, institutions...">{{ old('credentials') }}</textarea>
                    @error('credentials') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Area of Expertise *</label>
                    <input type="text" name="expertise" class="form-control @error('expertise') is-invalid @enderror"
                           placeholder="e.g., Data Science, Law, Medicine, Engineering..."
                           value="{{ old('expertise') }}">
                    @error('expertise') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Sample Content / Course Ideas</label>
                    <textarea name="sample_content" class="form-control" rows="3"
                              placeholder="Describe the courses you'd like to create on ACADEXXA...">{{ old('sample_content') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100"
                        {{ !auth()->check() ? 'disabled' : '' }}>
                    <i class="bi bi-send me-2"></i>Submit Application
                </button>
            </form>
        </div>
        @endif
    </div>
</section>
@endsection
