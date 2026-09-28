@extends('layouts.instructor')
@section('title', __('lms.grade_submission'))
@section('page-title', __('lms.grade_submission'))

@php $lesson = $submission->lesson; @endphp

@section('content')
<a href="{{ route('instructor.submissions.index') }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ __('lms.assignments') }}</a>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <div class="bg-white rounded-xl shadow-brand p-4 mb-3">
            <div class="small text-muted">{{ $lesson->module->course->title() }} · {{ $lesson->module->title() }}</div>
            <h5 class="fw-bold">{{ $lesson->title() }}</h5>
            <details class="small">
                <summary class="text-primary">{{ __('lms.assignment_instructions') }}</summary>
                <div class="lesson-content mt-2">{!! $lesson->renderedBody() !!}</div>
            </details>
        </div>

        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="{{ $submission->user->avatarUrl() }}" alt="" width="44" height="44" class="rounded-circle">
                <div>
                    <div class="fw-semibold">{{ $submission->user->name }}</div>
                    <div class="small text-muted">{{ __('lms.submitted_on') }} {{ $submission->submitted_at?->isoFormat('L LT') }}</div>
                </div>
            </div>
            @if($submission->content)
                <div class="border rounded p-3 mb-3" style="white-space:pre-wrap">{{ $submission->content }}</div>
            @endif
            @if($submission->file_path)
                <a href="{{ route('media.submission', $submission) }}" class="resource-link">
                    <x-icon name="file-earmark-arrow-down" />
                    <span class="flex-grow-1">{{ $submission->original_name }}</span>
                    <span class="small text-muted">{{ __('lms.download') }}</span>
                </a>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        <form method="POST" action="{{ route('instructor.submissions.grade', $submission) }}" class="bg-white rounded-xl shadow-brand p-4">
            @csrf
            <h6 class="fw-bold mb-3">{{ __('lms.grade') }}</h6>
            @if($submission->isGraded())
                <div class="alert {{ $submission->isPassed() ? 'alert-success' : 'alert-danger' }} small">
                    {{ __('lms.graded_by_on', ['name' => $submission->grader?->name ?? '—', 'date' => $submission->graded_at?->isoFormat('L LT')]) }}
                </div>
            @endif
            <label class="form-label">{{ __('lms.score') }}</label>
            <div class="input-group mb-1">
                <input type="number" name="score" class="form-control @error('score') is-invalid @enderror" min="0" max="{{ $lesson->assignment_max_score }}" step="0.5" required
                       value="{{ old('score', $submission->score !== null ? (float) $submission->score : '') }}">
                <span class="input-group-text">/ {{ $lesson->assignment_max_score }}</span>
                @error('score')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-text mb-3">{{ __('lms.pass_mark_is', ['score' => $lesson->assignment_pass_score]) }}</div>
            <label class="form-label">{{ __('lms.feedback') }}</label>
            <textarea name="feedback" rows="6" class="form-control mb-3" placeholder="{{ __('lms.feedback_placeholder') }}">{{ old('feedback', $submission->feedback) }}</textarea>
            <button class="btn btn-primary w-100"><x-icon name="check2-circle" class="me-1" />{{ __('lms.save_grade') }}</button>
            <p class="small text-muted mt-2 mb-0">{{ __('lms.grade_notify_help') }}</p>
        </form>
    </div>
</div>
@endsection
