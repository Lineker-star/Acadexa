@extends('layouts.instructor')
@section('title', $enrollment->user->name)
@section('page-title', __('lms.student_progress'))

@section('content')
<a href="{{ route('instructor.students.index', $course) }}" class="small"><x-icon name="arrow-left" class="me-1" />{{ __('lms.students') }}</a>

<div class="bg-white rounded-xl shadow-brand p-4 my-3 d-flex flex-wrap align-items-center gap-3">
    <img src="{{ $enrollment->user->avatarUrl() }}" alt="" width="56" height="56" class="rounded-circle">
    <div class="flex-grow-1">
        <h5 class="fw-bold mb-0">{{ $enrollment->user->name }}</h5>
        <div class="small text-muted">{{ $enrollment->user->email }} · {{ __('lms.enrolled_on') }} {{ $enrollment->enrolled_at?->isoFormat('L') }}</div>
    </div>
    <div style="min-width:200px">
        <div class="d-flex justify-content-between small"><span>{{ __('lms.progress') }}</span><strong>{{ (float) $enrollment->progress_percent }} %</strong></div>
        <div class="progress" style="height:8px"><div class="progress-bar" style="width:{{ $enrollment->progress_percent }}%"></div></div>
    </div>
    <form method="POST" action="{{ route('messages.store') }}" class="w-100 mt-2">
        @csrf
        <input type="hidden" name="course_id" value="{{ $course->id }}">
        <input type="hidden" name="student_id" value="{{ $enrollment->user_id }}">
        <details>
            <summary class="btn btn-sm btn-outline-primary"><x-icon name="envelope" class="me-1" />{{ __('lms.send_message') }}</summary>
            <div class="mt-2">
                <input type="text" name="subject" class="form-control form-control-sm mb-2" required maxlength="255" placeholder="{{ __('lms.subject') }}" value="{{ $course->title() }}">
                <textarea name="body" class="form-control form-control-sm mb-2" rows="3" required maxlength="5000"></textarea>
                <button class="btn btn-sm btn-primary">{{ __('lms.send') }}</button>
            </div>
        </details>
    </form>
</div>

<div class="bg-white rounded-xl shadow-brand p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h5 class="fw-bold mb-0"><x-icon name="graph-up-arrow" class="text-primary me-1" />{{ __('learn.knowledge_evolution') }}</h5>
        <a href="{{ route('instructor.students.knowledge', $course) }}" class="small">{{ __('learn.knowledge_tracking') }}</a>
    </div>
    <p class="small text-muted">{{ __('learn.knowledge_evolution_help') }}</p>
    @if($profile['evaluations']->isEmpty())
        <div class="alert alert-light border small mb-0">{{ __('learn.student_not_evaluated') }}</div>
    @else
        @include('student.courses.partials.knowledge-summary', ['profile' => $profile])
    @endif
    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <h6 class="fw-bold small">{{ __('learn.mastery_by_module') }}</h6>
            @foreach($profile['modules'] as $row)
                @php $value = $row['evaluation'] ?? $row['exercise']; @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between small"><span class="text-truncate me-2">{{ $row['title'] }}</span><strong>{{ $value !== null ? round($value) . ' %' : '—' }}</strong></div>
                    <div class="progress" style="height:5px"><div class="progress-bar {{ $value !== null && $value < config('lms.assessment.pass_percent') ? 'bg-warning' : 'bg-success' }}" style="width:{{ $value ?? 0 }}%"></div></div>
                </div>
            @endforeach
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold small">{{ __('learn.while_studying') }}</h6>
            <dl class="row small mb-0">
                <dt class="col-8 fw-normal text-muted">{{ __('learn.lesson_quiz_average') }}</dt><dd class="col-4 text-end fw-semibold">{{ $profile['lesson_avg'] !== null ? $profile['lesson_avg'] . ' %' : '—' }}</dd>
                <dt class="col-8 fw-normal text-muted">{{ __('learn.module_exercise_average') }}</dt><dd class="col-4 text-end fw-semibold">{{ $profile['exercise_avg'] !== null ? $profile['exercise_avg'] . ' %' : '—' }}</dd>
                <dt class="col-8 fw-normal text-muted">{{ __('learn.best_evaluation') }}</dt><dd class="col-4 text-end fw-semibold">{{ $profile['best'] !== null ? $profile['best'] . ' %' : '—' }}</dd>
            </dl>
        </div>
    </div>
</div>

@foreach($course->modules as $module)
    <div class="bg-white rounded-xl shadow-brand mb-3">
        <div class="p-3 border-bottom fw-semibold">{{ $module->title() }} <span class="small text-muted fw-normal">· {{ $module->hoursLabel() }}</span></div>
        <ul class="list-unstyled mb-0">
            @foreach($module->lessons as $lesson)
                @php
                    $done = $completed[$lesson->id] ?? null;
                    $quizScore = $lesson->quiz ? ($bestScores[$lesson->quiz->id] ?? null) : null;
                    $sub = $submissions[$lesson->id] ?? null;
                @endphp
                <li class="d-flex align-items-center gap-3 px-3 py-2 border-bottom small">
                    <x-icon :name="$done ? 'check-circle-fill' : 'circle'" :class="$done ? 'text-success' : 'text-muted'" />
                    <span class="flex-grow-1"><x-icon :name="$lesson->icon()" class="me-1 text-muted" />{{ $lesson->title() }}</span>
                    @if($quizScore)
                        <span class="badge bg-light text-dark border">{{ __('lms.best_score') }} {{ (float) $quizScore->best }} % ({{ $quizScore->attempts }}×)</span>
                    @endif
                    @if($sub)
                        <a href="{{ route('instructor.submissions.show', $sub) }}" class="badge {{ $sub->isGraded() ? 'bg-success' : 'bg-warning text-dark' }} text-decoration-none">
                            {{ $sub->isGraded() ? rtrim(rtrim((string) $sub->score, '0'), '.') . '/' . $lesson->assignment_max_score : __('lms.to_grade') }}
                        </a>
                    @endif
                    <span class="text-muted">{{ $done ? \Illuminate\Support\Carbon::parse($done)->isoFormat('L') : '' }}</span>
                </li>
            @endforeach
            @if($module->exam)
                @php $examScore = $bestScores[$module->exam->id] ?? null; @endphp
                <li class="d-flex align-items-center gap-3 px-3 py-2 small" style="background:#F8FAFF">
                    <x-icon :name="$examScore && $examScore->passed ? 'check-circle-fill' : 'circle'" :class="$examScore && $examScore->passed ? 'text-success' : 'text-muted'" />
                    <span class="flex-grow-1 fw-semibold"><x-icon name="clipboard-check" class="me-1 text-primary" />{{ __('learn.module_exercise') }}</span>
                    @php $handIn = $exerciseAnswers[$module->exam->id] ?? null; $openExam = $module->exam->questions->contains(fn ($q) => $q->isOpen()); @endphp
                    @if($openExam && $handIn)<span class="badge bg-success">{{ __('learn.exercise_handed_in') }} · {{ $handIn->attempted_at?->isoFormat('L') }}</span>
                    @elseif($examScore)<span class="badge bg-light text-dark border">{{ __('lms.best_score') }} {{ (float) $examScore->best }} % ({{ $examScore->attempts }}×)</span>@endif
                </li>
                @if($openExam && $handIn)
                <li class="px-3 py-2 small border-top">
                    <details>
                        <summary class="fw-semibold">{{ __('learn.student_answers') }}</summary>
                        @foreach($module->exam->questions as $question)
                            <div class="mt-3">
                                <div class="fw-semibold">{{ $loop->iteration }}. {{ $question->question }}</div>
                                <div class="border rounded bg-light p-2 my-1" style="white-space:pre-wrap">{{ is_array($handIn->answers[$question->id] ?? null) ? '' : ($handIn->answers[$question->id] ?? '—') }}</div>
                                <div class="model-answer"><div class="small fw-semibold text-success mb-1">{{ __('learn.model_answer') }}</div>{!! nl2br(e($question->model_answer)) !!}</div>
                            </div>
                        @endforeach
                    </details>
                </li>
                @endif
            @endif
        </ul>
    </div>
@endforeach

@if($course->finalExam)
    @php $finalScore = $bestScores[$course->finalExam->id] ?? null; @endphp
    <div class="bg-white rounded-xl shadow-brand p-3 d-flex align-items-center gap-3 small">
        <x-icon :name="$finalScore && $finalScore->passed ? 'check-circle-fill' : 'circle'" :class="$finalScore && $finalScore->passed ? 'text-success' : 'text-muted'" />
        <span class="flex-grow-1 fw-semibold"><x-icon name="trophy" class="me-1 text-warning" />{{ __('learn.final_evaluation') }}</span>
        @if($finalScore)<span class="badge bg-light text-dark border">{{ __('lms.best_score') }} {{ (float) $finalScore->best }} % ({{ $finalScore->attempts }}×)</span>@endif
    </div>
@endif
@endsection
