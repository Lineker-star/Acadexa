@extends('layouts.instructor')
@section('title', __('lms.my_courses'))
@section('page-title', __('lms.my_courses'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">{{ __('lms.my_courses') }}</h4>
    <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary"><x-icon name="plus-lg" class="me-1" />{{ __('lms.new_course') }}</a>
</div>

@forelse($courses as $course)
    @php
        $statusClass = match($course->status) {
            'published' => 'bg-success', 'pending' => 'bg-warning text-dark', 'rejected' => 'bg-danger', default => 'bg-secondary',
        };
    @endphp
    <div class="bg-white rounded-xl shadow-brand mb-3 d-flex flex-column flex-md-row overflow-hidden">
        <img src="{{ $course->thumbnailUrl() }}" alt="" style="width:220px;max-width:100%;aspect-ratio:16/9;object-fit:cover;background:var(--light);" class="flex-shrink-0">
        <div class="p-3 flex-grow-1 d-flex flex-column">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge {{ $statusClass }}">{{ __('lms.status_' . $course->status) }}</span>
                <span class="small text-muted">{{ $course->category?->name() }}</span>
            </div>
            <h6 class="fw-bold mb-1">{{ $course->title() }}</h6>
            <div class="small text-muted mb-2">
                <x-icon name="clock" class="me-1" />{{ $course->hoursLabel() }}
                · <x-icon name="collection" class="me-1" />{{ trans_choice('lms.modules_count', $course->modules_count, ['count' => $course->modules_count]) }}
                · <x-icon name="people" class="me-1" />{{ $course->enrollments_count }}
                · <x-icon name="star-fill" class="text-warning me-1" />{{ number_format((float) $course->reviews_avg_rating, 1) }} ({{ $course->reviews_count }})
            </div>
            @if($course->status === 'rejected' && $course->admin_feedback)
                <div class="small text-danger mb-2"><x-icon name="chat-left-text" class="me-1" />{{ \Illuminate\Support\Str::limit($course->admin_feedback, 140) }}</div>
            @endif
            <div class="mt-auto d-flex flex-wrap gap-2">
                <a href="{{ route('instructor.courses.edit', $course) }}" class="btn btn-sm btn-primary"><x-icon name="pencil" class="me-1" />{{ __('lms.edit') }}</a>
                <a href="{{ route('instructor.courses.edit', ['course' => $course, 'tab' => 'curriculum']) }}" class="btn btn-sm btn-outline-primary"><x-icon name="list-nested" class="me-1" />{{ __('lms.tab_curriculum') }}</a>
                <a href="{{ route('instructor.students.index', $course) }}" class="btn btn-sm btn-outline-secondary"><x-icon name="graph-up" class="me-1" />{{ __('lms.students_and_stats') }}</a>
            </div>
        </div>
    </div>
@empty
    <div class="text-center bg-white rounded-xl shadow-brand py-5">
        <x-icon name="collection-play" style="font-size:2.5rem;color:var(--bs-primary);opacity:.4" />
        <h5 class="mt-3">{{ __('lms.no_courses_yet') }}</h5>
        <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary mt-2">{{ __('lms.new_course') }}</a>
    </div>
@endforelse

{{ $courses->links() }}
@endsection
