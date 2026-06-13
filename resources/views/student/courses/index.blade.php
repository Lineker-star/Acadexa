@extends('layouts.app')
@section('title', __('navigation.my_courses'))

@section('content')
<div class="container py-5">
    <h2 class="fw-bold mb-4">My Courses</h2>

    @forelse($enrollments as $enrollment)
    @php $course = $enrollment->course; @endphp
    <div class="bg-white rounded-xl shadow-brand mb-4 overflow-hidden d-flex flex-md-row flex-column">
        <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
             class="img-fluid" style="width:280px;height:160px;object-fit:cover;flex-shrink:0;">
        <div class="p-4 flex-grow-1">
            <h5 class="fw-bold mb-1">{{ $course->title() }}</h5>
            <div class="text-muted small mb-3">{{ __('courses.by') }} {{ $course->instructor?->name }}</div>

            <!-- Progress -->
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Progress</span>
                    <span class="fw-bold" style="color:var(--primary);">{{ $enrollment->progress_percent }}%</span>
                </div>
                <div class="progress" style="height:8px;border-radius:4px;">
                    <div class="progress-bar" role="progressbar"
                         style="width:{{ $enrollment->progress_percent }}%;background:var(--primary);"
                         aria-valuenow="{{ $enrollment->progress_percent }}"
                         aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                <a href="{{ route('student.courses.player', $course->slug) }}" class="btn btn-primary">
                    <i class="bi bi-play-circle me-1"></i>
                    {{ $enrollment->progress_percent > 0 ? 'Continue Learning' : 'Start Learning' }}
                </a>
                @if($enrollment->progress_percent >= 100)
                <span class="badge bg-success">
                    <i class="bi bi-check-circle me-1"></i>Completed
                </span>
                @if($enrollment->certificate)
                <a href="{{ route('student.certificates.download', $enrollment->certificate->certificate_code) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download me-1"></i>Certificate
                </a>
                @endif
                @endif
                <span class="text-muted small ms-auto">
                    Enrolled {{ $enrollment->enrolled_at->format('M d, Y') }}
                </span>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="bi bi-journal-x" style="font-size:3rem;color:var(--primary);opacity:.3;"></i>
        <h4 class="mt-3 text-muted">No courses yet</h4>
        <p class="text-muted">Browse our catalog and enroll in your first course.</p>
        <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
    </div>
    @endforelse

    @if($enrollments->hasPages())
    <div class="mt-3">{{ $enrollments->links() }}</div>
    @endif
</div>
@endsection
