@extends('layouts.instructor')
@section('title', 'My Courses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">My Courses</h4>
    <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Course
    </a>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl shadow-brand p-3 mb-4">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <select name="status" class="form-select form-select-sm" style="width:auto;">
            <option value="">All Status</option>
            <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending Review</option>
            <option value="published" {{ request('status')=='published'?'selected':'' }}>Published</option>
            <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>

@forelse($courses as $course)
<div class="bg-white rounded-xl shadow-brand p-3 mb-3 d-flex gap-3 align-items-start">
    <img src="{{ $course->thumbnailUrl() }}" alt="{{ e($course->title()) }}"
         class="rounded" style="width:80px;height:56px;object-fit:cover;flex-shrink:0;">
    <div class="flex-grow-1">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div>
                <div style="font-size:.95rem;font-weight:700;">{{ $course->title() }}</div>
                <div class="text-muted small">
                    {{ $course->category?->name ?? 'No category' }}
                    • {{ ucfirst($course->level) }}
                    • {{ $course->modules->count() }} modules
                    • {{ $course->durationFormatted() }}
                </div>
            </div>
            <span class="badge {{ match($course->status) {'published'=>'bg-success','pending'=>'bg-warning text-dark','rejected'=>'bg-danger',default=>'bg-secondary'} }} flex-shrink-0">
                {{ ucfirst($course->status) }}
            </span>
        </div>

        @if($course->status === 'rejected' && $course->admin_feedback)
        <div class="alert alert-danger py-1 px-2 mt-2 mb-0" style="font-size:.8rem;">
            <strong>Feedback:</strong> {{ $course->admin_feedback }}
        </div>
        @endif

        <div class="d-flex gap-2 mt-2 flex-wrap">
            <a href="{{ route('instructor.courses.edit', $course) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @if($course->status === 'draft')
            <form method="POST" action="{{ route('instructor.courses.submit', $course) }}">
                @csrf
                <button class="btn btn-success btn-sm">
                    <i class="bi bi-send me-1"></i>Submit for Review
                </button>
            </form>
            @endif
            @if($course->status === 'published')
            <a href="{{ route('courses.show', $course->slug) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="bi bi-eye me-1"></i>View Live
            </a>
            @endif
            <form method="POST" action="{{ route('instructor.courses.destroy', $course) }}">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm"
                        onclick="return confirm('Delete this course? This cannot be undone.')">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="text-center d-none d-md-block flex-shrink-0" style="min-width:80px;">
        <div style="font-size:1.3rem;font-weight:700;color:var(--primary);">
            {{ $course->enrollments->count() }}
        </div>
        <div class="text-muted" style="font-size:.7rem;">Students</div>
    </div>
</div>
@empty
<div class="bg-white rounded-xl shadow-brand p-5 text-center">
    <i class="bi bi-collection-play" style="font-size:2.5rem;color:var(--primary);opacity:.4;"></i>
    <p class="text-muted mt-3">No courses yet. Start creating your first course!</p>
    <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary">Create My First Course</a>
</div>
@endforelse
<div class="mt-3">{{ $courses->links() }}</div>
@endsection
