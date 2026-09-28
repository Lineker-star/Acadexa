{{-- Module exercise or final evaluation (standard, placement test or re-evaluation). --}}
@php $isFinal = $quiz->isFinal(); @endphp
<div class="mb-3">
    <div class="small text-muted">{{ $isFinal ? $course->title() : $quiz->module->title() }}</div>
    <h1 class="h4 fw-bold mb-1"><x-icon :name="$quiz->icon()" class="text-primary me-1" />
        @if($mode === 'diagnostic') {{ __('learn.diagnostic_title') }}
        @elseif($mode === 'retake') {{ __('learn.retake_title') }}
        @else {{ $quiz->title() }} @endif
    </h1>
    <div class="small text-muted">
        {{ trans_choice('lms.questions_count', $quiz->questions->count(), ['count' => $quiz->questions->count()]) }}
        @if($mode === 'standard') · {{ __('lms.pass_at', ['score' => $quiz->effectivePassingScore()]) }} @endif
        @if($done) · <span class="text-success"><x-icon name="check-circle-fill" class="me-1" />{{ __('lms.completed') }}</span> @endif
    </div>
</div>

@if($mode === 'diagnostic')
    <div class="alert alert-info small"><x-icon name="speedometer2" class="me-1" />{{ __('learn.diagnostic_intro') }}</div>
@elseif($mode === 'retake')
    <div class="alert alert-info small"><x-icon name="arrow-repeat" class="me-1" />{{ __('learn.retake_intro') }}</div>
@elseif($isFinal)
    <div class="alert alert-light border small"><x-icon name="trophy" class="me-1" />{{ __('learn.final_intro') }}</div>
@else
    <div class="alert alert-light border small"><x-icon name="clipboard-check" class="me-1" />{{ __('learn.module_exercise_intro') }}</div>
@endif

@if($isFinal && $knowledge && $knowledge['evaluations']->isNotEmpty())
    @include('student.courses.partials.knowledge-summary', ['profile' => $knowledge, 'compact' => true])
@endif

@if($mode === 'standard' && $isFinal && $done && $retakeAt !== null)
    <div class="alert alert-success d-flex flex-wrap align-items-center gap-2">
        <x-icon name="award" class="fs-4" />
        <span class="flex-grow-1">{{ __('learn.final_passed_retake_hint') }}</span>
        @if($retakeAt->isPast())
            <a class="btn btn-sm btn-primary" href="{{ route('student.courses.player', $enrollment) }}?assessment={{ $quiz->id }}&mode=retake">{{ __('learn.retake_start') }}</a>
        @else
            <span class="small">{{ __('learn.retake_available_on', ['date' => $retakeAt->isoFormat('LL')]) }}</span>
        @endif
    </div>
@else
    @include('student.courses.partials.quiz', ['quiz' => $quiz, 'state' => $state, 'mode' => $mode])
@endif
