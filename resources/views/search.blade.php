@extends('layouts.app')
@section('title', 'Search: ' . $query)

@section('content')
<div class="container py-5">
    <h2 class="fw-bold mb-1">Search Results</h2>
    <p class="text-muted mb-4">
        {{ $results->total() }} results for "<strong>{{ $query }}</strong>"
    </p>

    <!-- Search Bar -->
    <form method="GET" action="{{ route('search') }}" class="mb-4">
        <div class="input-group input-group-lg" style="max-width:600px;">
            <input type="text" name="q" class="form-control" value="{{ $query }}" placeholder="Search courses...">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    @forelse($results as $course)
    <div class="d-flex gap-3 bg-white rounded-xl shadow-brand mb-3 overflow-hidden">
        <a href="{{ route('courses.show', $course->slug) }}">
            <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
                 style="width:160px;height:100px;object-fit:cover;" class="flex-shrink-0">
        </a>
        <div class="flex-grow-1 py-3 pe-3">
            <h5 class="mb-1">
                <a href="{{ route('courses.show', $course->slug) }}" class="text-dark text-decoration-none">
                    {{ $course->title() }}
                </a>
            </h5>
            <div class="text-muted small mb-2">
                {{ $course->instructor?->name }} • {{ ucfirst($course->level) }}
                • {{ $course->category?->name }}
            </div>
            <p class="text-muted small mb-2">{{ Str::limit($course->description(), 120) }}</p>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-1 text-warning small">
                    <i class="bi bi-star-fill"></i>
                    <span>{{ number_format($course->avgRating(),1) }}</span>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-people me-1"></i>{{ $course->enrollments->count() }} students
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-search" style="font-size:3rem;opacity:.2;"></i>
        <h4 class="mt-3 text-muted">No courses found for "{{ $query }}"</h4>
        <p class="text-muted">Try different keywords or browse our categories.</p>
        <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse All Courses</a>
    </div>
    @endforelse

    @if($results->hasPages())
    <div class="mt-4">{{ $results->appends(['q' => $query])->links() }}</div>
    @endif
</div>
@endsection
