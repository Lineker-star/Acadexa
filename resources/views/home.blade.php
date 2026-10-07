@extends('layouts.app')

@section('title', __('Home'))
@section('meta_description', __('ACADEXXA — Empowering World Innovators and Leaders for Global Impact. Learn from top instructors at ZTF University Institute, Bertoua, Cameroon.'))

@section('content')

{{-- ─── HERO ────────────────────────────────────────────────────────────────── --}}
<section class="hero-section"
         style="background-image: url('{{ \App\Support\Branding::heroUrl() }}'); background-position: 70% center;"
         aria-label="{{ __('African students learning') }}">
    <div class="container hero-content py-5">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-8">
                <div class="d-inline-block px-3 py-1 rounded-pill mb-3"
                     style="background:rgba(193,68,14,.25);color:#FFCBA4;font-size:.85rem;font-weight:600;border:1px solid rgba(193,68,14,.4);">
                    <x-icon name="geo-alt-fill" class="me-1" />{{ __('ZTF University Institute — Bertoua, Cameroon') }}
                </div>
                <h1>{{ __('Empowering World Innovators') }}<br>{!! __('and Leaders for :impact', ['impact' => '<span style="color:#C1440E;">' . e(__('Global Impact')) . '</span>']) !!}</h1>
                <p class="my-4" style="font-size:1.15rem;max-width:560px;">
                    {{ __('Access world-class education online. :courses+ courses, :instructors+ expert instructors, in 6 languages. Start your free trial today.', ['courses' => $stats['courses'] ?? 0, 'instructors' => $stats['instructors'] ?? 0]) }}
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('courses.index') }}" class="btn btn-secondary btn-lg">
                        <x-icon name="play-circle" class="me-2" />{{ __('courses.explore_courses') }}
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
                               placeholder="{{ __('Search courses...') }}">
                        <button class="btn btn-secondary"><x-icon name="search" /></button>
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
                ['num' => $stats['courses'] ?? 0,     'label' => __('dashboard.total_courses'),   'icon' => 'collection-play'],
                ['num' => $stats['students'] ?? 0,    'label' => __('dashboard.total_students'),  'icon' => 'mortarboard'],
                ['num' => $stats['instructors'] ?? 0, 'label' => __('dashboard.instructors'),     'icon' => 'person-video3'],
                ['num' => $stats['certificates'] ?? 0,'label' => __('dashboard.certificates'),    'icon' => 'trophy'],
            ] as $stat)
            <div class="col-6 col-md-3 stat-item">
                <div style="font-size:2rem;margin-bottom:.25rem;"><x-icon :name="$stat['icon']" /></div>
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
                        <div class="icon"><x-icon :name="$cat->icon ?: 'book'" /></div>
                        <div class="name">{{ $cat->name(app()->getLocale()) }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem;">
                            {{ trans_choice(':count course|:count courses', $cat->courses()->published()->count(), ['count' => $cat->courses()->published()->count()]) }}
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
            <a href="{{ route('courses.index') }}" class="btn btn-outline-primary btn-sm">{{ __('courses.view_all') }} <x-icon name="arrow-right" class="ms-1" /></a>
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
            <h2 class="section-title">{!! __('Why choose :brand?', ['brand' => '<span>' . e($siteSettings['site_name'] ?? 'ACADEXXA') . '</span>']) !!}</h2>
            <div class="section-divider"></div>
        </div>
        <div class="row g-4">
            @foreach([
                ['icon' => 'translate',      'title' => __('6 Languages'),        'desc' => __('Learn in English, French, Spanish, Portuguese, Chinese, or Arabic.')],
                ['icon' => 'mortarboard',    'title' => __('Expert Instructors'), 'desc' => __('Courses created by ZTF University Institute academics and industry professionals.')],
                ['icon' => 'phone',          'title' => __('Learn Anywhere'),     'desc' => __('Access your courses on any device, anytime, at your own pace — even offline.')],
                ['icon' => 'award',          'title' => __('Earn Certificates'),  'desc' => __('Receive verifiable digital certificates upon course completion.')],
                ['icon' => 'gift',           'title' => __('Free Trial'),         'desc' => __('Get :days days of free access to explore courses before subscribing.', ['days' => $siteSettings['trial_days'] ?? 30])],
                ['icon' => 'shield-check',   'title' => __('Quality Assured'),    'desc' => __('Every course reviewed by our academic team before publishing.')],
            ] as $f)
            <div class="col-md-4">
                <div class="bg-white rounded-xl p-4 shadow-brand h-100">
                    <div style="font-size:2.5rem;margin-bottom:.75rem;color:var(--secondary);"><x-icon :name="$f['icon']" /></div>
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
        <h2 style="color:#fff;font-size:2rem;">{{ __('Become an ACADEXXA Instructor') }}</h2>
        <p style="color:rgba(255,255,255,.85);font-size:1.1rem;max-width:600px;margin:.75rem auto 1.5rem;">
            {{ __('Share your expertise with thousands of learners across Africa and the world. Join our growing community of educators.') }}
        </p>
        <a href="{{ route('become-instructor') }}" class="btn btn-secondary btn-lg">
            <x-icon name="mortarboard" class="me-2" />{{ __('Start Teaching Today') }}
        </a>
    </div>
</section>

@endsection
