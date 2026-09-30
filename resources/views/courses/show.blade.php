@extends('layouts.app')
@php $trans = $course->translation(); @endphp
@section('title', $trans?->title ?? $course->title())
@section('meta_description', Str::limit(strip_tags($trans?->description ?? ''), 160))

@section('content')
<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">{{ __('Courses') }}</a></li>
            <li class="breadcrumb-item active">{{ $course->title() }}</li>
        </ol>
    </div>
</div>

@if($course->status !== 'published')
<div class="alert alert-warning rounded-0 mb-0 text-center small">
    <x-icon name="eye" class="me-1" />{{ __('learn.preview_unpublished', ['status' => __('lms.status_' . $course->status)]) }}
    @if(auth()->user()?->isAdmin())
        <form method="POST" action="{{ route('admin.courses.approve', $course) }}" class="d-inline ms-2" data-confirm="{{ __('learn.publish_confirm') }}">
            @csrf <button class="btn btn-sm btn-success">{{ __('learn.publish_now') }}</button>
        </form>
    @endif
</div>
@endif
<!-- Hero -->
<section class="hero-section py-5"
         style="min-height:380px;background-image:url('{{ $course->thumbnailUrl() }}');"
         aria-label="{{ $course->title() }}">
    <div class="container hero-content">
        <div class="row">
            <div class="col-lg-8">
                <span class="badge-level {{ $course->level }} mb-3 d-inline-block">{{ __('messages.' . $course->level) }}</span>
                <h1 style="font-size:clamp(1.6rem,3vw,2.5rem);">{{ $course->title() }}</h1>
                <p style="color:rgba(255,255,255,.9);max-width:600px;">{{ Str::limit(strip_tags($course->description()), 200) }}</p>
                <div class="d-flex align-items-center gap-3 mt-3 flex-wrap" style="font-size:.9rem;color:rgba(255,255,255,.85);">
                    <span><x-icon name="star-fill" class="me-1" style="color:#F59E0B;" />{{ $course->avgRating() }} ({{ trans_choice(':count review|:count reviews', $course->reviewCount(), ['count' => $course->reviewCount()]) }})</span>
                    <span><x-icon name="people" class="me-1" />{{ trans_choice(':count student|:count students', $course->enrollmentCount(), ['count' => $course->enrollmentCount()]) }}</span>
                    <span><x-icon name="clock" class="me-1" />{{ $course->hoursLabel() }}</span>
                    <span><x-icon name="person-circle" class="me-1" />
                        <a href="{{ route('instructor.profile', $course->instructor) }}" style="color:rgba(255,255,255,.9);">
                            {{ $course->instructor->name }}
                        </a>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Presentation video, played inside the platform -->
                @if($course->hasIntroVideo())
                <div class="bg-white rounded-xl shadow-brand p-3 mb-4">
                    <h3 class="h5 mb-3 px-1"><x-icon name="play-circle" class="text-primary me-1" />{{ __('learn.intro_video') }}</h3>
                    <div class="ratio ratio-16x9 rounded overflow-hidden bg-dark">
                        @if($course->intro_video_path)
                        <video controls playsinline preload="metadata" controlsList="nodownload" src="{{ route('media.course.intro', $course) }}"></video>
                        @else
                        <iframe src="https://www.youtube-nocookie.com/embed/{{ $course->intro_youtube_id }}?rel=0&modestbranding=1&playsinline=1&hl={{ app()->getLocale() }}"
                                title="{{ __('learn.intro_video') }} — {{ $course->title() }}" loading="lazy"
                                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
                        @endif
                    </div>
                </div>
                @endif

                <!-- What You Learn -->
                @if($trans?->what_you_learn)
                <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
                    <h3 class="h5 mb-3">{{ __('courses.what_you_learn') }}</h3>
                    <div class="row g-2">
                        @foreach(explode("\n", $trans->what_you_learn) as $item)
                            @if(trim($item))
                            <div class="col-md-6 d-flex gap-2">
                                <x-icon name="check2-circle" class="text-success mt-1" />
                                <span style="font-size:.9rem;">{{ trim($item) }}</span>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Requirements -->
                @if($trans?->requirements)
                <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
                    <h3 class="h5 mb-3">{{ __('courses.requirements') }}</h3>
                    <ul class="mb-0" style="font-size:.9rem;">
                        @foreach(explode("\n", $trans->requirements) as $req)
                            @if(trim($req)) <li>{{ trim($req) }}</li> @endif
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Curriculum Accordion -->
                <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
                    <h3 class="h5 mb-3">{{ __('courses.curriculum') }}</h3>
                    <div class="accordion" id="curriculumAccordion">
                        @foreach($course->modules as $module)
                        <div class="accordion-item border-0 mb-2" style="background:var(--light);border-radius:.5rem;overflow:hidden;">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fw-bold"
                                        style="background:var(--primary);color:#fff;font-family:'Poppins',sans-serif;font-size:.95rem;"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#module_{{ $module->id }}">
                                    {{ $module->title(app()->getLocale()) }}
                                    <span class="ms-auto badge bg-white text-primary me-3" style="font-size:.7rem;">
                                        {{ trans_choice('lms.lessons_count', $module->lessons->count(), ['count' => $module->lessons->count()]) }}
                                        @if($module->hoursLabel()) · {{ $module->hoursLabel() }} @endif
                                    </span>
                                </button>
                            </h2>
                            <div id="module_{{ $module->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                                <div class="accordion-body p-0">
                                    @foreach($module->lessons as $lesson)
                                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom" style="font-size:.9rem;">
                                        <x-icon :name="$lesson->icon()" class="text-primary" />
                                        <span class="flex-grow-1">{{ $lesson->title(app()->getLocale()) }}</span>
                                        @if($lesson->is_free_preview)
                                            <a href="{{ route('courses.preview', [$course->slug, $lesson]) }}" class="btn btn-outline-primary btn-sm" style="font-size:.75rem;">{{ __('lms.preview') }}</a>
                                        @endif
                                        @if($lesson->duration_minutes > 0)
                                            <span class="text-muted" style="font-size:.8rem;">{{ $lesson->duration_minutes }}m</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Reviews -->
                <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
                    <h3 class="h5 mb-4">{{ __('courses.student_reviews') }}</h3>

                    @if($isEnrolled && $enrollment->progress_percent >= 100 && !$course->reviews->where('user_id', auth()->id())->count())
                    <div class="mb-4 p-3 bg-light rounded">
                        <h6>{{ __('Leave a Review') }}</h6>
                        <form method="POST" action="{{ route('student.review.store', $course) }}">
                            @csrf
                            <div class="star-rating-input mb-2" role="radiogroup" aria-label="{{ __('Rating') }}">
                                @for($i=1;$i<=5;$i++)
                                    <button type="button" class="star" data-val="{{ $i }}" aria-label="{{ trans_choice(':count star|:count stars', $i, ['count' => $i]) }}">
                                        <x-icon name="star-fill" />
                                    </button>
                                @endfor
                                <input type="hidden" name="rating" value="0">
                            </div>
                            <textarea name="comment" class="form-control mb-2" rows="3"
                                      placeholder="{{ __('Share your experience...') }}"></textarea>
                            <button class="btn btn-primary btn-sm">{{ __('Submit Review') }}</button>
                        </form>
                    </div>
                    @endif

                    @forelse($course->reviews->take(6) as $review)
                    <div class="d-flex gap-3 mb-4 pb-4 border-bottom">
                        <img src="{{ $review->user->avatarUrl() }}" class="rounded-circle" width="44" height="44" alt="{{ __('reviewer') }}">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <strong style="font-size:.9rem;">{{ $review->user->name }}</strong>
                                <div class="stars">
                                    @for($s=1;$s<=5;$s++)<x-icon :name="'star' . ($s<=$review->rating?'-fill':'')" />@endfor
                                </div>
                            </div>
                            <p class="mb-0" style="font-size:.9rem;">{{ $review->comment }}</p>
                            <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">{{ __('No reviews yet. Be the first!') }}</p>
                    @endforelse
                </div>
            </div>

            <!-- Sidebar: Enroll Card -->
            <div class="col-lg-4">
                <div class="bg-white rounded-xl shadow-brand p-4 sticky-top" style="top:80px;">
                    <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
                         class="img-fluid rounded mb-3" style="height:180px;object-fit:cover;width:100%;">

                    <div class="text-center mb-3">
                        <div style="font-size:2rem;font-weight:800;color:var(--primary);">
                            {{ $course->price == 0 ? __('courses.free') : number_format($course->price, 0, ',', ' ') . ' FCFA' }}
                        </div>
                    </div>

                    @auth
                        @if($isEnrolled)
                            <a href="{{ route('student.courses.player', $enrollment) }}" class="btn btn-secondary w-100 mb-2">
                                <x-icon name="play-fill" class="me-2" />{{ __('courses.continue_learning') }}
                            </a>
                        @else
                            <form method="POST" action="{{ route('student.enroll', $course) }}">
                                @csrf
                                <button class="btn btn-primary w-100 mb-2">
                                    <x-icon name="mortarboard" class="me-2" />{{ __('courses.enroll_now') }}
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary w-100 mb-2">
                            <x-icon name="mortarboard" class="me-2" />{{ __('courses.enroll_now') }}
                        </a>
                        <p class="text-center text-muted small mb-2">
                            {!! __(':login or register to enroll', ['login' => '<a href="' . e(route('login')) . '">' . e(__('Log in')) . '</a>']) !!}
                        </p>
                    @endauth

                    <ul class="list-unstyled mt-3" style="font-size:.85rem;">
                        <li class="py-1 border-bottom"><x-icon name="play-circle" class="me-2 text-primary" />{{ trans_choice('lms.lessons_count', $course->modules->sum(fn ($m) => $m->lessons->count()), ['count' => $course->modules->sum(fn ($m) => $m->lessons->count())]) }}</li>
                        <li class="py-1 border-bottom"><x-icon name="clock" class="me-2 text-primary" />{{ __(':hours in total', ['hours' => $course->hoursLabel()]) }}</li>
                        <li class="py-1 border-bottom"><x-icon name="bar-chart" class="me-2 text-primary" />{{ __('messages.' . $course->level) }}</li>
                        <li class="py-1 border-bottom"><x-icon name="phone" class="me-2 text-primary" />{{ __('Mobile accessible') }}</li>
                        <li class="py-1"><x-icon name="award" class="me-2 text-primary" />{{ __('Certificate of completion') }}</li>
                    </ul>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('cms.page', 'terms') }}" class="text-muted" style="font-size:.75rem;">{{ __('Terms') }}</a>
                        <button class="btn btn-link btn-sm p-0 text-muted" data-wishlist="{{ $course->id }}" style="font-size:.85rem;">
                            <span class="wishlist-icon"><x-icon name="heart" /></span> {{ __('Save') }}
                        </button>
                    </div>
                </div>

                <!-- Instructor Card -->
                <div class="bg-white rounded-xl shadow-brand p-4 mt-4">
                    <h6 class="fw-bold mb-3">{{ __('Instructor') }}</h6>
                    <div class="d-flex gap-3 align-items-center">
                        <img src="{{ $course->instructor->avatarUrl() }}" class="rounded-circle" width="56" height="56" alt="{{ e($course->instructor->name) }}">
                        <div>
                            <a href="{{ route('instructor.profile', $course->instructor) }}" class="fw-bold text-decoration-none" style="color:var(--primary);">
                                {{ $course->instructor->name }}
                            </a>
                            <div class="text-muted" style="font-size:.82rem;">{{ $course->instructor->bio ? Str::limit($course->instructor->bio, 80) : 'Expert Instructor' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Courses -->
        @if($relatedCourses->count())
        <div class="mt-5">
            <h3 class="h5 mb-4">{{ __('Related Courses') }}</h3>
            <div class="row g-4">
                @foreach($relatedCourses as $course)
                <div class="col-sm-6 col-lg-3">
                    @include('partials.course-card', ['course' => $course])
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
