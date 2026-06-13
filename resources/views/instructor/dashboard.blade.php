@extends('layouts.instructor')
@section('title', 'Instructor Dashboard')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold">Welcome back, {{ auth()->user()->name }}</h4>
    <p class="text-muted mb-0">Manage your courses and track student progress from here.</p>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:var(--primary);">{{ $totalCourses }}</div>
            <div class="text-muted small">My Courses</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:var(--primary);">{{ $publishedCourses }}</div>
            <div class="text-muted small">Published</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:var(--accent);">{{ $totalStudents }}</div>
            <div class="text-muted small">Total Students</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-xl shadow-brand p-3 text-center">
            <div style="font-size:1.6rem;font-weight:700;color:#F59E0B;">{{ number_format($avgRating ?? 0, 1) }} ⭐</div>
            <div class="text-muted small">Avg Rating</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- My Courses -->
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">My Courses</h5>
                <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>New Course
                </a>
            </div>
            @forelse($courses as $course)
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
                     class="rounded" style="width:56px;height:40px;object-fit:cover;">
                <div class="flex-grow-1">
                    <div style="font-size:.88rem;font-weight:600;">{{ $course->title() }}</div>
                    <div class="text-muted" style="font-size:.75rem;">
                        {{ $course->enrollments->count() }} students
                        • {{ $course->modules->count() }} modules
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ match($course->status) {'published'=>'bg-success','pending'=>'bg-warning text-dark','rejected'=>'bg-danger',default=>'bg-secondary'} }}">
                        {{ ucfirst($course->status) }}
                    </span>
                    <a href="{{ route('instructor.courses.edit', $course) }}" class="btn btn-outline-primary btn-sm">
                        Edit
                    </a>
                </div>
            </div>
            @empty
            <div class="text-center py-5 text-muted">
                <i class="bi bi-collection-play" style="font-size:2rem;"></i>
                <p class="mt-2">No courses yet. Create your first course!</p>
                <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary">Create Course</a>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Reviews & Announcements -->
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
            <h6 class="fw-bold mb-3">Recent Reviews</h6>
            @forelse($recentReviews as $review)
            <div class="mb-3 pb-3 border-bottom">
                <div class="d-flex gap-1 mb-1" style="color:#F59E0B;font-size:.8rem;">
                    @for($s=1;$s<=5;$s++)<i class="bi bi-star{{ $s<=$review->rating?'-fill':'' }}"></i>@endfor
                </div>
                <p class="mb-1" style="font-size:.82rem;">{{ Str::limit($review->comment, 80) }}</p>
                <div class="text-muted" style="font-size:.75rem;">
                    {{ $review->user?->name }} on {{ Str::limit($review->course?->title(), 30) }}
                </div>
            </div>
            @empty
            <p class="text-muted small">No reviews yet.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl shadow-brand p-4">
            <h6 class="fw-bold mb-3">Announcements</h6>
            @forelse($announcements as $ann)
            <div class="mb-2 pb-2 border-bottom">
                <div style="font-size:.85rem;font-weight:600;">{{ $ann->title() }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ $ann->created_at->diffForHumans() }}</div>
            </div>
            @empty
            <p class="text-muted small">No announcements.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
