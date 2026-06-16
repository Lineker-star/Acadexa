@extends('layouts.app')
@section('title', $user->name . ' — Instructor')

@section('content')
<!-- Profile Hero -->
<div style="background:var(--primary);padding:3rem 0;">
    <div class="container">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <img src="{{ $user->avatarUrl() }}" class="rounded-circle border border-3 border-white"
                 width="100" height="100" alt="{{ $user->name }}">
            <div class="text-white">
                <h2 class="fw-bold mb-1">{{ $user->name }}</h2>
                <p class="mb-1 opacity-75">Verified Instructor at ACADEXXA</p>
                <div class="d-flex gap-3 text-white-50 small">
                    <span><i class="bi bi-collection-play me-1"></i>{{ $courses->total() }} courses</span>
                    <span><i class="bi bi-people me-1"></i>{{ $totalStudents }} students</span>
                    <span><i class="bi bi-star-fill me-1" style="color:#F59E0B;"></i>{{ number_format($avgRating,1) }} avg rating</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Courses -->
            <h4 class="fw-bold mb-3">Courses by {{ $user->name }}</h4>
            <div class="row g-3">
                @forelse($courses as $course)
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0 course-card">
                        <img src="{{ $course->thumbnailUrl() }}" class="card-img-top"
                             style="height:160px;object-fit:cover;" alt="{{ e($course->title()) }}">
                        <div class="card-body">
                            <h6 class="card-title">{{ $course->title() }}</h6>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="badge bg-light text-dark border">{{ ucfirst($course->level) }}</span>
                                <a href="{{ route('courses.show', $course->slug) }}" class="btn btn-primary btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <p class="text-muted">No published courses yet.</p>
                </div>
                @endforelse
            </div>
            @if($courses->hasPages())
            <div class="mt-4">{{ $courses->links() }}</div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="bg-white rounded-xl shadow-brand p-4">
                <h5 class="fw-bold mb-3">About</h5>
                <p class="text-muted" style="font-size:.9rem;white-space:pre-wrap;">{{ $user->bio ?? 'This instructor has not added a bio yet.' }}</p>
                @if($user->website)
                <a href="{{ $user->website }}" class="btn btn-outline-primary w-100 mt-2" target="_blank">
                    <i class="bi bi-globe me-1"></i>Visit Website
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
