@extends('layouts.app')
@section('title', __('navigation.my_courses'))

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h2 class="fw-bold mb-0">{{ __('navigation.my_courses') }}</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('student.library.index') }}" class="btn btn-sm btn-outline-primary"><x-icon name="book" class="me-1" />{{ __('learn.my_library') }}</a>
            <a href="{{ route('offline') }}" class="btn btn-sm btn-outline-primary"><x-icon name="cloud-slash" class="me-1" />{{ __('lms.offline_courses') }}</a>
        </div>
    </div>

    @forelse($enrollments as $enrollment)
        @php $course = $enrollment->course; $certId = $certificates[$course->id] ?? null; @endphp
        <div class="bg-white rounded-xl shadow-brand mb-4 overflow-hidden d-flex flex-md-row flex-column">
            <img src="{{ $course->thumbnailUrl() }}" alt="" style="width:280px;max-width:100%;aspect-ratio:16/9;object-fit:cover;flex-shrink:0;background:var(--light)">
            <div class="p-4 flex-grow-1">
                <h5 class="fw-bold mb-1">{{ $course->title() }}</h5>
                <div class="text-muted small mb-3">
                    {{ __('courses.by') }} {{ $course->instructor?->name }} · <x-icon name="clock" class="me-1" />{{ $course->hoursLabel() }}
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ __('lms.progress') }}</span>
                        <span class="fw-bold" style="color:var(--primary);">{{ (float) $enrollment->progress_percent }} %</span>
                    </div>
                    <div class="progress" style="height:8px;border-radius:4px;" role="progressbar" aria-valuenow="{{ $enrollment->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $enrollment->progress_percent >= 100 ? 'bg-success' : '' }}" style="width:{{ $enrollment->progress_percent }}%;"></div>
                    </div>
                </div>

                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <a href="{{ route('student.courses.player', $enrollment) }}" class="btn btn-primary">
                        <x-icon name="play-circle" class="me-1" />
                        {{ $enrollment->progress_percent > 0 ? __('messages.continue_learning') : __('messages.start_learning') }}
                    </a>
                    <a href="{{ route('student.courses.results', $enrollment) }}" class="btn btn-outline-primary"><x-icon name="graph-up-arrow" class="me-1" />{{ __('learn.my_results') }}</a>
                    @if($certId)
                        <a href="{{ route('student.certificates.download', $certId) }}" class="btn btn-outline-success"><x-icon name="award" class="me-1" />{{ __('lms.certificate') }}</a>
                    @endif
                    <div data-offline-download="{{ $enrollment->id }}"></div>
                    <span class="text-muted small ms-auto">{{ __('lms.enrolled_on') }} {{ $enrollment->enrolled_at?->isoFormat('L') }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <x-icon name="journal-x" style="font-size:3rem;color:var(--primary);opacity:.3;" />
            <h4 class="mt-3 text-muted">{{ __('lms.no_enrollments') }}</h4>
            <a href="{{ route('courses.index') }}" class="btn btn-primary">{{ __('messages.browse_courses') }}</a>
        </div>
    @endforelse

    {{ $enrollments->links() }}
</div>
@endsection
