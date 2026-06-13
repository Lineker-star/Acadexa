@extends('layouts.app')
@section('title', $category->name)

@section('content')
<!-- Category Hero -->
<div style="background:var(--primary);padding:3rem 0;">
    <div class="container text-white text-center">
        <div style="font-size:3rem;">{{ $category->icon ?? '📚' }}</div>
        <h1 class="fw-bold mt-2">{{ $category->name }}</h1>
        <p class="opacity-75 mb-0">{{ $courses->total() }} courses available</p>
    </div>
</div>

<div class="container py-5">
    <!-- Subcategories -->
    @if($category->children->isNotEmpty())
    <div class="mb-4">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('categories.show', $category->slug) }}" class="btn btn-sm btn-primary">All</a>
            @foreach($category->children as $sub)
            <a href="{{ route('categories.show', $sub->slug) }}" class="btn btn-sm btn-outline-secondary">
                {{ $sub->icon ?? '' }} {{ $sub->name }}
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
                        <i class="bi bi-star-fill"></i>
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
                            <i class="bi bi-people me-1"></i>{{ $course->enrollments->count() }} students
                        </div>
                        <span class="badge bg-light text-dark border">{{ ucfirst($course->level) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-collection-play" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="mt-2">No courses in this category yet.</p>
            <a href="{{ route('courses.index') }}" class="btn btn-outline-primary">Browse All Courses</a>
        </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $courses->links() }}</div>
</div>
@endsection
