@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', 'ACADEXXA — Empowering World Innovators and Leaders for Global Impact. Learn from top instructors at ZTF University Institute, Bertoua, Cameroon.')

@section('content')

{{-- ─── HERO ────────────────────────────────────────────────────────────────── --}}
<section class="hero-section"
         style="background-image: url('/images/hero.jpg');"
         aria-label="African students learning">
    <div class="container hero-content py-5">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-8">
                <div class="d-inline-block px-3 py-1 rounded-pill mb-3"
                     style="background:rgba(193,68,14,.25);color:#FFCBA4;font-size:.85rem;font-weight:600;border:1px solid rgba(193,68,14,.4);">
                    🌍 ZTF University Institute — Bertoua, Cameroon
                </div>
                <h1>Empowering World Innovators<br>and Leaders for <span style="color:#C1440E;">Global Impact</span></h1>
                <p class="my-4" style="font-size:1.15rem;max-width:560px;">
                    Access world-class education online. {{ $stats['courses'] ?? 0 }}+ courses,
                    {{ $stats['instructors'] ?? 0 }}+ expert instructors, in 6 languages.
                    Start your free trial today.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-lg">
                        <i class="bi bi-play-circle me-2"></i>{{ __('courses.explore_courses') }}
                    </a>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                            {{ __('navigation.start_free') }}
                        </a>
                    @endguest
                </div>

                <!-- Search bar in hero (mobile) -->
                <form action="{{ route('search') }}" method="GET" class="mt-4 d-lg-none">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control"
                               placeholder="{{ __('navigation.search') }} courses...">
                        <button class="btn btn-secondary"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ─── STATS ───────────────────────────────────────────────────────────────── --}}
<section class="stats-bar">
    <div class="container">
        <div class="row g-4 text-center">
            @foreach([
                ['num' => $stats['courses'] ?? 0,     'label' => __('dashboard.total_courses'),   'icon' => '📚'],
                ['num' => $stats['students'] ?? 0,    'label' => __('dashboard.total_students'),  'icon' => '🎓'],
                ['num' => $stats['instructors'] ?? 0, 'label' => __('dashboard.instructors'),     'icon' => '👨‍🏫'],
                ['num' => $stats['certificates'] ?? 0,'label' => __('dashboard.certificates'),    'icon' => '🏆'],
            ] as $stat)
            <div class="col-6 col-md-3 stat-item">
                <div style="font-size:2rem;margin-bottom:.25rem;">{{ $stat['icon'] }}</div>
                <div class="stat-num">{{ number_format($stat['num']) }}+</div>
                <div class="stat-label">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── CATEGORIES ──────────────────────────────────────────────────────────── --}}
@if($categories->count())
<section class="py-5 bg-light-gray">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">{{ __('courses.browse_by') }} <span>{{ __('courses.category') }}</span></h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-3">
            @foreach($categories->take(12) as $cat)
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('categories.show', $cat->slug) }}" class="text-decoration-none">
                    <div class="category-card h-100">
                        <div class="icon">{{ $cat->icon ?? '📖' }}</div>
                        <div class="name">{{ $cat->name(app()->getLocale()) }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem;">
                            {{ $cat->courses()->published()->count() }} {{ __('courses.courses') }}
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ─── FEATURED COURSES ────────────────────────────────────────────────────── --}}
@if($featuredCourses->count())
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-1">{{ __('courses.featured') }} <span>{{ __('courses.courses') }}</span></h2>
                <div class="section-divider" style="margin:0;"></div>
            </div>
            <a href="{{ route('courses.index') }}" class="btn btn-outline-primary btn-sm">{{ __('courses.view_all') }} <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            @foreach($featuredCourses as $course)
            <div class="col-sm-6 col-lg-3">
                @include('partials.course-card', ['course' => $course])
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ─── WHY ACADEXXA ─────────────────────────────────────────────────────────── --}}
<section class="py-5 bg-light-gray">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why Choose <span>ACADEXXA</span>?</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4">
            @foreach([
                ['icon'=>'🌐','title'=>'6 Languages','desc'=>'Learn in English, French, Spanish, Portuguese, Chinese, or Arabic.'],
                ['icon'=>'🎓','title'=>'Expert Instructors','desc'=>'Courses created by ZTF University Institute academics and industry professionals.'],
                ['icon'=>'📱','title'=>'Learn Anywhere','desc'=>'Access your courses on any device, anytime, at your own pace.'],
                ['icon'=>'🏆','title'=>'Earn Certificates','desc'=>'Receive verifiable digital certificates upon course completion.'],
                ['icon'=>'🆓','title'=>'Free Trial','desc'=>'Get 30 days of free access to explore courses before subscribing.'],
                ['icon'=>'🛡️','title'=>'Quality Assured','desc'=>'Every course reviewed by our academic team before publishing.'],
            ] as $f)
            <div class="col-md-4">
                <div class="bg-white rounded-xl p-4 shadow-brand h-100">
                    <div style="font-size:2.5rem;margin-bottom:.75rem;">{{ $f['icon'] }}</div>
                    <h5 style="color:var(--primary);">{{ $f['title'] }}</h5>
                    <p class="text-muted mb-0" style="font-size:.9rem;">{{ $f['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── LATEST COURSES ──────────────────────────────────────────────────────── --}}
@if($latestCourses->count())
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-1">{{ __('courses.latest') }} <span>{{ __('courses.courses') }}</span></h2>
                <div class="section-divider" style="margin:0;"></div>
            </div>
            <a href="{{ route('courses.index') }}" class="btn btn-outline-primary btn-sm">{{ __('courses.view_all') }}</a>
        </div>
        <div class="row g-4">
            @foreach($latestCourses as $course)
            <div class="col-sm-6 col-lg-4">
                @include('partials.course-card', ['course' => $course])
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ─── BECOME INSTRUCTOR CTA ───────────────────────────────────────────────── --}}
<section class="py-5"
         style="background:linear-gradient(135deg,var(--primary) 0%,#0d3a7a 100%);">
    <div class="container text-center text-white py-3">
        <h2 style="color:#fff;font-size:2rem;">Become an ACADEXXA Instructor</h2>
        <p style="color:rgba(255,255,255,.85);font-size:1.1rem;max-width:600px;margin:.75rem auto 1.5rem;">
            Share your expertise with thousands of learners across Africa and the world.
            Join our growing community of educators.
        </p>
        <a href="{{ route('become-instructor') }}" class="btn btn-secondary btn-lg">
            <i class="bi bi-mortarboard me-2"></i>Start Teaching Today
        </a>
    </div>
</section>

@endsection
