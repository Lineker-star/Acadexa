@extends('layouts.app')
@section('title', $category->name())

@section('content')
<!-- Category Hero -->
<div style="background:var(--primary);padding:3rem 0;">
    <div class="container text-white text-center">
        <x-icon :name="$category->icon ?: 'book'" style="font-size:3rem" />
        <h1 class="fw-bold mt-2">{{ $category->name() }}</h1>
        <p class="opacity-75 mb-0">{{ trans_choice(':count course available|:count courses available', $courses->total(), ['count' => $courses->total()]) }}</p>
    </div>
</div>

<div class="container py-5">
    <!-- Subcategories -->
    @if($category->children->isNotEmpty())
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('categories.show', $category->slug) }}" class="btn btn-sm btn-primary">{{ __('All') }}</a>
            @foreach($category->children as $sub)
            <a href="{{ route('categories.show', $sub->slug) }}" class="btn btn-sm btn-outline-secondary">
                <x-icon :name="$sub->icon ?: 'folder'" class="me-1" />{{ $sub->name() }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Course Grid -->
    <div class="row g-4">
        @forelse($courses as $course)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm course-card">
                <a href="{{ route('courses.show', $course->slug) }}">
                    <img src="{{ $course->thumbnailUrl() }}" class="card-img-top"
                         style="height:180px;object-fit:cover;" alt="{{ e($course->title()) }}">
                </a>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-1 mb-2" style="color:#F59E0B;font-size:.85rem;">
                        <x-icon name="star-fill" />
                        <span>{{ number_format($course->avgRating(),1) }}</span>
                        <span class="text-muted">({{ $course->reviews->count() }})</span>
                    </div>
                    <h6 class="card-title fw-bold">
                        <a href="{{ route('courses.show', $course->slug) }}" class="text-dark text-decoration-none">
                            {{ $course->title() }}
                        </a>
                    </h6>
                    <div class="text-muted small mb-2">{{ $course->instructor?->name }}</div>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <div class="small text-muted">
                            <x-icon name="people" class="me-1" />{{ trans_choice(':count student|:count students', $course->enrollments->count(), ['count' => $course->enrollments->count()]) }}
                        </div>
                        <span class="badge bg-light text-dark border">{{ __('messages.' . $course->level) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5 text-muted">
            <x-icon name="collection-play" style="font-size:2.5rem;opacity:.3;" />
            <p class="mt-2">{{ __('No courses in this category yet.') }}</p>
            <a href="{{ route('courses.index') }}" class="btn btn-outline-primary">{{ __('Browse All Courses') }}</a>
        </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $courses->links() }}</div>
</div>
@endsection
