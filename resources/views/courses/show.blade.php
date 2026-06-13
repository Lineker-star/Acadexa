@extends('layouts.app')
@php $trans = $course->translation(); @endphp
@section('title', $trans?->title ?? $course->title())
@section('meta_description', Str::limit(strip_tags($trans?->description ?? ''), 160))

@section('content')
<!-- Breadcrumb -->
<div class="breadcrumb-bar">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Courses</a></li>
            <li class="breadcrumb-item active">{{ $course->title() }}</li>
        </ol>
    </div>
</div>

<!-- Hero -->
<section class="hero-section py-5"
         style="min-height:380px;background-image:url('{{ $course->thumbnailUrl() }}');"
         aria-label="{{ e($course->title()) }} course banner">
    <div class="container hero-content">
        <div class="row">
            <div class="col-lg-8">
                <span class="badge-level {{ $course->level }} mb-3 d-inline-block">{{ ucfirst($course->level) }}</span>
                <h1 style="font-size:clamp(1.6rem,3vw,2.5rem);">{{ $course->title() }}</h1>
                <p style="color:rgba(255,255,255,.9);max-width:600px;">{{ Str::limit(strip_tags($course->description()), 200) }}</p>
                <div class="d-flex align-items-center gap-3 mt-3 flex-wrap" style="font-size:.9rem;color:rgba(255,255,255,.85);">
                    <span><i class="bi bi-star-fill me-1" style="color:#F59E0B;"></i>{{ $course->avgRating() }} ({{ $course->reviewCount() }} reviews)</span>
                    <span><i class="bi bi-people me-1"></i>{{ $course->enrollmentCount() }} students</span>
                    <span><i class="bi bi-clock me-1"></i>{{ $course->durationFormatted() }}</span>
                    <span><i class="bi bi-person-circle me-1"></i>
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
                <!-- What You Learn -->
                @if($trans?->what_you_learn)
                <div class="bg-white rounded-xl shadow-brand p-4 mb-4">
                    <h3 class="h5 mb-3">{{ __('courses.what_you_learn') }}</h3>
                    <div class="row g-2">
                        @foreach(explode("\n", $trans->what_you_learn) as $item)
                            @if(trim($item))
                            <div class="col-md-6 d-flex gap-2">
                                <i class="bi bi-check2-circle text-success mt-1"></i>
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
                                        {{ $module->lessons->count() }} lessons
                                    </span>
                                </button>
                            </h2>
                            <div id="module_{{ $module->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                                <div class="accordion-body p-0">
                                    @foreach($module->lessons as $lesson)
                                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom" style="font-size:.9rem;">
                                        <i class="bi bi-{{ $lesson->type === 'video' ? 'play-circle' : ($lesson->type === 'quiz' ? 'patch-question' : 'file-text') }} text-primary"></i>
                                        <span class="flex-grow-1">{{ $lesson->title(app()->getLocale()) }}</span>
                                        @if($lesson->is_free_preview)
                                            <a href="#" class="btn btn-outline-primary btn-sm" style="font-size:.75rem;">Preview</a>
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

                    @if($isEnrolled && !$course->reviews->where('user_id', auth()->id())->count())
                    <div class="mb-4 p-3 bg-light rounded">
                        <h6>Leave a Review</h6>
                        <form method="POST" action="{{ route('student.review.store', $course) }}">
                            @csrf
                            <div class="star-rating-input mb-2">
                                @for($i=1;$i<=5;$i++)
                                    <span class="star bi bi-star-fill" data-val="{{ $i }}"></span>
                                @endfor
                                <input type="hidden" name="rating" value="0">
                            </div>
                            <textarea name="comment" class="form-control mb-2" rows="3"
                                      placeholder="Share your experience..."></textarea>
                            <button class="btn btn-primary btn-sm">Submit Review</button>
                        </form>
                    </div>
                    @endif

                    @forelse($course->reviews->take(6) as $review)
                    <div class="d-flex gap-3 mb-4 pb-4 border-bottom">
                        <img src="{{ $review->user->avatarUrl() }}" class="rounded-circle" width="44" height="44" alt="reviewer">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <strong style="font-size:.9rem;">{{ $review->user->name }}</strong>
                                <div class="stars">
                                    @for($s=1;$s<=5;$s++)<i class="bi bi-star{{ $s<=$review->rating?'-fill':'' }}"></i>@endfor
                                </div>
                            </div>
                            <p class="mb-0" style="font-size:.9rem;">{{ $review->comment }}</p>
                            <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">No reviews yet. Be the first!</p>
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
                            {{ $course->price == 0 ? 'FREE' : 'XAF '.number_format($course->price) }}
                        </div>
                    </div>

                    @auth
                        @if($isEnrolled)
                            <a href="{{ route('student.courses.player', $enrollment) }}" class="btn btn-secondary w-100 mb-2">
                                <i class="bi bi-play-fill me-2"></i>{{ __('courses.continue_learning') }}
                            </a>
                        @else
                            <form method="POST" action="{{ route('student.enroll', $course) }}">
                                @csrf
                                <button class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-mortarboard me-2"></i>{{ __('courses.enroll_now') }}
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-mortarboard me-2"></i>{{ __('courses.enroll_now') }}
                        </a>
                        <p class="text-center text-muted small mb-2">
                            <a href="{{ route('login') }}">Log in</a> or register to enroll
                        </p>
                    @endauth

                    <ul class="list-unstyled mt-3" style="font-size:.85rem;">
                        <li class="py-1 border-bottom"><i class="bi bi-play-circle me-2 text-primary"></i>{{ $course->modules->sum(fn($m)=>$m->lessons->count()) }} lessons</li>
                        <li class="py-1 border-bottom"><i class="bi bi-clock me-2 text-primary"></i>{{ $course->durationFormatted() }} total</li>
                        <li class="py-1 border-bottom"><i class="bi bi-bar-chart me-2 text-primary"></i>{{ ucfirst($course->level) }}</li>
                        <li class="py-1 border-bottom"><i class="bi bi-phone me-2 text-primary"></i>Mobile accessible</li>
                        <li class="py-1"><i class="bi bi-award me-2 text-primary"></i>Certificate of completion</li>
                    </ul>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('cms.page', 'terms') }}" class="text-muted" style="font-size:.75rem;">Terms</a>
                        <button class="btn btn-link btn-sm p-0 text-muted" data-wishlist="{{ $course->id }}" style="font-size:.85rem;">
                            <span class="wishlist-icon">🤍</span> Save
                        </button>
                    </div>
                </div>

                <!-- Instructor Card -->
                <div class="bg-white rounded-xl shadow-brand p-4 mt-4">
                    <h6 class="fw-bold mb-3">Instructor</h6>
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
            <h3 class="h5 mb-4">Related Courses</h3>
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
